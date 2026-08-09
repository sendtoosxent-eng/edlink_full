<?php

namespace App\Support;

use App\Models\School;
use Illuminate\Notifications\Messages\MailMessage;

final class MailIdentity
{
    public static function supportAddress(): string
    {
        return (string) config('edlink.mail.support_address', 'support@edlink.space');
    }

    public static function supportName(): string
    {
        return (string) config('edlink.mail.support_name', 'Edlink Support');
    }

    public static function applySupport(MailMessage $message): MailMessage
    {
        return $message->from(self::supportAddress(), self::supportName());
    }

    public static function applySchoolReplyTo(MailMessage $message, ?School $school): MailMessage
    {
        $message = self::applySupport($message);

        if ($school?->email) {
            $message->replyTo($school->email, $school->name);
        }

        return $message;
    }
}
