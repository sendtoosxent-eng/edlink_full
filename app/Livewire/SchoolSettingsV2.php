<?php

namespace App\Livewire;

use App\Models\SchoolSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class SchoolSettingsV2 extends SchoolSettings
{
    public function mount(): void
    {
        $this->authorizeManagement();
        parent::mount();
        $defaults = [
            'academic_year_rule' => 'Three terms per academic year',
            'promotion_rule' => 'automatic_average',
            'required_student_fields' => 'standard',
            'student_status_rules' => 'standard_lifecycle',
            'grading_scale' => 'stage_scales',
            'payment_methods' => 'cash,mobile_money,bank',
            'payment_receipt_email_enabled' => 'enabled',
            'payroll_approval_rule' => 'admin', 'payroll_period' => 'Monthly',
            'leave_types' => 'standard', 'leave_approval_rule' => 'admin',
            'otp_enabled' => 'enabled', 'audit_retention' => '12 months',
            'timezone' => 'Africa/Kampala', 'date_format' => 'd M Y', 'language' => 'English',
        ];
        foreach ($defaults as $key => $value) if (blank($this->settings[$key] ?? null)) $this->settings[$key] = $value;
    }

    public function save(): void
    {
        $this->authorizeManagement();
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'motto' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'principal_name' => ['nullable', 'string', 'max:255'],
            'badge' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $school = Auth::user()->school;
        $oldBadge = $school->badge_path;
        $newBadge = $this->badge ? $this->badge->store('school-badges', 'public') : $oldBadge;
        $school->update([
            'name' => trim($this->name), 'email' => trim($this->email) ?: null,
            'phone' => trim($this->phone) ?: null, 'address' => trim($this->address) ?: null,
            'motto' => trim($this->motto) ?: null, 'website' => trim($this->website) ?: null,
            'principal_name' => trim($this->principal_name) ?: null, 'badge_path' => $newBadge,
        ]);
        if ($this->badge && $oldBadge && $oldBadge !== $newBadge) Storage::disk('public')->delete($oldBadge);

        foreach ($this->keys as $key) {
            SchoolSetting::updateOrCreate(['school_id' => $school->id, 'key' => $key], ['value' => $this->settings[$key] ?? null]);
        }

        $this->reset('badge');
        Auth::user()->setRelation('school', $school->fresh());
        session()->flash('status', 'School profile and system settings saved.');
    }

    public function removeBadge(): void
    {
        $this->authorizeManagement();
        $school = Auth::user()->school;
        $oldBadge = $school->badge_path;
        $school->update(['badge_path' => null]);
        if ($oldBadge) Storage::disk('public')->delete($oldBadge);
        $this->reset('badge');
        Auth::user()->setRelation('school', $school->fresh());
        session()->flash('status', 'School badge removed.');
    }

    public function render()
    {
        $school = Auth::user()->school;
        $currentBadgeUrl = $school->badge_path && Storage::disk('public')->exists($school->badge_path)
            ? Storage::disk('public')->url($school->badge_path) : null;

        return view('livewire.school-settings', compact('currentBadgeUrl') + ['pageTitle' => 'System Settings']);
    }

    private function authorizeManagement(): void
    {
        abort_unless(Auth::user()->hasPermission('settings.manage'), 403);
    }
}
