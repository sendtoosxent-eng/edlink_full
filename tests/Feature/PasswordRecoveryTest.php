<?php

use App\Models\School;
use App\Models\User;
use App\Notifications\QueuedResetPassword;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

function passwordRecoverySchool(string $slug): School
{
    return School::create([
        'name'=>'Recovery School',
        'slug'=>$slug,
        'status'=>'active',
        'is_demo'=>false,
        'license_status'=>'active',
        'license_expires_at'=>now()->addYear(),
    ]);
}

it('queues a school-scoped password reset email', function () {
    Notification::fake();
    $school=passwordRecoverySchool('recovery-school');
    $user=User::factory()->create(['school_id'=>$school->id,'email'=>'admin@recovery.test']);

    Volt::test('pages.auth.forgot-password')
        ->set('school_number',$school->school_number)
        ->set('email',$user->email)
        ->call('sendResetLink')
        ->assertHasNoErrors();

    Notification::assertSentTo($user,QueuedResetPassword::class,fn($notification)=>$notification instanceof ShouldQueue && $notification->connection === 'sync');
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

it('emails a school-scoped link, resets the password once, and accepts the new login', function () {
    Notification::fake();
    $school=passwordRecoverySchool('complete-recovery-school');
    $user=User::factory()->create([
        'school_id'=>$school->id,
        'email'=>'login@recovery.test',
        'password'=>Hash::make('OldPassword123!'),
        'email_verified_at'=>now(),
        'employment_status'=>'active',
    ]);

    Volt::test('pages.auth.forgot-password')
        ->set('school_number',$school->school_number)
        ->set('email',$user->email)
        ->call('sendResetLink')
        ->assertHasNoErrors();

    $token=null;
    Notification::assertSentTo($user,QueuedResetPassword::class,function($notification)use($user,$school,&$token){
        $token=$notification->token;
        $url=$notification->toMail($user)->actionUrl;
        expect($url)->toContain('/reset-password/'.$token)
            ->and(urldecode($url))->toContain('email='.$user->email)
            ->and(urldecode($url))->toContain('school_number='.$school->school_number);
        return true;
    });

    Volt::test('pages.auth.reset-password',['token'=>$token])
        ->set('school_number',$school->school_number)
        ->set('email',$user->email)
        ->set('password','NewPassword123!')
        ->set('password_confirmation','NewPassword123!')
        ->call('resetPassword')
        ->assertHasNoErrors()
        ->assertRedirect(route('login',absolute:false));

    expect(Hash::check('NewPassword123!',$user->fresh()->password))->toBeTrue()
        ->and(Hash::check('OldPassword123!',$user->fresh()->password))->toBeFalse();

    Volt::test('pages.auth.reset-password',['token'=>$token])
        ->set('school_number',$school->school_number)
        ->set('email',$user->email)
        ->set('password','AnotherPassword123!')
        ->set('password_confirmation','AnotherPassword123!')
        ->call('resetPassword')
        ->assertHasErrors(['email']);

    Volt::test('pages.auth.login')
        ->set('school_number',$school->school_number)
        ->set('email',$user->email)
        ->set('password','NewPassword123!')
        ->call('login')
        ->assertHasNoErrors();
    $this->assertAuthenticatedAs($user->fresh());
});

it('does not allow a reset token to cross schools that share an email address', function () {
    Notification::fake();
    $firstSchool=passwordRecoverySchool('first-recovery-school');
    $secondSchool=passwordRecoverySchool('second-recovery-school');
    $email='shared@recovery.test';
    $firstUser=User::factory()->create(['school_id'=>$firstSchool->id,'email'=>$email,'password'=>Hash::make('FirstPassword123!')]);
    $secondUser=User::factory()->create(['school_id'=>$secondSchool->id,'email'=>$email,'password'=>Hash::make('SecondPassword123!')]);

    Volt::test('pages.auth.forgot-password')
        ->set('school_number',$firstSchool->school_number)
        ->set('email',$email)
        ->call('sendResetLink');

    $token=null;
    Notification::assertSentTo($firstUser,QueuedResetPassword::class,function($notification)use(&$token){$token=$notification->token;return true;});

    Volt::test('pages.auth.reset-password',['token'=>$token])
        ->set('school_number',$secondSchool->school_number)
        ->set('email',$email)
        ->set('password','CrossTenantPassword123!')
        ->set('password_confirmation','CrossTenantPassword123!')
        ->call('resetPassword')
        ->assertHasErrors(['email']);

    expect(Hash::check('SecondPassword123!',$secondUser->fresh()->password))->toBeTrue();

    Volt::test('pages.auth.reset-password',['token'=>$token])
        ->set('school_number',$firstSchool->school_number)
        ->set('email',$email)
        ->set('password','FirstNewPassword123!')
        ->set('password_confirmation','FirstNewPassword123!')
        ->call('resetPassword')
        ->assertHasNoErrors();

    expect(Hash::check('FirstNewPassword123!',$firstUser->fresh()->password))->toBeTrue();
});

it('throttles repeated password reset emails without revealing account details', function () {
    Notification::fake();
    $school=passwordRecoverySchool('throttled-recovery-school');
    $user=User::factory()->create(['school_id'=>$school->id,'email'=>'throttle@recovery.test']);

    foreach(range(1,2)as$attempt){
        Volt::test('pages.auth.forgot-password')
            ->set('school_number',$school->school_number)
            ->set('email',$user->email)
            ->call('sendResetLink')
            ->assertHasNoErrors()
            ->assertSee('If those details match');
    }

    Notification::assertSentToTimes($user,QueuedResetPassword::class,1);
});
