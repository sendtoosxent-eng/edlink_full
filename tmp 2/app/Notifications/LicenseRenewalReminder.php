<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LicenseRenewalReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries=3;
    public int $timeout=20;

    public function __construct(public readonly string $schoolName,public readonly string $schoolNumber,public readonly int $daysRemaining) {}

    public function via(object $notifiable): array{return ['mail'];}

    public function toMail(object $notifiable): MailMessage
    {
        $timing=$this->daysRemaining<1?'has expired':($this->daysRemaining===1?'expires tomorrow':'expires in '.$this->daysRemaining.' days');
        return (new MailMessage)->subject('Edlink licence renewal reminder')->greeting('Hello '.$notifiable->name.',')->line($this->schoolName.' ('.$this->schoolNumber.') '.$timing.'.')->line('Renew early to avoid interruption to staff and parent access.')->action('Review licence',url('/settings/licence'));
    }
}
