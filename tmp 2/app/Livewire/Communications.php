<?php

namespace App\Livewire;

use App\Jobs\DispatchSchoolAnnouncement;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

#[Layout('layouts.app')]
class Communications extends Component
{
    public string $title = '';
    public string $message = '';
    public bool $sendEmail = false;
    public bool $sendSms = false;

    public function send(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'sendEmail' => ['boolean'],
            'sendSms' => ['boolean'],
        ]);

        $school = Auth::user()->school;
        $recipients = User::where('school_id', $school->id);
        $recipientCount = (clone $recipients)->count();

        if ($recipientCount === 0) {
            $this->addError('title', 'This school has no user accounts to notify.');
            return;
        }
        if ($this->sendEmail && ! (clone $recipients)->whereNotNull('email')->where('email', '!=', '')->exists()) {
            $this->addError('sendEmail', 'No school users have an email address.');
            return;
        }
        if ($this->sendSms && ! $school->smsConfiguration?->isReady()) {
            $this->addError('sendSms', 'SMS is not enabled or fully configured for this school.');
            return;
        }
        if ($this->sendSms && ! (clone $recipients)->whereNotNull('phone')->where('phone', '!=', '')->exists()) {
            $this->addError('sendSms', 'No school users have a phone number.');
            return;
        }

        try {
            $announcement = DB::transaction(function () use ($school, $recipientCount): Announcement {
                $announcement = Announcement::create([
                    'school_id' => $school->id,
                    'term_id' => $school->currentTerm()?->id,
                    'created_by' => Auth::id(),
                    'title' => $this->title,
                    'message' => $this->message,
                    'target_audience' => 'all',
                    'send_email' => $this->sendEmail,
                    'send_sms' => $this->sendSms,
                    'delivery_status' => ($this->sendEmail || $this->sendSms) ? 'queued' : 'in_app',
                    'recipient_count' => $recipientCount,
                    'sent_at' => now(),
                ]);

                DB::table('school_notifications')->insert([
                    'school_id' => $school->id,
                    'user_id' => null,
                    'title' => $announcement->title,
                    'message' => $announcement->message,
                    'type' => 'info',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return $announcement;
            });

            if ($announcement->send_email || $announcement->send_sms) {
                DispatchSchoolAnnouncement::dispatch($announcement->id)->afterCommit();
            }

            $channels = collect(['in-app'])
                ->when($announcement->send_email, fn ($items) => $items->push('email'))
                ->when($announcement->send_sms, fn ($items) => $items->push('SMS'))
                ->join(', ');

            $this->reset(['title', 'message', 'sendEmail', 'sendSms']);
            session()->flash('status', "Announcement sent in-app to {$recipientCount} users; {$channels} delivery is queued where selected.");
        } catch (Throwable $exception) {
            report($exception);
            session()->flash('error', 'Announcement could not be created. Nothing was queued; please try again.');
        }
    }

    public function render()
    {
        return view('livewire.communications', [
            'announcements' => Announcement::where('school_id', Auth::user()->school_id)->latest()->get(),
            'smsReady' => Auth::user()->school->smsConfiguration?->isReady() ?? false,
            'pageTitle' => 'Announcements',
        ]);
    }
}
