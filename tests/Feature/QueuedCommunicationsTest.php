<?php

use App\Jobs\DispatchSchoolAnnouncement;
use App\Jobs\SendAnnouncementSms;
use App\Livewire\Communications;
use App\Models\Announcement;
use App\Models\School;
use App\Models\User;
use App\Notifications\QueuedAnnouncement;
use App\Services\SchoolSmsSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('creates a whole-school in-app announcement and queues background delivery', function () {
    Queue::fake();
    $school = School::create(['name' => 'Broadcast School', 'slug' => 'broadcast-school']);
    $otherSchool = School::create(['name' => 'Other School', 'slug' => 'other-school']);
    $admin = User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);
    User::factory()->create(['school_id' => $school->id, 'role' => 'parent']);
    User::factory()->create(['school_id' => $otherSchool->id, 'role' => 'parent']);

    Livewire::actingAs($admin)->test(Communications::class)
        ->set('title', 'Public holiday tomorrow')
        ->set('message', 'The school will be closed tomorrow and reopen the following day.')
        ->set('sendEmail', true)
        ->call('send')
        ->assertHasNoErrors();

    $announcement = Announcement::where('school_id', $school->id)->firstOrFail();
    expect($announcement->recipient_count)->toBe(2)
        ->and($announcement->target_audience)->toBe('all')
        ->and($announcement->send_email)->toBeTrue();
    $this->assertDatabaseHas('school_notifications', [
        'school_id' => $school->id,
        'user_id' => null,
        'title' => 'Public holiday tomorrow',
    ]);
    $this->assertDatabaseMissing('school_notifications', ['school_id' => $otherSchool->id]);
    Queue::assertPushed(DispatchSchoolAnnouncement::class, fn ($job) => $job->announcementId === $announcement->id);
});

it('fans email and sms out only to users in the announcement school', function () {
    Notification::fake();
    Queue::fake([SendAnnouncementSms::class]);
    $school = School::create(['name' => 'Broadcast School', 'slug' => 'broadcast-school']);
    $otherSchool = School::create(['name' => 'Other School', 'slug' => 'other-school']);
    $first = User::factory()->create(['school_id' => $school->id, 'phone' => '+256700000001']);
    $second = User::factory()->create(['school_id' => $school->id, 'phone' => null]);
    $outsider = User::factory()->create(['school_id' => $otherSchool->id, 'phone' => '+256700000002']);
    $announcement = Announcement::create([
        'school_id' => $school->id,
        'created_by' => $first->id,
        'title' => 'Holiday',
        'message' => 'School is closed tomorrow.',
        'target_audience' => 'all',
        'send_email' => true,
        'send_sms' => true,
        'recipient_count' => 2,
        'sent_at' => now(),
    ]);

    (new DispatchSchoolAnnouncement($announcement->id))->handle();

    Notification::assertSentTo([$first, $second], QueuedAnnouncement::class, fn ($notification) => $notification instanceof ShouldQueue);
    Notification::assertNotSentTo($outsider, QueuedAnnouncement::class);
    Queue::assertPushed(SendAnnouncementSms::class, fn ($job) => $job->userId === $first->id && $job->announcementId === $announcement->id);
    Queue::assertNotPushed(SendAnnouncementSms::class, fn ($job) => $job->userId === $second->id || $job->userId === $outsider->id);
    expect($announcement->fresh()->delivery_status)->toBe('dispatched');
});

it('refuses sms delivery when the school gateway is unavailable', function () {
    Queue::fake();
    $school = School::create(['name' => 'Broadcast School', 'slug' => 'broadcast-school']);
    $admin = User::factory()->create(['school_id' => $school->id, 'role' => 'admin', 'phone' => '+256700000001']);

    Livewire::actingAs($admin)->test(Communications::class)
        ->set('title', 'Holiday')
        ->set('message', 'School is closed tomorrow.')
        ->set('sendSms', true)
        ->call('send')
        ->assertHasErrors(['sendSms']);

    $this->assertDatabaseCount('announcements', 0);
    Queue::assertNothingPushed();
});

it('sends custom gateway sms with the configured school credentials', function () {
    Http::fake(['https://sms.example.test/send' => Http::response(['accepted' => true])]);
    $school = School::create(['name' => 'SMS School', 'slug' => 'sms-school']);
    $school->smsConfiguration()->create([
        'enabled' => true,
        'provider' => 'custom',
        'api_key' => 'school-secret',
        'sender_id' => 'EDLINK',
        'endpoint' => 'https://sms.example.test/send',
    ]);

    app(SchoolSmsSender::class)->send($school, '+256 700 000 001', 'Holiday: School is closed tomorrow.');

    Http::assertSent(fn ($request) => $request->url() === 'https://sms.example.test/send'
        && $request->hasHeader('Authorization', 'Bearer school-secret')
        && $request['to'] === '+256700000001'
        && $request['sender_id'] === 'EDLINK'
        && $request['school_id'] === $school->id);
});

it('uses the Africa Talking sandbox endpoint in sandbox mode', function () {
    Http::fake(['https://api.sandbox.africastalking.com/version1/messaging' => Http::response(['SMSMessageData' => ['Recipients' => []]])]);
    $school = School::create(['name' => 'Sandbox School', 'slug' => 'sandbox-school']);
    $school->smsConfiguration()->create([
        'enabled' => true,
        'provider' => 'africastalking',
        'sandbox' => true,
        'api_username' => 'sandbox',
        'api_key' => 'sandbox-key',
        'sender_id' => '12345',
    ]);

    app(SchoolSmsSender::class)->send($school, '+256700000001', 'Sandbox test message');

    Http::assertSent(fn ($request) => $request->url() === 'https://api.sandbox.africastalking.com/version1/messaging'
        && $request->hasHeader('apiKey', 'sandbox-key')
        && $request['username'] === 'sandbox'
        && $request['to'] === '+256700000001');
    Http::assertSentCount(1);
});
