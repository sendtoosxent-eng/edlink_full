<?php

namespace App\Notifications;

use App\Support\MailIdentity;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class OtpCodeNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly string $code) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return MailIdentity::applySupport((new MailMessage)
            ->subject('Your Edlink login code')
            ->greeting('Hi '.$notifiable->name.',')
            ->line('We noticed a login attempt from a new device or location.')
            ->line('Your one-time verification code is:')
            ->line(new HtmlString('<h1 style="letter-spacing:6px;">'.$this->code.'</h1>'))
            ->line('This code expires in 10 minutes from now.')
            ->line('If this wasn\'t you, please change your password immediately.'));
    }
}
