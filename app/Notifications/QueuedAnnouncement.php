<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Support\MailIdentity;

class QueuedAnnouncement extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 20;

    public function __construct(
        public readonly string $schoolName,
        public readonly ?string $schoolEmail,
        public readonly string $title,
        public readonly string $message,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = MailIdentity::applySupport((new MailMessage)
            ->subject($this->schoolName.': '.$this->title)
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->message)
            ->line('This announcement was sent through Edlink by '.$this->schoolName.'.'));

        return $this->schoolEmail ? $message->replyTo($this->schoolEmail, $this->schoolName) : $message;
    }
}
