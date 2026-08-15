<?php

namespace App\Livewire;

use App\Models\SchoolSetting;
use App\Services\SchoolAcademicSetup;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ReportSettings extends Component
{
    public string $stage = '';
    public string $passMark = '50';
    public string $bestSubjects = '8';
    public string $showPosition = 'enabled';
    public string $showFees = 'enabled';
    public string $showAttendance = 'enabled';
    public string $showPromotion = 'disabled';
    public string $footer = '';

    public function mount(): void
    {
        $this->authorizeManagement();
        $this->stage = SchoolAcademicSetup::stagesFor(Auth::user()->school)[0];
        $this->loadStage();
    }

    public function updatedStage(): void
    {
        $this->loadStage();
    }

    public function save(): void
    {
        $this->authorizeManagement();
        $this->validate([
            'stage' => 'required|in:primary,lower_secondary,advanced_level,kindergarten',
            'passMark' => 'required|numeric|min:0|max:100',
            'bestSubjects' => 'required|integer|min:1|max:20',
            'footer' => 'nullable|string|max:1000',
        ]);

        $prefix = "report_{$this->stage}_";
        foreach ([
            'pass_mark' => $this->passMark,
            'best_subjects' => $this->bestSubjects,
            'show_position' => $this->showPosition,
            'show_fees' => $this->showFees,
            'show_attendance' => $this->showAttendance,
            'show_promotion' => $this->showPromotion,
            'footer' => $this->footer,
        ] as $key => $value) {
            SchoolSetting::updateOrCreate(
                ['school_id' => Auth::user()->school_id, 'key' => $prefix.$key],
                ['value' => $value],
            );
        }

        session()->flash('status', 'Report settings saved for '.str($this->stage)->replace('_', ' ')->title().'.');
    }

    private function loadStage(): void
    {
        $values = SchoolSetting::where('school_id', Auth::user()->school_id)->pluck('value', 'key');
        $prefix = "report_{$this->stage}_";
        $defaults = SchoolAcademicSetup::defaultReportSettings($this->stage);
        $this->passMark = $values[$prefix.'pass_mark'] ?? (string) $defaults['pass_mark'];
        $this->bestSubjects = $values[$prefix.'best_subjects'] ?? (string) $defaults['best_subjects'];
        $this->showPosition = $values[$prefix.'show_position'] ?? $defaults['show_position'];
        $this->showFees = $values[$prefix.'show_fees'] ?? $defaults['show_fees'];
        $this->showAttendance = $values[$prefix.'show_attendance'] ?? $defaults['show_attendance'];
        $this->showPromotion = $values[$prefix.'show_promotion'] ?? $defaults['show_promotion'];
        $this->footer = $values[$prefix.'footer'] ?? $defaults['footer'];
    }

    private function authorizeManagement(): void
    {
        abort_unless(Auth::user()->hasPermission('settings.manage'), 403);
    }

    public function render()
    {
        return view('livewire.report-settings', [
            'stages' => SchoolAcademicSetup::stagesFor(Auth::user()->school),
            'pageTitle' => 'Report Settings',
        ]);
    }
}
