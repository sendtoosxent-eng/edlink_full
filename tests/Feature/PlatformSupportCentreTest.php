<?php

use App\Mail\ContactMessageReplyMail;
use App\Models\ContactMessage;
use App\Models\ContactMessageReply;
use App\Models\PlatformAdmin;
use App\Models\PlatformAuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function authenticatedSupportOwner(): PlatformAdmin
{
    $admin=PlatformAdmin::create(['name'=>'Support Owner','email'=>'support-owner@edlink.test','password'=>'StrongPassword!123','role'=>'platform_owner','is_active'=>true,'totp_secret'=>'TESTSECRET','totp_confirmed_at'=>now(),'recovery_codes'=>[]]);
    test()->actingAs($admin,'platform')->withSession(['platform_mfa_passed'=>true,'platform_last_activity'=>now()->timestamp]);
    return $admin;
}

it('shows landing page messages and marks an opened message as read', function () {
    authenticatedSupportOwner();
    $message=ContactMessage::create(['name'=>'Jane Visitor','email'=>'jane@example.com','subject'=>'School demo','message'=>'Please arrange a demonstration.','type'=>'contact']);

    $this->get(route('platform.support.index'))->assertOk()->assertSee('Landing-page inbox')->assertSee('Jane Visitor')->assertSee('School demo');
    $this->get(route('platform.support.show',$message))->assertOk()->assertSee('Please arrange a demonstration.')->assertSee('Reply by email');
    expect($message->fresh()->status)->toBe('open')->and($message->fresh()->read_at)->not->toBeNull();
});

it('emails and stores a platform support reply', function () {
    Mail::fake(); $admin=authenticatedSupportOwner();
    $message=ContactMessage::create(['name'=>'John Visitor','email'=>'john@example.com','subject'=>'Pricing enquiry','message'=>'What package should our school use?','type'=>'contact']);

    $this->post(route('platform.support.reply',$message),['subject'=>'Re: Pricing enquiry','message'=>'Thank you for contacting Edlink. We recommend the Basic package.'])->assertRedirect();

    Mail::assertSent(ContactMessageReplyMail::class,fn($mail)=>$mail->hasTo('john@example.com'));
    expect(ContactMessageReply::where('contact_message_id',$message->id)->where('platform_admin_id',$admin->id)->where('delivery_status','sent')->exists())->toBeTrue()
        ->and($message->fresh()->status)->toBe('replied')
        ->and(PlatformAuditLog::where('event','platform.support.replied')->exists())->toBeTrue();
});