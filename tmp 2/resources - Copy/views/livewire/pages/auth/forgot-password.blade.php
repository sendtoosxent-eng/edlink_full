<?php

use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $school_number='';
    public string $email='';

    public function sendResetLink(): void
    {
        $this->validate(['school_number'=>['required','string'],'email'=>['required','email']]);
        $school=School::where('school_number',strtoupper(trim($this->school_number)))->first();
        $user=$school?User::where('school_id',$school->id)->where('email',strtolower(trim($this->email)))->first():null;

        if ($user) {
            $user->sendPasswordResetNotification(Password::broker()->createToken($user));
        }

        session()->flash('status','If those details match an Edlink account, a reset link has been queued for delivery.');
    }
}; ?>

<div>
    <h1 class='text-2xl font-bold text-darken'>Reset your password</h1>
    <p class='mt-2 text-sm text-gray-500'>Enter your school number and account email. For security, Edlink always returns the same response.</p>
    @if(session('status'))<div class='mt-5 rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800'>{{ session('status') }}</div>@endif
    <form wire:submit='sendResetLink' class='mt-6 space-y-4'>
        <div><label class='text-sm font-medium'>School number</label><input wire:model='school_number' required class='mt-1 w-full rounded-lg border-gray-300 uppercase' placeholder='EDL-4K9P2'>@error('school_number')<span class='text-xs text-red-600'>{{ $message }}</span>@enderror</div>
        <div><label class='text-sm font-medium'>Email address</label><input wire:model='email' type='email' required class='mt-1 w-full rounded-lg border-gray-300'>@error('email')<span class='text-xs text-red-600'>{{ $message }}</span>@enderror</div>
        <button class='w-full rounded-full bg-darken py-3 font-medium text-white' wire:loading.attr='disabled'>Send reset link</button>
    </form>
    <a href='{{ route('login') }}' class='mt-5 block text-center text-sm text-yellow-700'>Back to login</a>
</div>
