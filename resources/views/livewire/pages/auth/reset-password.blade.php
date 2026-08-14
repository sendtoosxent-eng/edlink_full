<?php

use App\Models\School;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $token='';
    public string $school_number='';
    public string $email='';
    public string $password='';
    public string $password_confirmation='';

    public function mount(string $token): void
    {
        $this->token=$token;
        $this->school_number=(string)request('school_number');
        $this->email=(string)request('email');
    }

    public function resetPassword(): void
    {
        $this->validate(['school_number'=>['required','string'],'email'=>['required','email'],'password'=>['required','string','min:8','confirmed']]);
        $school=School::where('school_number',strtoupper(trim($this->school_number)))->first();
        if (!$school) {$this->addError('email','This password reset link is invalid or has expired.');return;}
        $status=Password::broker()->reset(['email'=>strtolower(trim($this->email)),'school_id'=>$school->id,'password'=>$this->password,'password_confirmation'=>$this->password_confirmation,'token'=>$this->token],function(User $user,string $password){$user->forceFill(['password'=>Hash::make($password),'remember_token'=>Str::random(60)])->save();event(new PasswordReset($user));});
        if ($status!==Password::PASSWORD_RESET) {$this->addError('email','This password reset link is invalid or has expired.');return;}
        session()->flash('status','Your password was reset. You can now log in.');
        $this->redirect(route('login',absolute:false),navigate:true);
    }
}; ?>

<div>
    <h1 class='text-2xl font-bold text-darken'>Choose a new password</h1>
    <form wire:submit='resetPassword' class='mt-6 space-y-4'>
        <div><label class='text-sm font-medium'>School number</label><input wire:model='school_number' required autocomplete='organization' class='mt-1 w-full rounded-lg border-gray-300 uppercase'>@error('school_number')<span class='text-xs text-red-600'>{{ $message }}</span>@enderror</div>
        <div><label class='text-sm font-medium'>Email address</label><input wire:model='email' type='email' required autocomplete='email' class='mt-1 w-full rounded-lg border-gray-300'>@error('email')<span class='text-xs text-red-600'>{{ $message }}</span>@enderror</div>
        <div><label class='text-sm font-medium'>New password</label><input wire:model='password' type='password' required autocomplete='new-password' class='mt-1 w-full rounded-lg border-gray-300'>@error('password')<span class='text-xs text-red-600'>{{ $message }}</span>@enderror</div>
        <div><label class='text-sm font-medium'>Confirm password</label><input wire:model='password_confirmation' type='password' required autocomplete='new-password' class='mt-1 w-full rounded-lg border-gray-300'></div>
        <button class='w-full rounded-full bg-darken py-3 font-medium text-white'>Reset password</button>
    </form>
</div>
