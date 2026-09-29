<?php

use App\Models\School;
use App\Models\User;
use App\Notifications\LicenseRenewalReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('queues one daily renewal reminder for an expiring school', function () {
    Notification::fake();
    $school=School::create(['name'=>'Renewal School','slug'=>'renewal-school','license_status'=>'active','license_expires_at'=>now()->addDays(5)]);
    $admin=User::factory()->create(['school_id'=>$school->id,'role'=>'admin']);

    $this->artisan('edlink:renewal-reminders')->assertSuccessful();
    $this->artisan('edlink:renewal-reminders')->assertSuccessful();

    Notification::assertSentToTimes($admin,LicenseRenewalReminder::class,1);
    $this->assertDatabaseCount('school_notifications',1);
});
