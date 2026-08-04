<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class BulkTermReportsV3 extends BulkTermReports
{
    public function render()
    {
        $view = parent::render();
        return view('livewire.bulk-term-reports-v3', $view->getData());
    }
}
