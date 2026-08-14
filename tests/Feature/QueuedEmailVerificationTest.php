<?php

use App\Models\School;
use App\Models\User;
use App\Notifications\QueuedVerifyEmail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('queues email verification when a client registration event is fired', function () {
    Notification::fake();
    $school=School::create(['name'=>'Queued Mail School','slug'=>'queued-mail-school']);
    $user=User::factory()->unverified()->create(['school_id'=>$school->id,'role'=>'admin']);

    event(new Registered($user));

    Notification::assertSentTo($user,QueuedVerifyEmail::class,function($notification){
        expect($notification)->toBeInstanceOf(ShouldQueue::class)
            ->and($notification->timeout)->toBe(20)
            ->and($notification->tries)->toBe(3)
            ->and($notification->connection)->toBe('sync');
        return true;
    });
});

it('includes the school number in the verification email', function () {
    $school=School::create(['name'=>'Numbered School','slug'=>'numbered-school']);
    $user=User::factory()->unverified()->create(['school_id'=>$school->id,'role'=>'admin']);
    $mail=(new QueuedVerifyEmail)->toMail($user);
    expect(collect($mail->introLines)->merge($mail->outroLines)->implode(' '))->toContain($school->school_number);
});
