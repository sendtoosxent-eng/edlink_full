<?php

use App\Models\School;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest-school-login')] class extends Component
{
    public string $school_number = '';

    public string $email = '';

    public function sendResetLink(): void
    {
        $this->validate(['school_number' => ['required', 'string'], 'email' => ['required', 'email']]);
        $school = School::where('school_number', strtoupper(trim($this->school_number)))->first();

        if ($school) {
            // Using the broker's complete flow enforces token throttling and
            // resolves the account using both its school and email address.
            Password::broker()->sendResetLink([
                'school_id' => $school->id,
                'email' => strtolower(trim($this->email)),
            ]);
        }

        session()->flash('status', 'If those details match an Edlink account, a reset link has been sent by email.');
    }
}; ?>

<div class="school-login password-recovery">
    <p class="login-eyebrow">Account recovery</p>
    <h1>Forgot your password?</h1>
    <p>Enter your school number and account email. We’ll email you a link to set a new password.</p>

    @if(session('status'))
        <div class="recovery-status" role="status">{{ session('status') }}</div>
    @endif

    <form wire:submit="sendResetLink" class="space-y-4">
        <div>
            <label for="recovery-school-number" class="block">School number</label>
            <div class="login-input-wrap">
                <svg class="login-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 10 9-7 9 7v11H3V10Zm6 11v-7h6v7M7 10h.01M17 10h.01"/></svg>
                <input id="recovery-school-number" name="school_number" wire:model="school_number" type="text" required autocomplete="organization" autocapitalize="characters" spellcheck="false" placeholder="e.g. EDL-4K9P2" class="uppercase" @error('school_number') aria-invalid="true" aria-describedby="recovery-school-error" @enderror>
            </div>
            @error('school_number')<span id="recovery-school-error" class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
        </div>
        <div>
            <label for="recovery-email" class="block">Email address</label>
            <div class="login-input-wrap">
                <svg class="login-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="m3 7 9 6 9-6"/></svg>
                <input id="recovery-email" name="email" wire:model="email" type="email" required autocomplete="email" autocapitalize="none" spellcheck="false" inputmode="email" placeholder="you@school.com" @error('email') aria-invalid="true" aria-describedby="recovery-email-error" @enderror>
            </div>
            @error('email')<span id="recovery-email-error" class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
        </div>
        <button type="submit" wire:loading.attr="disabled" wire:target="sendResetLink" class="flex w-full items-center justify-between gap-2">
            <span wire:loading.remove wire:target="sendResetLink">Send reset link</span><span wire:loading wire:target="sendResetLink">Sending link…</span>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 12h16m-6-6 6 6-6 6"/></svg>
        </button>
    </form>
    <p class="recovery-login-link">Remember your password? <a href="{{ route('login') }}" wire:navigate>Back to login</a></p>
</div>
