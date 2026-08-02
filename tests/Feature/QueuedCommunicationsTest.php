<?php

use App\Livewire\Communications;
use App\Models\School;
use App\Models\User;
use App\Notifications\QueuedAnnouncement;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('queues school announcements without waiting for smtp', function () {
    Notification::fake();
    $school=School::create(['name'=>'Broadcast School','slug'=>'broadcast-school']);
    $admin=User::factory()->create(['school_id'=>$school->id,'role'=>'admin']);
    $parent=User::factory()->create(['school_id'=>$school->id,'role'=>'parent']);

    Livewire::actingAs($admin)->test(Communications::class)
        ->set('audience','parents')
        ->set('title','Term closes Friday')
        ->set('message','Please collect learners by midday.')
        ->call('send')
        ->assertHasNoErrors();

    Notification::assertSentTo($parent,QueuedAnnouncement::class,function($notification){
        return $notification instanceof ShouldQueue && $notification->tries===3;
    });
    $this->assertDatabaseHas('announcements',['school_id'=>$school->id,'recipient_count'=>1]);
});
