<?php

namespace App\Http\Controllers;

use App\Models\PlatformAuditLog;
use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PlatformSchoolSmsController extends Controller
{
    public function update(Request $request, School $school): RedirectResponse
    {
        $configuration = $school->smsConfiguration()->firstOrNew();
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'provider' => ['required', Rule::in(['africastalking', 'twilio', 'custom'])],
            'api_key' => ['nullable', 'string', 'max:1000'],
            'api_username' => ['nullable', 'string', 'max:150'],
            'sender_id' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9_-]+$/'],
            'endpoint' => ['nullable', 'url', 'max:500'],
            'webhook_secret' => ['nullable', 'string', 'max:1000'],
        ]);

        $enabled = $request->boolean('enabled');
        if ($enabled && blank($data['api_key'] ?? null) && blank($configuration->api_key)) {
            throw ValidationException::withMessages(['api_key' => 'An API key is required before SMS can be enabled.']);
        }
        if ($enabled && blank($data['sender_id'] ?? null)) {
            throw ValidationException::withMessages(['sender_id' => 'A sender ID is required before SMS can be enabled.']);
        }
        if ($enabled && $data['provider'] === 'africastalking' && blank($data['api_username'] ?? null)) {
            throw ValidationException::withMessages(['api_username' => 'The Africa’s Talking username is required.']);
        }
        if ($enabled && $data['provider'] === 'custom' && blank($data['endpoint'] ?? null)) {
            throw ValidationException::withMessages(['endpoint' => 'A gateway endpoint is required for a custom provider.']);
        }

        $changes = collect($data)->except(['api_key', 'webhook_secret'])->all();
        $changes['enabled'] = $enabled;
        if (filled($data['api_key'] ?? null)) $changes['api_key'] = $data['api_key'];
        if (filled($data['webhook_secret'] ?? null)) $changes['webhook_secret'] = $data['webhook_secret'];

        $configuration->fill($changes);
        $configuration->school_id = $school->id;
        $configuration->save();

        PlatformAuditLog::create([
            'platform_admin_id' => Auth::guard('platform')->id(),
            'event' => 'platform.school.sms_configuration.updated',
            'metadata' => [
                'school_id' => $school->id,
                'school' => $school->name,
                'enabled' => $configuration->enabled,
                'provider' => $configuration->provider,
                'sender_id' => $configuration->sender_id,
                'api_key_changed' => filled($data['api_key'] ?? null),
                'webhook_secret_changed' => filled($data['webhook_secret'] ?? null),
            ],
            'ip_address' => $request->ip(),
            'user_agent' => str($request->userAgent() ?? '')->limit(500)->toString() ?: null,
        ]);

        return back()->with('status', 'SMS configuration for '.$school->name.' was updated.');
    }
}
