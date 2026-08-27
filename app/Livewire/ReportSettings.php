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
    public string $calculationMethod = 'single_exam';
    public string $missingAssessmentRule = 'incomplete';
    public array $assessmentWeights = [];
    public string $showPosition = 'enabled';
    public string $showFees = 'enabled';
    public string $showAttendance = 'enabled';
    public string $showPromotion = 'disabled';
    public string $showMarks = 'enabled';
    public string $showMaximum = 'enabled';
    public string $showPercentage = 'enabled';
    public string $showGrade = 'enabled';
    public string $showPoints = 'disabled';
    public string $showRemarks = 'enabled';
    public string $nextTermStarts = '';
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
            'calculationMethod' => 'required|in:single_exam,weighted,average,best_exam',
            'missingAssessmentRule' => 'required|in:incomplete,zero,redistribute',
            'assessmentWeights' => 'array|max:12',
            'assessmentWeights.*.name' => 'required_if:calculationMethod,weighted|nullable|string|max:100',
            'assessmentWeights.*.weight' => 'required_if:calculationMethod,weighted|nullable|numeric|min:0.01|max:100',
            'nextTermStarts' => 'nullable|date',
            'footer' => 'nullable|string|max:1000',
            'showMarks' => 'required|in:enabled,disabled',
            'showMaximum' => 'required|in:enabled,disabled',
            'showPercentage' => 'required|in:enabled,disabled',
            'showGrade' => 'required|in:enabled,disabled',
            'showPoints' => 'required|in:enabled,disabled',
            'showRemarks' => 'required|in:enabled,disabled',
        ]);

        if ($this->calculationMethod === 'weighted' && abs(collect($this->assessmentWeights)->sum(fn ($row) => (float) ($row['weight'] ?? 0)) - 100) > 0.01) {
            $this->addError('assessmentWeights', 'Assessment weights must add up to exactly 100%.');
            return;
        }

        $prefix = "report_{$this->stage}_";
        foreach ([
            'pass_mark' => $this->passMark,
            'best_subjects' => $this->bestSubjects,
            'calculation_method' => $this->calculationMethod,
            'missing_assessment_rule' => $this->missingAssessmentRule,
            'assessment_weights' => json_encode(array_values($this->assessmentWeights)),
            'show_position' => $this->showPosition,
            'show_fees' => $this->showFees,
            'show_attendance' => $this->showAttendance,
            'show_promotion' => $this->showPromotion,
            'show_marks' => $this->showMarks,
            'show_maximum' => $this->showMaximum,
            'show_percentage' => $this->showPercentage,
            'show_grade' => $this->showGrade,
            'show_points' => $this->showPoints,
            'show_remarks' => $this->showRemarks,
            'next_term_starts' => $this->nextTermStarts,
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
        $this->calculationMethod = $values[$prefix.'calculation_method'] ?? $defaults['calculation_method'];
        $this->missingAssessmentRule = $values[$prefix.'missing_assessment_rule'] ?? $defaults['missing_assessment_rule'];
        $this->assessmentWeights = json_decode($values[$prefix.'assessment_weights'] ?? $defaults['assessment_weights'], true) ?: [];
        $this->showPosition = $values[$prefix.'show_position'] ?? $defaults['show_position'];
        $this->showFees = $values[$prefix.'show_fees'] ?? $defaults['show_fees'];
        $this->showAttendance = $values[$prefix.'show_attendance'] ?? $defaults['show_attendance'];
        $this->showPromotion = $values[$prefix.'show_promotion'] ?? $defaults['show_promotion'];
        $this->showMarks = $values[$prefix.'show_marks'] ?? $defaults['show_marks'];
        $this->showMaximum = $values[$prefix.'show_maximum'] ?? $defaults['show_maximum'];
        $this->showPercentage = $values[$prefix.'show_percentage'] ?? $defaults['show_percentage'];
        $this->showGrade = $values[$prefix.'show_grade'] ?? $defaults['show_grade'];
        $this->showPoints = $values[$prefix.'show_points'] ?? $defaults['show_points'];
        $this->showRemarks = $values[$prefix.'show_remarks'] ?? $defaults['show_remarks'];
        $this->nextTermStarts = $values[$prefix.'next_term_starts'] ?? $defaults['next_term_starts'];
        $this->footer = $values[$prefix.'footer'] ?? $defaults['footer'];
    }

    public function addAssessmentWeight(): void
    {
        $this->assessmentWeights[] = ['name' => '', 'weight' => ''];
    }

    public function removeAssessmentWeight(int $index): void
    {
        unset($this->assessmentWeights[$index]);
        $this->assessmentWeights = array_values($this->assessmentWeights);
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
