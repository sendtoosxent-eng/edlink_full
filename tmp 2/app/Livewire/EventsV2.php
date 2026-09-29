<?php

namespace App\Livewire;

use App\Models\SchoolEvent;
use App\Models\Term;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class EventsV2 extends Events
{
    public function render()
    {
        $school = Auth::user()->school;

        return view('livewire.events-v2', [
            'terms' => Term::where('school_id', $school->id)->latest('year')->get(),
            'events' => SchoolEvent::with('term')->where('school_id', $school->id)
                ->when($this->termId, fn ($query) => $query->where('term_id', $this->termId))
                ->orderBy('event_date')->get(),
            'pageTitle' => 'Events',
        ]);
    }
}
