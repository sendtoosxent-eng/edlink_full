<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\School;
use App\Support\SubscriptionPlans;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Licensing extends Component
{
    public string $schoolId = '';
    public string $license_plan = 'basic';
    public string $license_status = 'active';
    public string $license_started_at = '';
    public string $license_expires_at = '';
    public string $license_student_limit = '';
    public bool $is_demo = false;

    public function mount(): void
    {
        $this->schoolId = (string) Auth::user()->school_id;
        $this->loadSchool();
    }

    public function updatedSchoolId(): void { $this->loadSchool(); }

    public function loadSchool(): void
    {
        $school = $this->selectedSchool();
        $this->license_plan = SubscriptionPlans::valid((string) $school->license_plan) ? $school->license_plan : 'basic';
        $this->license_status = $school->license_status ?: ($school->is_demo ? 'trial' : 'active');
        $this->license_started_at = $school->license_started_at?->toDateString() ?? '';
        $this->license_expires_at = $school->license_expires_at?->toDateString() ?? '';
        $this->license_student_limit = (string) ($school->license_student_limit ?? '');
        $this->is_demo = (bool) $school->is_demo;
    }

    public function save(): void
    {
        abort_unless(Auth::user()->isSuperadmin(), 403);
        $this->validate(['license_plan' => 'required|in:basic,premium,enterprise', 'license_status' => 'required|in:active,trial,suspended,expired', 'license_started_at' => 'nullable|date', 'license_expires_at' => 'nullable|date|after_or_equal:license_started_at', 'license_student_limit' => 'nullable|integer|min:1']);
        $school = $this->selectedSchool();
        $school->update(['license_plan' => $this->license_plan, 'license_status' => $this->license_status, 'license_started_at' => $this->license_started_at ?: null, 'license_expires_at' => $this->license_expires_at ?: null, 'license_student_limit' => $this->license_student_limit ?: null, 'is_demo' => $this->is_demo]);
        AuditLog::record($school->id, 'licence.updated', $school, ['plan' => $school->license_plan, 'status' => $school->license_status]);
        session()->flash('status', 'Licence details updated.');
    }

    private function selectedSchool(): School
    {
        return Auth::user()->isSuperadmin() ? School::findOrFail($this->schoolId) : Auth::user()->school;
    }

    public function render()
    {
        return view('livewire.licensing', ['school' => $this->selectedSchool(), 'schools' => Auth::user()->isSuperadmin() ? School::orderBy('name')->get(['id', 'name', 'school_number']) : collect(), 'canManage' => Auth::user()->isSuperadmin(), 'pageTitle' => 'School Licence']);
    }
}
