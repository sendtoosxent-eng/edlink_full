<?php

namespace App\Services;

use App\Models\SchoolWhatsappConfiguration;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class SchoolWhatsapp
{
    public static function phone(?string $phone): ?string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone ?? '');
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        if (preg_match('/^0[0-9]{9}$/', $digits)) {
            $digits = '256'.substr($digits, 1);
        }

        return preg_match('/^[1-9][0-9]{7,14}$/', $digits) ? $digits : null;
    }

    public function endpoint(string $id, string $resource = ''): string
    {
        $version = config('whatsapp.graph_version');
        if (! preg_match('/^v[0-9]+\.[0-9]+$/', $version) || ! ctype_digit($id)) {
            throw new \RuntimeException('Invalid WhatsApp API configuration.');
        }

        return 'https://graph.facebook.com/'.$version.'/'.$id.($resource ? '/'.$resource : '');
    }

    public function sync(SchoolWhatsappConfiguration $configuration): void
    {
        try {
            $phone = Http::withToken($configuration->access_token)->connectTimeout(5)->timeout(15)->get($this->endpoint($configuration->phone_number_id), ['fields' => 'display_phone_number,verified_name']);
            $numbers = Http::withToken($configuration->access_token)->connectTimeout(5)->timeout(15)->get($this->endpoint($configuration->business_account_id, 'phone_numbers'), ['fields' => 'id', 'limit' => 100]);
            $templates = Http::withToken($configuration->access_token)->connectTimeout(5)->timeout(15)->get($this->endpoint($configuration->business_account_id, 'message_templates'), ['fields' => 'name,status,language,components,category', 'limit' => 100]);
            if (! $phone->successful() || ! $numbers->successful() || ! $templates->successful() || ! collect($numbers->json('data', []))->contains(fn ($n) => (string) $n['id'] === (string) $configuration->phone_number_id)) {
                throw new \RuntimeException;
            }
            $approved = [];
            foreach ($templates->json('data', []) as $template) {
                if (($template['status'] ?? '') !== 'APPROVED' || ! in_array($template['category'] ?? '', ['UTILITY', 'MARKETING'])) {
                    continue;
                }
                $components = collect($template['components'] ?? []);
                // Pilot supports text-only templates with numeric BODY parameters.
                if ($components->contains(fn ($c) => in_array($c['type'], ['HEADER', 'BUTTONS', 'CAROUSEL']))) {
                    continue;
                }
                $body = $components->firstWhere('type', 'BODY')['text'] ?? '';
                preg_match_all('/\{\{([^}]+)\}\}/', $body, $matches);
                if (collect($matches[1])->contains(fn ($v) => ! ctype_digit($v))) {
                    continue;
                }
                $positions = array_unique(array_map('intval', $matches[1]));
                sort($positions);
                if ($positions && $positions !== range(1, count($positions))) {
                    continue;
                }
                $approved[] = ['name' => $template['name'], 'language' => $template['language'], 'body' => $body, 'parameters' => count($positions), 'category' => $template['category'], 'footer' => $components->firstWhere('type', 'FOOTER')['text'] ?? ''];
            }
            $configuration->update(['display_phone' => $phone->json('display_phone_number'), 'templates' => $approved, 'connected_at' => now()]);
        } catch (\Throwable) {
            throw ValidationException::withMessages(['connection' => 'Unable to verify the account. Check the account ID, phone number ID, token permissions and connection.']);
        }
    }

    public static function preview(string $body, array $parameters): string
    {
        return preg_replace_callback('/\{\{([0-9]+)\}\}/', fn ($m) => (string) ($parameters[(int) $m[1] - 1] ?? $m[0]), $body);
    }
}
