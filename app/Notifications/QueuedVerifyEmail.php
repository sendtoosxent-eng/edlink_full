<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class QueuedVerifyEmail extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 20;

    public function toMail($notifiable): MailMessage
    {
        $message = parent::toMail($notifiable);
        $schoolNumber = $notifiable->school?->school_number;

        if ($schoolNumber) {
            $message->line('Your Edlink school number is: '.$schoolNumber);
        }

        return $message;
    }
}
