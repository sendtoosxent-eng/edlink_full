<?php
namespace App\Mail;
use App\Models\ContactMessage;
use App\Models\ContactMessageReply;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Support\MailIdentity;
class ContactMessageReplyMail extends Mailable {
    use Queueable, SerializesModels;
    public function __construct(public ContactMessage $contactMessage, public ContactMessageReply $supportReply) {}
    public function envelope(): Envelope { return new Envelope(subject:$this->supportReply->subject, from: new \Illuminate\Mail\Mailables\Address(MailIdentity::supportAddress(), MailIdentity::supportName())); }
    public function headers(): \Illuminate\Mail\Mailables\Headers
    {
        return new \Illuminate\Mail\Mailables\Headers(
            replyTo: [new \Illuminate\Mail\Mailables\Address(MailIdentity::supportAddress(), MailIdentity::supportName())],
        );
    }
    public function content(): Content { return new Content(view:'emails.contact-message-reply'); }
}
