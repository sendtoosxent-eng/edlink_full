<?php

namespace App\Livewire;

use App\Services\PublicImageStorage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProfileModal extends Component
{
    use WithFileUploads;

    public bool $isOpen = false;

    // Profile details
    public string $name = '';

    public string $email = '';

    public $photo = null; // temporary uploaded file

    // Password change
    public string $current_password = '';

    public string $new_password = '';

    public string $new_password_confirmation = '';

    #[On('open-profile-modal')]
    public function open(): void
    {
        $this->resetValidation();
        $this->reset(['current_password', 'new_password', 'new_password_confirmation', 'photo']);

        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
    }

    public function updateProfile(PublicImageStorage $images): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->where('school_id', $user->school_id)->ignore($user->id)],
            'photo' => ['nullable', 'image', 'max:2048'], // 2MB max
        ]);

        $emailChanged = $validated['email'] !== $user->email;

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if ($this->photo) {
            $oldPath = $user->avatar_path;
            $user->avatar_path = $images->store($this->photo, 'avatars/'.$user->school_id);
            $images->deleteReplacement($oldPath, $user->avatar_path);
        }

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->photo = null;

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
            session()->flash('profile_status', 'Profile updated. Since you changed your email, please verify it again — we\'ve sent a new link.');
        } else {
            session()->flash('profile_status', 'Profile updated successfully.');
        }

        $this->dispatch('profile-updated');
    }

    public function updatePassword(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            $this->addError('current_password', 'Your current password is incorrect.');

            return;
        }

        $user->forceFill(['password' => Hash::make($validated['new_password'])])->save();

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);

        session()->flash('password_status', 'Password updated successfully.');
    }

    public function render()
    {
        return view('livewire.profile-modal');
    }
}
