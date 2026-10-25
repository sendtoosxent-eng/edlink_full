<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Designation;
use App\Services\DefaultDesignationService;
use App\Support\DesignationPermissions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Designations extends Component
{
    public string $name = '';

    public string $description = '';

    public array $permissions = [];

    public ?int $editingId = null;

    public function mount(DefaultDesignationService $defaults): void
    {
        $this->authorizeAccess();
        $defaults->ensureFor(Auth::user()->school);
    }

    public function save(): void
    {
        $this->authorizeAccess();
        $this->name = trim($this->name);
        $this->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('designations', 'name')->where('school_id', Auth::user()->school_id)->ignore($this->editingId)],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'distinct', Rule::in([...DesignationPermissions::all(), ...array_keys(DesignationPermissions::groups())])],
        ]);
        $schoolId = Auth::user()->school_id;
        $designation = $this->editingId ? Designation::where('school_id', $schoolId)->findOrFail($this->editingId) : new Designation(['school_id' => $schoolId]);
        $designation->fill(['name' => $this->name, 'description' => $this->description ?: null, 'permissions' => array_values($this->permissions)])->save();
        AuditLog::record($schoolId, $this->editingId ? 'designation.updated' : 'designation.created', $designation, ['name' => $designation->name]);
        $this->resetForm();
        session()->flash('status', 'Designation saved. Staff assigned to it now share its access rights.');
    }

    public function edit(int $id): void
    {
        $this->authorizeAccess();
        $designation = Designation::where('school_id', Auth::user()->school_id)->findOrFail($id);
        $this->editingId = $designation->id;
        $this->name = $designation->name;
        $this->description = $designation->description ?? '';
        $this->permissions = $designation->permissions ?? [];
    }

    public function delete(int $id): void
    {
        $this->authorizeAccess();
        $designation = Designation::where('school_id', Auth::user()->school_id)->findOrFail($id);
        if ($designation->default_key) {
            session()->flash('error', 'Default designations can be edited but cannot be deleted.');

            return;
        }
        if ($designation->users()->exists()) {
            session()->flash('error', 'Reassign staff before deleting this designation.');

            return;
        }
        AuditLog::record($designation->school_id, 'designation.deleted', $designation, ['name' => $designation->name]);
        $designation->delete();
        session()->flash('status', 'Designation removed.');
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'description', 'permissions']);
    }

    private function authorizeAccess(): void
    {
        abort_unless(Auth::user()?->hasPermission('staff.designations'), 403);
    }

    public function render()
    {
        return view('livewire.designations', ['accessGroups' => DesignationPermissions::groups(), 'designations' => Designation::withCount('users')->where('school_id', Auth::user()->school_id)->orderBy('name')->get(), 'pageTitle' => 'Designations']);
    }
}
