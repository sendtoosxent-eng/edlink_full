<?php

namespace App\Jobs;

use App\Models\School;
use App\Models\SchoolWhatsappConfiguration;
use App\Models\WhatsappDelivery;
use App\Services\SchoolWhatsapp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendWhatsappAnnouncement implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 0;

    public int $timeout = 30;

    public function __construct(public int $deliveryId, public int $schoolId) {}

    public function retryUntil(): \DateTimeInterface
    {
        return now()->addDay();
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping('whatsapp-school-'.$this->schoolId))->releaseAfter(3)->expireAfter(60), new RateLimited('school-whatsapp')];
    }

    public function handle(SchoolWhatsapp $api): void
    {
        $delivery = WhatsappDelivery::with(['broadcast', 'contact'])->find($this->deliveryId);
        if (! $delivery || (int) $delivery->broadcast->school_id !== $this->schoolId || $delivery->status !== 'queued') {
            return;
        }
        $school = School::find($this->schoolId);
        $config = SchoolWhatsappConfiguration::where('school_id', $this->schoolId)->first();
        if (! $school?->isLicenceUsable() || $school->isExpiredDemo() || ! $config?->isReady() || $config->phone_number_id !== $delivery->broadcast->phone_number_id || ! $delivery->contact->canReceive() || $delivery->phone !== $delivery->contact->phone) {
            $delivery->update(['status' => 'skipped', 'error_code' => 'recipient_or_connection_unavailable', 'status_at' => now()]);

            return;
        }
        $template = collect($config->templates ?? [])->first(fn ($t) => $t['name'] === $delivery->broadcast->template_name && $t['language'] === $delivery->broadcast->language && $t['body'] === $delivery->broadcast->body && ($t['footer'] ?? '') === ($delivery->broadcast->footer ?? ''));
        if (! $template) {
            $delivery->update(['status' => 'skipped', 'error_code' => 'template_changed', 'status_at' => now()]);

            return;
        }
        if (! WhatsappDelivery::whereKey($delivery->id)->where('status', 'queued')->update(['status' => 'sending', 'status_at' => now()])) {
            return;
        }
        try {
            $payload = ['messaging_product' => 'whatsapp', 'to' => $delivery->phone, 'type' => 'template', 'biz_opaque_callback_data' => 'edlink:'.$delivery->id, 'template' => ['name' => $delivery->broadcast->template_name, 'language' => ['code' => $delivery->broadcast->language]]];
            if ($delivery->broadcast->parameters) {
                $payload['template']['components'] = [['type' => 'body', 'parameters' => array_map(fn ($text) => ['type' => 'text', 'text' => $text], $delivery->broadcast->parameters)]];
            }
            $response = Http::withToken($config->access_token)->connectTimeout(5)->timeout(20)->post($api->endpoint($config->phone_number_id, 'messages'), $payload);
            if ($response->status() === 429) {
                WhatsappDelivery::whereKey($delivery->id)->where('status', 'sending')->update(['status' => 'queued', 'error_code' => 'rate_limited']);
                $this->release(60);

                return;
            }
            if ($response->successful() && filled($id = $response->json('messages.0.id'))) {
                WhatsappDelivery::whereKey($delivery->id)->where('status', 'sending')->update(['status' => 'accepted', 'message_id' => $id, 'error_code' => null, 'status_at' => now()]);
            } else {
                WhatsappDelivery::whereKey($delivery->id)->where('status', 'sending')->update(['status' => $response->serverError() ? 'unknown' : 'failed', 'error_code' => (string) ($response->json('error.code') ?? $response->status()), 'status_at' => now()]);
            }
        } catch (\Throwable) {
            // A timeout can occur after Meta accepted the message. Never resend blindly.
            WhatsappDelivery::whereKey($delivery->id)->where('status', 'sending')->update(['status' => 'unknown', 'error_code' => 'delivery_unconfirmed', 'status_at' => now()]);
        }
    }

    public function failed(?\Throwable $error): void
    {
        WhatsappDelivery::whereKey($this->deliveryId)->where('status', 'queued')->update(['status' => 'failed', 'error_code' => 'queue_exhausted', 'status_at' => now()]);
    }
}
