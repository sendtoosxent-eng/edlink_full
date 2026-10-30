<?php

namespace App\Livewire;

use App\Models\SchoolWhatsappConfiguration;
use App\Services\SchoolWhatsapp;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class WhatsappSettings extends Component
{
    public string $businessAccountId = '';

    public string $phoneNumberId = '';

    public string $accessToken = '';

    public string $appSecret = '';

    public bool $enabled = false;

    public bool $embedded = false;

    private function authorizeSettings(): void
    {
        abort_unless(Auth::user()?->hasPermission('settings.manage'), 403);
    }

    public function mount(): void
    {
        $this->authorizeSettings();
        $config = SchoolWhatsappConfiguration::where('school_id', Auth::user()->school_id)->first();
        if ($config) {
            $this->businessAccountId = $config->business_account_id ?? '';
            $this->phoneNumberId = $config->phone_number_id ?? '';
            $this->enabled = $config->enabled;
        }
    }

    public function save(): void
    {
        $this->authorizeSettings();
        $this->validate(['businessAccountId' => ['required', 'regex:/^[0-9]{5,40}$/'], 'phoneNumberId' => ['required', 'regex:/^[0-9]{5,40}$/'], 'accessToken' => ['nullable', 'string', 'max:2000'], 'appSecret' => ['nullable', 'string', 'max:500'], 'enabled' => ['boolean']]);
        $config = SchoolWhatsappConfiguration::firstOrNew(['school_id' => Auth::user()->school_id]);
        if (SchoolWhatsappConfiguration::where('phone_number_id', $this->phoneNumberId)->where('school_id', '!=', Auth::user()->school_id)->exists()) {
            $this->addError('phoneNumberId', 'This sending number is already connected to another school.');

            return;
        }
        if ((! filled($config->access_token) && blank($this->accessToken)) || (! filled($config->app_secret) && blank($this->appSecret))) {
            $this->addError('connection', 'Provide an access token and Meta App Secret to connect.');

            return;
        }
        $changed = $config->business_account_id !== $this->businessAccountId || $config->phone_number_id !== $this->phoneNumberId || filled($this->accessToken) || filled($this->appSecret);
        if ($this->enabled && ($changed || ! $config->connected_at)) {
            $this->addError('connection', 'Save with sending disabled, verify the connection, then enable sending.');

            return;
        }
        $config->business_account_id = $this->businessAccountId;
        $config->phone_number_id = $this->phoneNumberId;
        $config->enabled = $this->enabled;
        if (! $config->exists) {
            $config->webhook_key = (string) Str::uuid();
            $config->verify_token = Str::random(48);
        }
        if (filled($this->accessToken)) {
            $config->access_token = $this->accessToken;
        }
        if (filled($this->appSecret)) {
            $config->app_secret = $this->appSecret;
        }
        if ($changed) {
            $config->connected_at = null;
            $config->templates = [];
        }
        $config->save();
        $this->reset('accessToken', 'appSecret');
        session()->flash('status', 'WhatsApp settings saved. Verify the connection after changing credentials.');
    }

    public function syncTemplates(SchoolWhatsapp $api): void
    {
        $this->authorizeSettings();
        $config = SchoolWhatsappConfiguration::where('school_id', Auth::user()->school_id)->first();
        if (! $config) {
            $this->addError('connection', 'Save the account credentials first.');

            return;
        }
        $api->sync($config);
        session()->flash('status', 'Connection verified and approved text templates refreshed. You can now enable sending.');
    }

    public function render()
    {
        $this->authorizeSettings();

        return view('livewire.whatsapp-settings', ['configuration' => SchoolWhatsappConfiguration::where('school_id', Auth::user()->school_id)->first(), 'pageTitle' => 'WhatsApp settings']);
    }
}
