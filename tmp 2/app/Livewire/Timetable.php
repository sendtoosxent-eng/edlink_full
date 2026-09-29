<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\SchoolClass;
use App\Models\Stream;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Timetable extends Component
{
    public string $classId = '';
    public string $streamId = '';
    public string $day = 'Monday';
    public string $startsAt = '08:00';
    public string $endsAt = '08:40';
    public string $subjectId = '';
    public string $teacherId = '';
    public string $label = '';
    public ?int $editingId = null;

    public array $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

    public function mount(): void
    {
        $this->authorizeTimetable();
        $this->classId = (string) SchoolClass::where('school_id', Auth::user()->school_id)->orderBy('sort_order')->orderBy('name')->value('id');
    }

    public function updatedClassId(): void { $this->streamId = ''; }

    public function saveSlot(): void
    {
        $this->authorizeTimetable();
        $school = Auth::user()->school;
        $term = $school->currentTerm();
        if (! $term || ! $term->isOpen()) { session()->flash('error', 'Timetable slots can only be changed during an open current term.'); return; }

        $data = $this->validate([
            'classId' => ['required','integer'], 'streamId' => ['nullable','integer'],
            'day' => ['required','in:'.implode(',', $this->days)],
            'startsAt' => ['required','date_format:H:i'], 'endsAt' => ['required','date_format:H:i','after:startsAt'],
            'subjectId' => ['nullable','integer'], 'teacherId' => ['nullable','integer'], 'label' => ['nullable','string','max:100'],
        ]);

        $class = SchoolClass::where('school_id',$school->id)->find($data['classId']);
        if (! $class) throw ValidationException::withMessages(['classId'=>'Select a class belonging to this school.']);
        if ($data['streamId'] && ! Stream::where('school_id',$school->id)->where('school_class_id',$class->id)->whereKey($data['streamId'])->exists()) throw ValidationException::withMessages(['streamId'=>'Select a stream belonging to this class.']);
        if ($data['subjectId'] && ! Subject::where('school_id',$school->id)->whereKey($data['subjectId'])->exists()) throw ValidationException::withMessages(['subjectId'=>'Select a subject belonging to this school.']);
        if ($data['teacherId'] && ! User::where('school_id',$school->id)->where('employment_status','active')->whereKey($data['teacherId'])->exists()) throw ValidationException::withMessages(['teacherId'=>'Select an active staff member from this school.']);
        if (! $data['subjectId'] && blank($data['label'])) throw ValidationException::withMessages(['label'=>'Choose a subject or enter a label such as Break or Assembly.']);

        $overlap = fn ($query) => $query->where('timetable_slots.starts_at','<',$data['endsAt'])->where('timetable_slots.ends_at','>',$data['startsAt'])->when($this->editingId,fn($q)=>$q->where('timetable_slots.id','!=',$this->editingId));
        $base = DB::table('timetable_slots')->where('timetable_slots.school_id',$school->id)->where('timetable_slots.term_id',$term->id)->where('timetable_slots.day_of_week',$data['day']);
        $classSchedule = $overlap(clone $base)->where('timetable_slots.school_class_id', $class->id);
        if ($data['streamId']) {
            $classSchedule->where(fn ($query) => $query->whereNull('timetable_slots.stream_id')->orWhere('timetable_slots.stream_id', $data['streamId']));
        }
        $classConflict = $classSchedule->exists();
        if ($classConflict) throw ValidationException::withMessages(['startsAt'=>'This class or stream already has an activity during that time.']);
        if ($data['teacherId']) {
            $teacherConflict = $overlap(clone $base)
                ->leftJoin('school_classes', 'school_classes.id', '=', 'timetable_slots.school_class_id')
                ->leftJoin('streams', 'streams.id', '=', 'timetable_slots.stream_id')
                ->leftJoin('subjects', 'subjects.id', '=', 'timetable_slots.subject_id')
                ->where('timetable_slots.user_id', $data['teacherId'])
                ->first([
                    'timetable_slots.starts_at', 'timetable_slots.ends_at', 'timetable_slots.label',
                    'school_classes.name as class_name', 'streams.name as stream_name', 'subjects.name as subject_name',
                ]);
            if ($teacherConflict) {
                $activity = $teacherConflict->subject_name ?: $teacherConflict->label ?: 'an activity';
                $location = $teacherConflict->class_name.($teacherConflict->stream_name ? ' · '.$teacherConflict->stream_name : '');
                throw ValidationException::withMessages([
                    'teacherId' => "Conflict: this teacher already has {$activity} with {$location} on {$data['day']}, "
                        .substr($teacherConflict->starts_at, 0, 5).'–'.substr($teacherConflict->ends_at, 0, 5).'.',
                ]);
            }
        }

        $values = ['school_id'=>$school->id,'term_id'=>$term->id,'school_class_id'=>$class->id,'stream_id'=>$data['streamId'] ?: null,'subject_id'=>$data['subjectId'] ?: null,'user_id'=>$data['teacherId'] ?: null,'day_of_week'=>$data['day'],'starts_at'=>$data['startsAt'],'ends_at'=>$data['endsAt'],'label'=>filled($data['label']) ? trim($data['label']) : null,'updated_at'=>now()];
        if ($this->editingId) {
            $changed = DB::table('timetable_slots')->where('id',$this->editingId)->where('school_id',$school->id)->where('term_id',$term->id)->update($values);
            abort_unless($changed !== false, 404); $event = 'timetable.slot.updated';
        } else {
            $values['created_at'] = now(); $this->editingId = (int) DB::table('timetable_slots')->insertGetId($values); $event = 'timetable.slot.created';
        }
        AuditLog::record($school->id,$event,null,['slot_id'=>$this->editingId,'class'=>$class->name,'day'=>$data['day'],'starts_at'=>$data['startsAt']]);
        session()->flash('status','Timetable slot saved successfully.');
        $this->resetForm(false);
    }

    public function editSlot(int $id): void
    {
        $this->authorizeTimetable();
        $term = Auth::user()->school->currentTerm();
        $slot = DB::table('timetable_slots')->where('id',$id)->where('school_id',Auth::user()->school_id)->when($term,fn($q)=>$q->where('term_id',$term->id))->first();
        abort_unless($slot,404);
        $this->editingId=$slot->id; $this->classId=(string)$slot->school_class_id; $this->streamId=(string)($slot->stream_id ?? ''); $this->day=$slot->day_of_week; $this->startsAt=substr($slot->starts_at,0,5); $this->endsAt=substr($slot->ends_at,0,5); $this->subjectId=(string)($slot->subject_id ?? ''); $this->teacherId=(string)($slot->user_id ?? ''); $this->label=(string)($slot->label ?? '');
    }

    public function cancelEdit(): void { $this->resetForm(); }

    public function deleteSlot(int $id): void
    {
        $this->authorizeTimetable();
        $school=Auth::user()->school; $term=$school->currentTerm();
        if(!$term?->isOpen()){session()->flash('error','Only an open current term can be edited.');return;}
        $deleted=DB::table('timetable_slots')->where('id',$id)->where('school_id',$school->id)->where('term_id',$term->id)->delete();
        if($deleted){AuditLog::record($school->id,'timetable.slot.deleted',null,['slot_id'=>$id]);session()->flash('status','Timetable slot removed.');}
        if($this->editingId===$id)$this->resetForm();
    }

    private function resetForm(bool $keepClass=true): void
    {
        $class=$this->classId; $this->reset(['streamId','subjectId','teacherId','label','editingId']);
        if($keepClass)$this->classId=$class; $this->day='Monday'; $this->startsAt='08:00'; $this->endsAt='08:40';
    }

    private function authorizeTimetable(): void { abort_unless(Auth::user()?->hasPermission('academics.timetable'),403); }

    public function render()
    {
        $school=Auth::user()->school; $term=$school->currentTerm();
        $classes=SchoolClass::where('school_id',$school->id)->with('streams')->orderBy('sort_order')->orderBy('name')->get();
        $slots=DB::table('timetable_slots')->leftJoin('subjects','subjects.id','=','timetable_slots.subject_id')->leftJoin('users','users.id','=','timetable_slots.user_id')->leftJoin('streams','streams.id','=','timetable_slots.stream_id')->where('timetable_slots.school_id',$school->id)->when($term,fn($q)=>$q->where('timetable_slots.term_id',$term->id))->when($this->classId,fn($q)=>$q->where('timetable_slots.school_class_id',$this->classId))->orderByRaw("CASE day_of_week WHEN 'Monday' THEN 1 WHEN 'Tuesday' THEN 2 WHEN 'Wednesday' THEN 3 WHEN 'Thursday' THEN 4 WHEN 'Friday' THEN 5 WHEN 'Saturday' THEN 6 ELSE 7 END")->orderBy('starts_at')->get(['timetable_slots.*','subjects.name as subject','users.name as teacher','streams.name as stream']);
        return view('livewire.timetable',['term'=>$term,'classes'=>$classes,'streams'=>$this->classId?Stream::where('school_id',$school->id)->where('school_class_id',$this->classId)->orderBy('name')->get():collect(),'subjects'=>Subject::where('school_id',$school->id)->orderBy('name')->get(),'teachers'=>User::where('school_id',$school->id)->where('employment_status','active')->whereNotIn('role',['student','parent'])->orderBy('name')->get(),'timetableSlots'=>$slots,'slotsByDay'=>$slots->groupBy('day_of_week'),'pageTitle'=>'Timetable']);
    }
}
