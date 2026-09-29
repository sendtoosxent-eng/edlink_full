<?php

namespace App\Services;

use App\Models\School;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SchoolSmsSender
{
    public function send(School $school, string $phone, string $message): void
    {
        $configuration = $school->smsConfiguration;
        if (! $configuration?->isReady()) {
            throw new RuntimeException('SMS is not configured for '.$school->name.'.');
        }

        $phone = preg_replace('/[\s()-]+/', '', trim($phone));
        if (! preg_match('/^\+?[0-9]{9,15}$/', $phone)) {
            throw new RuntimeException('The recipient phone number is invalid.');
        }

        match ($configuration->provider) {
            'africastalking' => $this->africasTalking($configuration, $phone, $message),
            'twilio' => $this->twilio($configuration, $phone, $message),
            'custom' => $this->custom($configuration, $school, $phone, $message),
            default => throw new RuntimeException('The configured SMS provider is not supported.'),
        };
    }

    private function africasTalking(object $configuration, string $phone, string $message): void
    {
        $endpoint = $configuration->sandbox
            ? 'https://api.sandbox.africastalking.com/version1/messaging'
            : 'https://api.africastalking.com/version1/messaging';

        Http::asForm()->withHeaders(['apiKey' => $configuration->api_key])
            ->timeout(15)->retry(2, 250)
            ->post($endpoint, [
                'username' => $configuration->api_username,
                'to' => $phone,
                'message' => $message,
                'from' => $configuration->sender_id,
            ])->throw();
    }

    private function twilio(object $configuration, string $phone, string $message): void
    {
        Http::asForm()->withBasicAuth($configuration->api_username, $configuration->api_key)
            ->timeout(15)->retry(2, 250)
            ->post('https://api.twilio.com/2010-04-01/Accounts/'.$configuration->api_username.'/Messages.json', [
                'To' => $phone,
                'From' => $configuration->sender_id,
                'Body' => $message,
            ])->throw();
    }

    private function custom(object $configuration, School $school, string $phone, string $message): void
    {
        $request = Http::acceptJson()->timeout(15)->retry(2, 250);
        if (filled($configuration->api_key)) $request = $request->withToken($configuration->api_key);

        $request->post($configuration->endpoint, [
            'to' => $phone,
            'message' => $message,
            'sender_id' => $configuration->sender_id,
            'school_id' => $school->id,
        ])->throw();
    }
}
