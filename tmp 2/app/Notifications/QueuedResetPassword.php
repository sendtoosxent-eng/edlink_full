<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class QueuedResetPassword extends ResetPassword implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 20;

    protected function resetUrl($notifiable): string
    {
        return url(route('password.reset', [
            'token'=>$this->token,
            'email'=>$notifiable->getEmailForPasswordReset(),
            'school_number'=>$notifiable->school?->school_number,
        ], false));
    }
}
