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
            'certificate_title' => 'Certificate of Completion',
            'certificate_subtitle' => 'Awarded with distinction and pride',
            'certificate_intro' => 'This certificate is proudly presented to',
            'certificate_achievement' => 'For successfully completing the prescribed course of study with dedication and commitment.',
            'certificate_signatory_name' => $this->principal_name,
            'certificate_signatory_title' => 'Head Teacher',
            'certificate_number_prefix' => 'CERT',
            'recommendation_title' => 'Letter of Recommendation',
            'recommendation_intro' => 'To Whom It May Concern',
            'recommendation_body' => 'We are pleased to recommend this graduate, who completed their studies at our institution with commitment, discipline, and good standing.',
            'recommendation_closing' => 'We confidently recommend them for further study and suitable opportunities.',
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
            'settings.certificate_title' => ['required', 'string', 'max:100'],
            'settings.certificate_subtitle' => ['nullable', 'string', 'max:150'],
            'settings.certificate_intro' => ['required', 'string', 'max:180'],
            'settings.certificate_achievement' => ['required', 'string', 'max:500'],
            'settings.certificate_signatory_name' => ['nullable', 'string', 'max:100'],
            'settings.certificate_signatory_title' => ['nullable', 'string', 'max:100'],
            'settings.certificate_number_prefix' => ['required', 'string', 'max:20'],
            'settings.recommendation_title' => ['required', 'string', 'max:100'],
            'settings.recommendation_intro' => ['required', 'string', 'max:180'],
            'settings.recommendation_body' => ['required', 'string', 'max:1500'],
            'settings.recommendation_closing' => ['required', 'string', 'max:750'],
        ]);

        $school = Auth::user()->school;
        $oldBadge = $school->badge_path;
        $newBadge = $this->badge ? $this->badge->store('school-badges/'.$school->id, 'public') : $oldBadge;
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
        $currentBadgeUrl = $school->badgeUrl();

        return view('livewire.school-settings', compact('currentBadgeUrl') + ['pageTitle' => 'System Settings']);
    }

    private function authorizeManagement(): void
    {
        abort_unless(Auth::user()->hasPermission('settings.manage'), 403);
    }
}
