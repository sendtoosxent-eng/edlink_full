<?php

namespace App\Livewire;

use App\Models\SchoolSetting;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ResultAccessSettings extends Component
{
    public string $feeClearance = 'disabled';

    public function mount(): void
    {
        $this->authorizeManagement();
        $this->feeClearance = SchoolSetting::where([
            'school_id' => Auth::user()->school_id,
            'key' => 'results_fee_clearance_required',
        ])->value('value') ?? 'disabled';
    }

    public function save(): void
    {
        $this->authorizeManagement();
        $this->validate(['feeClearance' => ['required', 'in:enabled,disabled']]);
        SchoolSetting::updateOrCreate(
            ['school_id' => Auth::user()->school_id, 'key' => 'results_fee_clearance_required'],
            ['value' => $this->feeClearance],
        );
        session()->flash('status', 'Result access rule saved.');
    }

    private function authorizeManagement(): void
    {
        abort_unless(Auth::user()->hasPermission('settings.manage'), 403);
    }

    public function render()
    {
        return view('livewire.result-access-settings', ['pageTitle' => 'Result Access']);
    }
}
