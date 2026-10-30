<?php

namespace App\Livewire;

use App\Jobs\SendWhatsappAnnouncement;
use App\Models\SchoolClass;
use App\Models\SchoolWhatsappConfiguration;
use App\Models\StudentGuardian;
use App\Models\User;
use App\Models\WhatsappBroadcast;
use App\Models\WhatsappContact;
use App\Models\WhatsappDelivery;
use App\Services\SchoolWhatsapp;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class WhatsappAnnouncements extends Component
{
    use WithPagination;

    public string $title = '';

    public string $templateKey = '';

    public string $audience = 'all';

    public string $previewToken = '';

    public string $previewNonce = '';

    public string $search = '';

    public array $parameters = [];

    public array $classIds = [];

    public array $contactIds = [];

    public int $previewCount = 0;

    public int $historyId = 0;

    public string $contactName = '';

    public string $contactPhone = '';

    public string $consentSource = '';

    public bool $consent = false;

    private function authorizeSend(): void
    {
        abort_unless(Auth::user()?->hasPermission('parents.communications'), 403);
    }

    private function authorizeContacts(): void
    {
        $this->authorizeSend();
        abort_unless(Auth::user()->hasPermission('parents.manage'), 403);
    }

    private function configuration(): ?SchoolWhatsappConfiguration
    {
        return SchoolWhatsappConfiguration::where('school_id', Auth::user()->school_id)->first();
    }

    private function template(): ?array
    {
        return collect($this->configuration()?->templates ?? [])->first(fn ($t) => $this->templateKey === $t['name'].'|'.$t['language']);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTemplateKey(): void
    {
        $this->parameters = [];
        $this->previewToken = '';
    }

    private function recipients()
    {
        $q = WhatsappContact::where('school_id', Auth::user()->school_id)->whereNotNull('consented_at')->whereNull('opted_out_at');
        if ($this->audience === 'selected') {
            $q->whereIn('id', $this->contactIds);
        }
        if ($this->audience === 'classes') {
            $ids = SchoolClass::where('school_id', Auth::user()->school_id)->whereIn('id', $this->classIds)->pluck('id');
            $phones = StudentGuardian::whereHas('student', fn ($s) => $s->where('school_id', Auth::user()->school_id)->whereIn('school_class_id', $ids))->pluck('phone')
                ->merge(User::where('school_id', Auth::user()->school_id)->where('role', 'parent')->whereHas('portalStudents', fn ($s) => $s->whereIn('school_class_id', $ids))->pluck('phone'))
                ->map(fn ($p) => SchoolWhatsapp::phone($p))->filter()->unique()->values();
            $q->whereIn('phone', $phones);
        }

        return $q->orderBy('id')->get();
    }

    private function selection(): array
    {
        $this->authorizeSend();
        ksort($this->parameters);
        $this->validate(['title' => ['required', 'string', 'max:255'], 'templateKey' => ['required', 'string', 'max:300'], 'audience' => ['required', 'in:all,classes,selected'], 'parameters' => ['array', 'max:30'], 'parameters.*' => ['required', 'string', 'max:1024'], 'classIds' => ['array', 'max:100'], 'classIds.*' => ['integer'], 'contactIds' => ['array', 'max:5000'], 'contactIds.*' => ['integer']]);
        $config = $this->configuration();
        $template = $this->template();
        if (! $config?->isReady() || ! $template) {
            throw ValidationException::withMessages(['templateKey' => 'Connect and enable WhatsApp, then choose an approved template.']);
        }
        if (count($this->parameters) !== $template['parameters'] || array_keys($this->parameters) !== ($template['parameters'] ? range(0, $template['parameters'] - 1) : [])) {
            throw ValidationException::withMessages(['parameters' => 'Fill every template variable before previewing.']);
        }
        if (strlen(SchoolWhatsapp::preview($template['body'], $this->parameters)) > 4096) {
            throw ValidationException::withMessages(['parameters' => 'The completed message exceeds 4,096 characters.']);
        }
        $recipients = $this->recipients();
        if ($recipients->isEmpty()) {
            throw ValidationException::withMessages(['audience' => 'No opted-in parents match this selection. Record consent or change the audience.']);
        }

        return [$config, $template, $recipients];
    }

    private function fingerprint($config, array $template, $recipients): string
    {
        return hash_hmac('sha256', json_encode([$this->previewNonce, Auth::id(), Auth::user()->school_id, $config->phone_number_id, $this->title, $this->audience, $template, $this->parameters, $recipients->map(fn ($c) => [$c->id, $c->phone, $c->consented_at?->toIso8601String()])->all()], JSON_THROW_ON_ERROR), config('app.key'));
    }

    public function preview(): void
    {
        [$config,$template,$recipients] = $this->selection();
        $this->previewNonce = (string) Str::uuid();
        $this->previewToken = $this->fingerprint($config, $template, $recipients);
        $this->previewCount = $recipients->count();
    }

    public function send(): void
    {
        [$config,$template,$recipients] = $this->selection();
        if (! $this->previewToken || ! hash_equals($this->fingerprint($config, $template, $recipients), $this->previewToken)) {
            $this->addError('previewToken', 'The message or recipient list changed. Preview again before sending.');

            return;
        }
        $connection = config('queue.default');
        if (in_array(config('queue.connections.'.$connection.'.driver'), ['sync', 'null', null], true)) {
            $this->addError('previewToken', 'A background queue must be configured before WhatsApp broadcasts can be sent.');

            return;
        }
        try {
            DB::transaction(function () use ($config, $template, $recipients) {
                if (WhatsappBroadcast::where('preview_key', $this->previewToken)->exists()) {
                    return;
                }
                $broadcast = WhatsappBroadcast::create(['school_id' => Auth::user()->school_id, 'created_by' => Auth::id(), 'preview_key' => $this->previewToken, 'title' => $this->title, 'phone_number_id' => $config->phone_number_id, 'template_name' => $template['name'], 'language' => $template['language'], 'body' => $template['body'], 'footer' => $template['footer'] ?? '', 'parameters' => $this->parameters, 'audience' => $this->audience, 'recipient_count' => $recipients->count()]);
                foreach ($recipients as $contact) {
                    $delivery = $broadcast->deliveries()->create(['whatsapp_contact_id' => $contact->id, 'phone' => $contact->phone]);
                    $schoolId = (int) Auth::user()->school_id;
                    DB::afterCommit(function () use ($delivery, $schoolId) {
                        SendWhatsappAnnouncement::dispatch($delivery->id, $schoolId);
                        $delivery->update(['queued_at' => now()]);
                    });
                }
            });
        } catch (\Throwable $e) {
            if (! WhatsappBroadcast::where('preview_key', $this->previewToken)->exists()) {
                $this->addError('previewToken', 'The broadcast could not be queued. Please try again.');

                return;
            }
            session()->flash('status', 'Broadcast saved. The queue recovery scheduler will enqueue any recipients still waiting.');
            $this->reset('previewToken', 'previewNonce', 'previewCount');

            return;
        }
        $this->reset('previewToken', 'previewNonce', 'previewCount');
        session()->flash('status', 'WhatsApp broadcast queued. Accepted messages await delivery confirmation from Meta.');
    }

    public function saveContact(): void
    {
        $this->authorizeContacts();
        $this->validate(['contactName' => ['required', 'string', 'max:255'], 'contactPhone' => ['required', 'string', 'max:40'], 'consent' => ['boolean'], 'consentSource' => [$this->consent ? 'required' : 'nullable', 'string', 'max:500']]);
        $phone = SchoolWhatsapp::phone($this->contactPhone);
        if (! $phone) {
            $this->addError('contactPhone', 'Enter a valid international number, for example +256772123456.');

            return;
        }
        $contact = WhatsappContact::firstOrNew(['school_id' => Auth::user()->school_id, 'phone' => $phone]);
        $contact->name = $this->contactName;
        if ($this->consent) {
            $contact->consented_at = now();
            $contact->consented_by = Auth::id();
            $contact->consent_source = $this->consentSource;
            $contact->opted_out_at = null;
        } elseif ($contact->exists) {
            $contact->opted_out_at = now();
        }
        $contact->save();
        $this->reset('contactName', 'contactPhone', 'consentSource', 'consent', 'previewToken');
        session()->flash('status', 'Parent contact saved. Only opted-in contacts can receive broadcasts.');
    }

    public function revokeConsent(int $id): void
    {
        $this->authorizeContacts();
        WhatsappContact::where('school_id', Auth::user()->school_id)->findOrFail($id)->update(['opted_out_at' => now()]);
        $this->previewToken = '';
    }

    public function importContacts(): void
    {
        $this->authorizeContacts();
        $save = function ($record) {
            $phone = SchoolWhatsapp::phone($record->phone);
            if ($phone) {
                WhatsappContact::firstOrCreate(['school_id' => Auth::user()->school_id, 'phone' => $phone], ['name' => $record->name]);
            }
        };
        StudentGuardian::whereHas('student', fn ($q) => $q->where('school_id', Auth::user()->school_id))->orderBy('id')->chunkById(200, fn ($rows) => $rows->each($save));
        User::where('school_id', Auth::user()->school_id)->where('role', 'parent')->orderBy('id')->chunkById(200, fn ($rows) => $rows->each($save));
        session()->flash('status', 'Parent and guardian phone numbers imported. New contacts have no consent until you record it.');
    }

    public function viewDeliveryHistory(int $id): void
    {
        $this->authorizeSend();
        WhatsappBroadcast::where('school_id', Auth::user()->school_id)->findOrFail($id);
        $this->historyId = $id;
        $this->resetPage('deliveryPage');
    }

    public function render()
    {
        $this->authorizeSend();
        $template = $this->template();
        $history = WhatsappBroadcast::where('school_id', Auth::user()->school_id)->withCount('deliveries');
        foreach (['queued', 'sending', 'accepted', 'sent', 'delivered', 'read', 'failed', 'unknown', 'skipped'] as $status) {
            $history->withCount(['deliveries as '.$status.'_count' => fn ($q) => $q->where('status', $status)]);
        }

        return view('livewire.whatsapp-announcements', ['configuration' => $this->configuration(), 'template' => $template, 'messagePreview' => $template ? SchoolWhatsapp::preview($template['body'], $this->parameters).(filled($template['footer'] ?? '') ? "\n\n".$template['footer'] : '') : '', 'classes' => SchoolClass::where('school_id', Auth::user()->school_id)->orderBy('name')->get(), 'contacts' => WhatsappContact::where('school_id', Auth::user()->school_id)->when($this->search, fn ($q) => $q->where(fn ($s) => $s->where('name', 'like', '%'.$this->search.'%')->orWhere('phone', 'like', '%'.$this->search.'%')))->orderBy('name')->paginate(25), 'selectableContacts' => WhatsappContact::where('school_id', Auth::user()->school_id)->whereNotNull('consented_at')->whereNull('opted_out_at')->orderBy('name')->limit(500)->get(), 'history' => $history->latest()->limit(15)->get(), 'deliveryDetails' => $this->historyId ? WhatsappDelivery::with('contact')->where('whatsapp_broadcast_id', $this->historyId)->whereHas('broadcast',fn ($q) => $q->where('school_id',Auth::user()->school_id))->orderBy('id')->paginate(25,['*'],'deliveryPage') : null, 'canManageContacts' => Auth::user()->hasPermission('parents.manage'), 'pageTitle' => 'WhatsApp announcements']);
    }
}
