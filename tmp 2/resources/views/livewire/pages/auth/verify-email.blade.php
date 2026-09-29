<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public function resend(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirect(route('dashboard', absolute: false), navigate: false);
            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        session()->flash('status', 'verification-link-sent');
    }
}; ?>

<div class="text-center">
    <div class="w-14 h-14 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center mx-auto mb-5 text-2xl">✉</div>
    <h1 class="text-2xl font-semibold text-darken mb-2">Check your email</h1>
    <p class="text-gray-500 text-sm mb-6">
        We've sent a verification link to <span class="font-medium">{{ auth()->user()->email }}</span>.
        Click it to activate your demo school.
    </p>

    @if (session('new_school_number'))
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-6">
            <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Your school number</p>
            <p class="text-2xl font-bold text-darken tracking-widest">{{ session('new_school_number') }}</p>
            <p class="text-xs text-gray-400 mt-2">Save this — you'll need it every time you log in.</p>
        </div>
    @endif

    @if (session('status') === 'verification-link-sent')
        <p class="text-sm text-green-600 mb-4">A new verification link has been sent.</p>
    @endif

    <button wire:click="resend"
        class="w-full bg-darken text-white font-medium py-3 rounded-full hover:bg-opacity-90 transition">
        Resend verification email 
    </button>

    <form method="POST" action="{{ route('logout') }}" class="mt-4">
        @csrf
        <button type="submit" class="text-sm text-gray-400 underline">Log out</button>
    </form>
</div>
