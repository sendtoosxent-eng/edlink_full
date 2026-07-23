<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Designation;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Designations extends Component
{
    public string $name = '';
    public string $description = '';
    public array $permissions = [];
    public ?int $editingId = null;

    public function save(): void
    {
        $this->validate(['name' => ['required', 'string', 'max:100'], 'description' => ['nullable', 'string', 'max:500'], 'permissions' => ['array']]);
        $schoolId = Auth::user()->school_id;
        $designation = $this->editingId ? Designation::where('school_id', $schoolId)->findOrFail($this->editingId) : new Designation(['school_id' => $schoolId]);
        $designation->fill(['name' => $this->name, 'description' => $this->description ?: null, 'permissions' => array_values($this->permissions)])->save();
        AuditLog::record($schoolId, $this->editingId ? 'designation.updated' : 'designation.created', $designation, ['name' => $designation->name]);
        $this->resetForm();
        session()->flash('status', 'Designation saved. Staff assigned to it now share its access rights.');
    }

    public function edit(int $id): void
    {
        $designation = Designation::where('school_id', Auth::user()->school_id)->findOrFail($id);
        $this->editingId = $designation->id; $this->name = $designation->name; $this->description = $designation->description ?? ''; $this->permissions = $designation->permissions ?? [];
    }

    public function delete(int $id): void
    {
        $designation = Designation::where('school_id', Auth::user()->school_id)->findOrFail($id);
        if ($designation->users()->exists()) { session()->flash('error', 'Reassign staff before deleting this designation.'); return; }
        AuditLog::record($designation->school_id, 'designation.deleted', $designation, ['name' => $designation->name]);
        $designation->delete();
        session()->flash('status', 'Designation removed. Assigned staff keep their accounts but no longer have designation restrictions.');
    }

    public function resetForm(): void { $this->reset(['editingId', 'name', 'description', 'permissions']); }

    public function render()
    {
        return view('livewire.designations', ['accessGroups' => \App\Support\DesignationPermissions::groups(), 'designations' => Designation::withCount('users')->where('school_id', Auth::user()->school_id)->orderBy('name')->get(), 'pageTitle' => 'Designations']);
    }
}
