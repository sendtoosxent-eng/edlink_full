<?php

use App\Models\School;
use App\Models\User;
use App\Notifications\QueuedResetPassword;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

it('queues a school-scoped password reset email', function () {
    Notification::fake();
    $school=School::create(['name'=>'Recovery School','slug'=>'recovery-school']);
    $user=User::factory()->create(['school_id'=>$school->id,'email'=>'admin@recovery.test']);

    Volt::test('pages.auth.forgot-password')
        ->set('school_number',$school->school_number)
        ->set('email',$user->email)
        ->call('sendResetLink')
        ->assertHasNoErrors();

    Notification::assertSentTo($user,QueuedResetPassword::class,fn($notification)=>$notification instanceof ShouldQueue);
});

it('does not reveal whether password recovery details exist', function () {
    Notification::fake();
    Volt::test('pages.auth.forgot-password')
        ->set('school_number','EDL-NONE')
        ->set('email','unknown@example.test')
        ->call('sendResetLink')
        ->assertHasNoErrors()
        ->assertSee('If those details match');
});
