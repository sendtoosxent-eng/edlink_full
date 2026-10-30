<?php

namespace App\Http\Controllers;

use App\Models\SchoolWhatsappConfiguration;
use App\Models\WhatsappContact;
use App\Models\WhatsappDelivery;
use App\Services\SchoolWhatsapp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WhatsappWebhookController extends Controller
{
    public function __invoke(Request $request, string $key)
    {
        $config = SchoolWhatsappConfiguration::where('webhook_key', $key)->firstOrFail();
        if ($request->isMethod('get')) {
            abort_unless($request->query('hub_mode') === 'subscribe' && hash_equals($config->verify_token, (string) $request->query('hub_verify_token')), 403);

            return response((string) $request->query('hub_challenge'), 200)->header('Content-Type', 'text/plain');
        }
        abort_if(strlen($request->getContent()) > 1048576, 413);
        $signature = 'sha256='.hash_hmac('sha256', $request->getContent(), $config->app_secret ?? '');
        abort_unless(filled($config->app_secret) && hash_equals($signature, (string) $request->header('X-Hub-Signature-256')), 403);
        foreach ($request->json('entry', []) as $entry) {
            if ((string) ($entry['id'] ?? '') !== $config->business_account_id) {
                continue;
            }
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];
                if ((string) ($value['metadata']['phone_number_id'] ?? '') !== $config->phone_number_id) {
                    continue;
                }
                foreach ($value['statuses'] ?? [] as $status) {
                    if (! in_array($status['status'] ?? '', ['sent', 'delivered', 'read', 'failed']) || blank($status['id'] ?? null)) {
                        continue;
                    }
                    DB::transaction(function () use ($config, $status) {
                        $query = WhatsappDelivery::whereHas('broadcast', fn ($q) => $q->where('school_id', $config->school_id)->where('phone_number_id', $config->phone_number_id));
                        $delivery = (clone $query)->where('message_id', $status['id'])->lockForUpdate()->first();
                        if (! $delivery && preg_match('/^edlink:([0-9]+)$/', $status['biz_opaque_callback_data'] ?? '', $match)) {
                            $delivery = $query->whereKey($match[1])->whereIn('status', ['sending', 'unknown'])->lockForUpdate()->first();
                        }
                        if (! $delivery || (isset($status['recipient_id']) && SchoolWhatsapp::phone($status['recipient_id']) !== $delivery->phone)) {
                            return;
                        }
                        $ranks = ['queued' => 0, 'sending' => 0, 'unknown' => 0, 'accepted' => 0, 'failed' => 0, 'sent' => 1, 'delivered' => 2, 'read' => 3];
                        if (($ranks[$status['status']] ?? 0) < ($ranks[$delivery->status] ?? 0)) {
                            return;
                        }
                        if ($delivery->status === 'failed' && $status['status'] === 'sent') {
                            return;
                        }
                        $delivery->update(['message_id' => $status['id'], 'status' => $status['status'], 'error_code' => $status['status'] === 'failed' ? (string) ($status['errors'][0]['code'] ?? 'provider_rejected') : null, 'status_at' => now()]);
                    });
                }
                foreach ($value['messages'] ?? [] as $message) {
                    $text = strtoupper(trim($message['text']['body'] ?? ''));
                    if (! in_array($text, ['STOP', 'UNSUBSCRIBE', 'OPT OUT', 'CANCEL'])) {
                        continue;
                    }
                    $phone = SchoolWhatsapp::phone($message['from'] ?? '');
                    if ($phone) {
                        WhatsappContact::where('school_id', $config->school_id)->where('phone', $phone)->update(['opted_out_at' => now()]);
                    }
                }
            }
        }

        return response()->json(['received' => true]);
    }
}
