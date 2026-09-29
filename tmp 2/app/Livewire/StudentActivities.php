<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class StudentActivities extends Component
{
    public string $tab = 'houses';
    public string $houseName = '';
    public string $houseColor = '#facc15';
    public string $housePatronId = '';
    public string $houseDescription = '';
    public string $clubName = '';
    public string $clubColor = '#3b82f6';
    public string $clubPatronId = '';
    public string $clubDescription = '';
    public string $clubMaximumMembers = '';
    public string $selectedClubId = '';
    public string $selectedHouseId = '';
    public array $selectedStudents = [];
    public string $studentSearch = '';
    public string $classFilter = '';

    public function mount(): void
    {
        abort_unless($this->isManager() || $this->isPatron(), 403);
        if (! $this->isManager()) {
            $this->tab = $this->patronClubIds()->isNotEmpty() ? 'clubs' : 'houses';
            $this->selectedClubId = (string) ($this->patronClubIds()->first() ?? '');
            $this->loadClubMembers();
        }
    }

    private function isManager(): bool
    {
        $user = Auth::user();
        return in_array($user->role, ['admin', 'superadmin'], true) || $user->hasPermission('students.activities');
    }

    private function patronClubIds()
    {
        return DB::table('student_clubs')->where('school_id', Auth::user()->school_id)->where('patron_user_id', Auth::id())->pluck('id');
    }

    private function isPatron(): bool
    {
        return DB::table('student_houses')->where('school_id', Auth::user()->school_id)->where('patron_user_id', Auth::id())->exists()
            || $this->patronClubIds()->isNotEmpty();
    }

    private function authorizeManager(): void
    {
        abort_unless($this->isManager(), 403);
    }

    public function createHouse(): void
    {
        $this->authorizeManager();
        $data = $this->validate([
            'houseName' => ['required', 'string', 'max:100'],
            'houseColor' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'housePatronId' => ['nullable', 'integer', 'exists:users,id'],
            'houseDescription' => ['nullable', 'string', 'max:1000'],
        ]);
        $schoolId = Auth::user()->school_id;
        if (DB::table('student_houses')->where('school_id', $schoolId)->whereRaw('LOWER(name) = ?', [strtolower($data['houseName'])])->exists()) {
            $this->addError('houseName', 'A house with this name already exists.'); return;
        }
        $patronId = $this->validStaffId($data['housePatronId'] ?: null);
        DB::table('student_houses')->insert(['school_id'=>$schoolId,'name'=>$data['houseName'],'color'=>$data['houseColor'],'patron_user_id'=>$patronId,'description'=>$data['houseDescription'] ?: null,'created_at'=>now(),'updated_at'=>now()]);
        $this->rebalanceHouses();
        AuditLog::record($schoolId, 'student_house.created', null, ['name'=>$data['houseName'],'patron_user_id'=>$patronId]);
        $this->reset(['houseName','housePatronId','houseDescription']); $this->houseColor = '#facc15';
        session()->flash('status', 'House created and unassigned students distributed automatically.');
    }

    public function rebalanceHouses(): void
    {
        $this->authorizeManager();
        $schoolId = Auth::user()->school_id;
        $houses = DB::table('student_houses')->where('school_id', $schoolId)->orderBy('id')->pluck('id');
        if ($houses->isEmpty()) { session()->flash('error', 'Create at least one house first.'); return; }
        $students = Student::where('school_id', $schoolId)->where('status', 'active')->orderBy('school_class_id')->orderBy('gender')->orderBy('id')->pluck('id');
        DB::transaction(function () use ($schoolId, $houses, $students): void {
            DB::table('student_house_memberships')->where('school_id', $schoolId)->delete();
            foreach ($students as $index => $studentId) DB::table('student_house_memberships')->insert(['school_id'=>$schoolId,'student_house_id'=>$houses[$index % $houses->count()],'student_id'=>$studentId,'allocation_method'=>'automatic','assigned_by'=>Auth::id(),'created_at'=>now(),'updated_at'=>now()]);
        });
        AuditLog::record($schoolId, 'student_houses.rebalanced', null, ['students'=>$students->count(),'houses'=>$houses->count()]);
        session()->flash('status', $students->count().' active students were balanced across '.$houses->count().' houses.');
    }

    private function allocateUnassignedStudents(): void
    {
        $schoolId = Auth::user()->school_id;
        $houses = DB::table('student_houses')->where('school_id', $schoolId)->get(['id']);
        if ($houses->isEmpty()) return;
        $counts = DB::table('student_house_memberships')->where('school_id', $schoolId)->selectRaw('student_house_id, COUNT(*) total')->groupBy('student_house_id')->pluck('total','student_house_id');
        $students = Student::where('school_id', $schoolId)->where('status', 'active')->whereNotIn('id', DB::table('student_house_memberships')->where('school_id',$schoolId)->select('student_id'))->orderBy('id')->pluck('id');
        foreach ($students as $studentId) {
            $houseId = $houses->sortBy(fn ($house) => (int) ($counts[$house->id] ?? 0))->first()->id;
            DB::table('student_house_memberships')->insert(['school_id'=>$schoolId,'student_house_id'=>$houseId,'student_id'=>$studentId,'allocation_method'=>'automatic','assigned_by'=>Auth::id(),'created_at'=>now(),'updated_at'=>now()]);
            $counts[$houseId] = (int) ($counts[$houseId] ?? 0) + 1;
        }
    }

    public function createClub(): void
    {
        $this->authorizeManager();
        $data = $this->validate([
            'clubName' => ['required','string','max:100'], 'clubColor' => ['required','regex:/^#[0-9a-fA-F]{6}$/'],
            'clubPatronId' => ['nullable','integer','exists:users,id'], 'clubDescription' => ['nullable','string','max:1000'],
            'clubMaximumMembers' => ['nullable','integer','min:1','max:10000'],
        ]);
        $schoolId = Auth::user()->school_id;
        if (DB::table('student_clubs')->where('school_id',$schoolId)->whereRaw('LOWER(name) = ?', [strtolower($data['clubName'])])->exists()) { $this->addError('clubName','A club with this name already exists.'); return; }
        $patronId = $this->validStaffId($data['clubPatronId'] ?: null);
        $id = DB::table('student_clubs')->insertGetId(['school_id'=>$schoolId,'name'=>$data['clubName'],'color'=>$data['clubColor'],'patron_user_id'=>$patronId,'description'=>$data['clubDescription'] ?: null,'maximum_members'=>$data['clubMaximumMembers'] ?: null,'created_at'=>now(),'updated_at'=>now()]);
        AuditLog::record($schoolId, 'student_club.created', null, ['club_id'=>$id,'name'=>$data['clubName'],'patron_user_id'=>$patronId]);
        $this->selectedClubId=(string)$id; $this->selectedStudents=[]; $this->reset(['clubName','clubPatronId','clubDescription','clubMaximumMembers']); $this->clubColor='#3b82f6';
        session()->flash('status','Club created. You can now assign students.');
    }

    private function validStaffId(?int $id): ?int
    {
        if (! $id) return null;
        $valid = User::where('school_id',Auth::user()->school_id)->where('id',$id)->where('employment_status','active')->whereIn('role',['teacher','academic_admin','admin'])->exists();
        abort_unless($valid, 422); return $id;
    }

    public function updatedSelectedClubId(): void { $this->loadClubMembers(); }

    public function selectHouse(int $houseId): void
    {
        $allowed = DB::table('student_houses')->where('school_id', Auth::user()->school_id)->whereKey($houseId)
            ->when(! $this->isManager(), fn ($query) => $query->where('patron_user_id', Auth::id()))->exists();
        abort_unless($allowed, 403);
        $this->selectedHouseId = (string) $houseId;
    }

    private function loadClubMembers(): void
    {
        if (! $this->selectedClubId) { $this->selectedStudents=[]; return; }
        $club = $this->authorizedClub();
        $this->selectedStudents = DB::table('student_club_memberships')->where('student_club_id',$club->id)->pluck('student_id')->map(fn($id)=>(string)$id)->all();
    }

    private function authorizedClub(): object
    {
        $club = DB::table('student_clubs')->where('school_id',Auth::user()->school_id)->find((int)$this->selectedClubId);
        abort_unless($club && ($this->isManager() || (int)$club->patron_user_id === Auth::id()), 403);
        return $club;
    }

    public function saveClubMembers(): void
    {
        $club = $this->authorizedClub();
        $validIds = Student::where('school_id',Auth::user()->school_id)->where('status','active')->whereIn('id',array_map('intval',$this->selectedStudents))->pluck('id');
        if ($club->maximum_members && $validIds->count() > $club->maximum_members) { $this->addError('selectedStudents','This club allows a maximum of '.$club->maximum_members.' members.'); return; }
        DB::transaction(function () use ($club,$validIds): void {
            DB::table('student_club_memberships')->where('student_club_id',$club->id)->delete();
            foreach ($validIds as $studentId) DB::table('student_club_memberships')->insert(['school_id'=>Auth::user()->school_id,'student_club_id'=>$club->id,'student_id'=>$studentId,'assigned_by'=>Auth::id(),'created_at'=>now(),'updated_at'=>now()]);
        });
        AuditLog::record(Auth::user()->school_id,'student_club.members_updated',null,['club_id'=>$club->id,'members'=>$validIds->count()]);
        session()->flash('status',$validIds->count().' students assigned to '.$club->name.'.');
    }

    public function render()
    {
        $schoolId=Auth::user()->school_id; $manager=$this->isManager();
        $houseQuery=DB::table('student_houses as h')->leftJoin('users as u','u.id','=','h.patron_user_id')->leftJoin('student_house_memberships as m','m.student_house_id','=','h.id')->where('h.school_id',$schoolId)->selectRaw('h.id,h.name,h.color,h.description,h.patron_user_id,u.name patron_name,COUNT(m.id) members_count')->groupBy('h.id','h.name','h.color','h.description','h.patron_user_id','u.name')->orderBy('h.name');
        if (!$manager) $houseQuery->where('h.patron_user_id',Auth::id());
        $clubQuery=DB::table('student_clubs as c')->leftJoin('users as u','u.id','=','c.patron_user_id')->leftJoin('student_club_memberships as m','m.student_club_id','=','c.id')->where('c.school_id',$schoolId)->selectRaw('c.id,c.name,c.color,c.description,c.maximum_members,c.patron_user_id,u.name patron_name,COUNT(m.id) members_count')->groupBy('c.id','c.name','c.color','c.description','c.maximum_members','c.patron_user_id','u.name')->orderBy('c.name');
        if (!$manager) $clubQuery->where('c.patron_user_id',Auth::id());
        $clubs=$clubQuery->get();
        $houses=$houseQuery->get();
        if (!$this->selectedHouseId && $houses->isNotEmpty()) $this->selectedHouseId=(string)$houses->first()->id;
        if (!$this->selectedClubId && $clubs->isNotEmpty()) { $this->selectedClubId=(string)$clubs->first()->id; $this->loadClubMembers(); }
        $students=Student::with('schoolClass:id,name')->where('school_id',$schoolId)->where('status','active')->when($this->studentSearch,fn($q)=>$q->where(fn($s)=>$s->where('name','like','%'.$this->studentSearch.'%')->orWhere('admission_no','like','%'.$this->studentSearch.'%')))->when($this->classFilter,fn($q)=>$q->where('school_class_id',$this->classFilter))->orderBy('name')->get(['id','name','admission_no','school_class_id','gender']);
        $houseMembers = $this->selectedHouseId ? Student::query()
            ->join('student_house_memberships as membership', 'membership.student_id', '=', 'students.id')
            ->leftJoin('school_classes', 'school_classes.id', '=', 'students.school_class_id')
            ->where('membership.school_id', $schoolId)->where('membership.student_house_id', $this->selectedHouseId)
            ->orderBy('students.name')->get(['students.id','students.name','students.admission_no','students.gender','school_classes.name as class_name']) : collect();
        $clubMembers = $this->selectedClubId ? Student::query()
            ->join('student_club_memberships as membership', 'membership.student_id', '=', 'students.id')
            ->leftJoin('school_classes', 'school_classes.id', '=', 'students.school_class_id')
            ->where('membership.school_id', $schoolId)->where('membership.student_club_id', $this->selectedClubId)
            ->orderBy('students.name')->get(['students.id','students.name','students.admission_no','students.gender','school_classes.name as class_name']) : collect();
        return view('livewire.student-activities',['houses'=>$houses,'clubs'=>$clubs,'students'=>$students,'houseMembers'=>$houseMembers,'clubMembers'=>$clubMembers,'staff'=>User::where('school_id',$schoolId)->where('employment_status','active')->whereIn('role',['teacher','academic_admin','admin'])->orderBy('name')->get(['id','name','job_title']),'classes'=>Auth::user()->school->classes()->orderBy('name')->get(['id','name']),'isManager'=>$manager,'pageTitle'=>'Houses & Clubs']);
    }
}
