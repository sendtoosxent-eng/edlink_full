<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class AuditTrail extends Component
{
    use WithPagination;

    public string $search = '';
    public string $userId = '';
    public string $role = '';
    public string $event = '';
    public string $fromDate = '';
    public string $toDate = '';
    public ?int $selectedLogId = null;

    public function mount(): void
    {
        abort_unless(in_array(Auth::user()->role, ['admin', 'superadmin'], true), 403);
        AuditLog::record(Auth::user()->school_id, 'audit_trail.viewed', null, ['route' => 'settings.audit-trail']);
    }

    public function updated($property): void
    {
        if (in_array($property, ['search','userId','role','event','fromDate','toDate'], true)) $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search','userId','role','event','fromDate','toDate']);
        $this->resetPage();
    }

    public function showDetails(int $id): void
    {
        AuditLog::where('school_id', Auth::user()->school_id)->findOrFail($id);
        $this->selectedLogId = $id;
    }

    public function closeDetails(): void
    {
        $this->selectedLogId = null;
    }

    public function render()
    {
        $schoolId = Auth::user()->school_id;
        $query = AuditLog::with('user:id,name,email,role')->where('school_id', $schoolId)
            ->when($this->userId, fn ($q) => $q->where('user_id', $this->userId))
            ->when($this->role, fn ($q) => $q->whereHas('user', fn ($u) => $u->where('role', $this->role)))
            ->when($this->event, fn ($q) => $q->where('event', $this->event))
            ->when($this->fromDate, fn ($q) => $q->whereDate('created_at', '>=', $this->fromDate))
            ->when($this->toDate, fn ($q) => $q->whereDate('created_at', '<=', $this->toDate))
            ->when($this->search, function ($q): void {
                $term = '%'.$this->search.'%';
                $q->where(fn ($scope) => $scope->where('event','like',$term)->orWhere('metadata','like',$term)->orWhere('ip_address','like',$term)->orWhereHas('user',fn($u)=>$u->where('name','like',$term)->orWhere('email','like',$term)));
            });
        $today = now()->toDateString();
        $selectedLog = $this->selectedLogId ? AuditLog::with('user:id,name,email,role')->where('school_id',$schoolId)->find($this->selectedLogId) : null;
        return view('livewire.audit-trail', [
            'logs' => $query->latest()->paginate(40),
            'users' => User::where('school_id',$schoolId)->whereIn('role',['admin','superadmin','academic_admin','registrar','teacher','bursar'])->orderBy('name')->get(['id','name','role']),
            'events' => AuditLog::where('school_id',$schoolId)->distinct()->orderBy('event')->pluck('event'),
            'selectedLog' => $selectedLog,
            'todayCount' => AuditLog::where('school_id',$schoolId)->whereDate('created_at',$today)->count(),
            'actionCount' => AuditLog::where('school_id',$schoolId)->whereIn('event',['livewire.action','request.post','request.put','request.patch','request.delete'])->whereDate('created_at',$today)->count(),
            'activeUsers' => AuditLog::where('school_id',$schoolId)->whereDate('created_at',$today)->whereNotNull('user_id')->distinct()->count('user_id'),
            'pageTitle' => 'Audit Trail',
        ]);
    }
}