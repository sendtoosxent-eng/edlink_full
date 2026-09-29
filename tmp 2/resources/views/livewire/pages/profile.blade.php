<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.authed-blank')] class extends Component
{
    // Profile details
    public string $name = '';
    public string $email = '';

    // Password change
    public string $current_password = '';
    public string $new_password = '';
    public string $new_password_confirmation = '';

    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    public function updateProfile(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $emailChanged = $validated['email'] !== $user->email;

        $user->fill($validated);

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
            session()->flash('profile_status', 'Profile updated. Since you changed your email, please verify it again — we\'ve sent a new link.');
        } else {
            session()->flash('profile_status', 'Profile updated successfully.');
        }
    }

    public function updatePassword(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ], [], [
            'new_password' => 'new password',
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            $this->addError('current_password', 'Your current password is incorrect.');
            return;
        }

        $user->forceFill(['password' => Hash::make($validated['new_password'])])->save();

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);

        session()->flash('password_status', 'Password updated successfully.');
    }
}; ?>

<div class="space-y-8">
    <div>
        <h1 class="text-2xl font-semibold text-darken">Account settings</h1>
        <p class="text-gray-500 text-sm mt-1">Update your name, email, and password.</p>
    </div>

    {{-- Profile details --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h2 class="font-semibold text-darken mb-1">Profile information</h2>
        <p class="text-gray-500 text-sm mb-6">Your name and email address.</p>

        @if (session('profile_status'))
            <div class="bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3 mb-5">
                {{ session('profile_status') }}
            </div>
        @endif

        <form wire:submit="updateProfile" class="space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Full name</label>
                <input type="text" wire:model="name" required
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-yellow-400">
                @error('name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" wire:model="email" required
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-yellow-400">
                @error('email') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                <p class="text-xs text-gray-400 mt-1">Changing your email will require re-verifying it.</p>
            </div>

            <button type="submit" wire:loading.attr="disabled" wire:target="updateProfile"
                class="inline-flex items-center space-x-2 bg-darken text-white font-medium px-6 py-2.5 rounded-full hover:bg-opacity-90 transition disabled:opacity-60">
                <span wire:loading wire:target="updateProfile"><x-edlink-loader size="16" /></span>
                <span>Save changes</span>
            </button>
        </form>
    </div>

    {{-- Password --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h2 class="font-semibold text-darken mb-1">Change password</h2>
        <p class="text-gray-500 text-sm mb-6">Use a strong password you're not using elsewhere.</p>

        @if (session('password_status'))
            <div class="bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3 mb-5">
                {{ session('password_status') }}
            </div>
        @endif

        <form wire:submit="updatePassword" class="space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Current password</label>
                <input type="password" wire:model="current_password" required
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-yellow-400">
                @error('current_password') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">New password</label>
                <input type="password" wire:model="new_password" required
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-yellow-400">
                @error('new_password') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm new password</label>
                <input type="password" wire:model="new_password_confirmation" required
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-yellow-400">
            </div>

            <button type="submit" wire:loading.attr="disabled" wire:target="updatePassword"
                class="inline-flex items-center space-x-2 bg-yellow-500 text-darken font-semibold px-6 py-2.5 rounded-full hover:bg-yellow-400 transition disabled:opacity-60">
                <span wire:loading wire:target="updatePassword"><x-edlink-loader size="16" /></span>
                <span>Update password</span>
            </button>
        </form>
    </div>
</div>
