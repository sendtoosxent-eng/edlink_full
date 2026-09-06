<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\SchoolEvent;
use App\Support\MobileTeacherWorkspace;
use App\Support\TeacherAcademicScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class NativeTeacherToolsController extends ApiController
{
    private function authorizeTool(Request $request, string $tool): array
    {
        abort_unless(TeacherAcademicScope::isTeacher($request->user()), 403);
        $entry = collect(MobileTeacherWorkspace::forUser($request->user())['tools'])->firstWhere('id', $tool);
        abort_unless($entry, 403);
        return $entry;
    }

    public function show(Request $request, string $tool)
    {
        $entry = $this->authorizeTool($request, $tool);
        $user = $request->user();
        $school = $user->school_id;
        $forms = \App\Support\NativeTeacherActions::forms($tool, $user);
        if ($tool === 'subjects.index') {
            $assignments = TeacherAcademicScope::subjectAssignments($user, $user->school->currentTerm()?->id);
            $subjects = DB::table('subjects')->where('school_id', $school)->pluck('name', 'id');
            $classes = DB::table('school_classes')->where('school_id', $school)->pluck('name', 'id');
            return $this->ok(['title' => 'My subjects', 'rows' => $assignments->map(fn ($a) => ['id' => $a->school_class_id.':'.$a->subject_id, 'title' => $subjects[$a->subject_id] ?? 'Subject', 'details' => ['Class' => $classes[$a->school_class_id] ?? 'Class']])->values(), 'forms' => [], 'page' => 1, 'last_page' => 1]);
        }
        if (in_array($tool, ['reports.index', 'reports.student-term-report', 'reports.bulk-term-reports'], true)) {
            $class = match ($tool) { 'reports.index' => \App\Livewire\Reports::class, 'reports.student-term-report' => \App\Livewire\StudentTermReport::class, default => \App\Livewire\BulkTermReports::class };
            $component = \Livewire\Livewire::new($class);
            $component->mount();
            $filters = $request->validate(['report' => ['nullable', 'string', 'max:50'], 'termId' => ['nullable', 'integer'], 'studentId' => ['nullable', 'integer'], 'examId' => ['nullable', 'integer'], 'classId' => ['nullable', 'integer'], 'page' => ['nullable', 'integer', 'min:1']]);
            foreach ($filters as $key => $value) if (property_exists($component, $key)) $component->{$key} = $key === 'page' ? (int) $value : (string) $value;
            $view = $component->render()->getData();
            $options = fn ($items, $label = 'name') => collect($items)->map(fn ($item) => ['value' => (string) $item->id, 'label' => $item->{$label}])->values();
            $filterFields = [['key' => 'termId', 'label' => 'Term', 'options' => $options($view['terms'])]];
            $page = 1; $last = 1;
            if ($tool === 'reports.index') {
                $filterFields[] = ['key' => 'report', 'label' => 'Report type', 'options' => collect($view['reportOptions'])->map(fn ($label, $key) => ['value' => $key, 'label' => $label])->values()];
                $result = $view['result'];
                $rows = collect($result['rows'])->values()->map(fn ($row, $index) => ['id' => $index, 'title' => (string) (array_values((array) $row)[0] ?? 'Record'), 'details' => collect($result['columns'])->mapWithKeys(fn ($column, $i) => [$column => strip_tags((string) (array_values((array) $row)[$i] ?? ''))])]);
                $page = $result['pagination']['page']; $last = $result['pagination']['last_page'];
            } elseif ($tool === 'reports.student-term-report') {
                $filterFields[] = ['key' => 'studentId', 'label' => 'Learner', 'options' => $options($view['students'])];
                $filterFields[] = ['key' => 'examId', 'label' => 'Exam', 'options' => $options($view['exams'])];
                $rows = collect();
                if ($view['student'] && $view['exam']) {
                    $rows->push(['id' => 'summary', 'title' => $view['student']->name, 'details' => ['Exam' => $view['exam']->name, 'Average' => $view['average'], 'Aggregate' => $view['aggregate'], 'Position' => $view['position'], 'Attendance' => $view['attendance_present'].' / '.$view['attendance_total'], 'Teacher remarks' => $view['teacher_remarks']]]);
                    foreach ($view['grades'] as $grade) $rows->push(['id' => $grade->subject->id, 'title' => $grade->subject_name, 'details' => ['Score' => $grade->score.' / '.$grade->maximum_score, 'Percentage' => $grade->percentage, 'Grade' => $grade->grade]]);
                }
            } else {
                $filterFields[] = ['key' => 'classId', 'label' => 'Class', 'options' => $options($view['classes'])];
                $rows = collect($view['reports'])->map(function ($report) use ($view) {
                    $data = $report['data'];
                    $details = ['Average' => $data['average'], 'Aggregate' => $data['aggregate'], 'Position' => $view['positions'][$report['student']->id] ?? null, 'Attendance' => $data['attendance_present'].' / '.$data['attendance_total'], 'Remarks' => $data['teacher_remarks']];
                    foreach ($data['marks'] as $mark) $details[$mark['subject']] = $mark['score'].' / '.$mark['maximum'].' · '.$mark['grade'];
                    return ['id' => $report['student']->id, 'title' => $report['student']->name, 'details' => $details];
                });
            }
            return $this->ok(['title' => $entry['label'], 'rows' => $rows, 'forms' => [], 'filters' => $filterFields, 'page' => $page, 'last_page' => $last]);
        }
        if ($tool === 'attendance.reports') {
            $response = app(TeacherReportsController::class)->attendance($request)->getData(true)['data'];
            return $this->ok(['title' => 'Attendance reports', 'rows' => collect($response['records']['data'])->map(fn ($row) => ['id' => $row['id'], 'title' => $row['name'] ?? 'Learner', 'details' => ['Date' => $row['date'], 'Register' => $row['subject'], 'Status' => $row['status']]]), 'forms' => [], 'page' => $response['records']['current_page'], 'last_page' => $response['records']['last_page']]);
        }
        if ($tool === 'promotions.index') {
            $component = \Livewire\Livewire::new(\App\Livewire\Promotions::class); $component->mount();
            foreach ($request->validate(['sourceTermId' => ['nullable', 'integer'], 'targetTermId' => ['nullable', 'integer']]) as $key => $value) $component->{$key} = (string) $value;
            $component->loadLearners(); $view = $component->render()->getData();
            $options = fn ($items) => collect($items)->map(fn ($item) => ['value' => (string) $item->id, 'label' => $item->name])->values();
            $fields = [['key' => 'sourceTermId', 'label' => 'Closed source term', 'default' => $component->sourceTermId, 'options' => $options($view['terms']->where('status', 'closed'))], ['key' => 'targetTermId', 'label' => 'Target term', 'default' => $component->targetTermId, 'options' => $options($view['terms']->whereIn('status', ['pending', 'open']))]];
            $filterFields = $fields;
            $fields = array_map(fn ($field) => array_merge($field, ['hidden' => true]), $fields);
            foreach ($view['enrolments'] as $enrolment) {
                $fields[] = ['key' => 'outcome_'.$enrolment->id, 'label' => $enrolment->student->name.' · Outcome', 'default' => $component->outcomes[$enrolment->id], 'options' => collect(['promoted', 'repeated', 'graduated', 'withdrawn'])->map(fn ($value) => ['value' => $value, 'label' => ucfirst($value)])];
                $fields[] = ['key' => 'target_'.$enrolment->id, 'label' => $enrolment->student->name.' · Target class', 'default' => $component->targetClasses[$enrolment->id], 'options' => $options($view['classes'])];
            }
            return $this->ok(['title' => 'Promotions', 'filters' => $filterFields, 'rows' => $view['enrolments']->map(fn ($e) => ['id' => $e->id, 'title' => $e->student->name, 'details' => ['Class' => $e->schoolClass?->name, 'Outcome' => $e->promotion_outcome ?: 'Not decided']]), 'forms' => $view['enrolments']->isEmpty() ? [] : [['action' => 'commit_promotions', 'label' => 'Review and commit promotions', 'fields' => $fields]], 'page' => 1, 'last_page' => 1]);
        }
        if ($tool === 'subject-selections.index') {
            $term = $user->school->currentTerm();
            $students = TeacherAcademicScope::scopeStudents(\App\Models\Student::where('school_id', $school), $user, $term?->id)->where('status', 'active')->whereHas('schoolClass', fn ($q) => $q->where('education_stage', 'advanced_level')->orWhere(fn ($lower) => $lower->where('education_stage', 'lower_secondary')->whereIn('sort_order', [3, 4])))->with('schoolClass')->orderBy('name')->get(['id', 'name', 'school_class_id']);
            $student = $students->firstWhere('id', $request->integer('studentId')) ?? $students->first();
            $subjects = $student && $term ? DB::table('class_subjects as cs')->join('subjects as s', 's.id', '=', 'cs.subject_id')->where('cs.school_id', $school)->where('cs.term_id', $term->id)->where('cs.school_class_id', $student->school_class_id)->get(['s.id', 's.name']) : collect();
            $selected = $student && $term ? DB::table('student_subject_selections')->where('school_id', $school)->where('student_id', $student->id)->where('term_id', $term->id)->pluck('selection_type', 'subject_id') : collect();
            $types = $student ? \App\Services\StudentSubjectSelectionService::typesFor($student->schoolClass) : [];
            $fields = [['key' => 'studentId', 'label' => 'Learner', 'default' => $student ? (string) $student->id : '', 'hidden' => true, 'options' => $students->map(fn ($s) => ['value' => (string) $s->id, 'label' => $s->name])]];
            foreach ($subjects as $subject) $fields[] = ['key' => 'subject_'.$subject->id, 'label' => $subject->name, 'default' => $selected[$subject->id] ?? '', 'options' => collect(['' => 'Not selected'] + $types)->map(fn ($label, $value) => ['value' => (string) $value, 'label' => $label])->values()];
            return $this->ok(['title' => 'Student subject selection', 'filters' => [['key' => 'studentId', 'label' => 'Learner', 'options' => $students->map(fn ($s) => ['value' => (string) $s->id, 'label' => $s->name])]], 'rows' => $subjects->map(fn ($s) => ['id' => $s->id, 'title' => $s->name, 'details' => ['Selection' => $selected[$s->id] ?? 'Not selected']]), 'forms' => $student && $subjects->isNotEmpty() && $term?->isOpen() ? [['action' => 'save_selections', 'label' => 'Update subject selection', 'fields' => $fields]] : [], 'page' => 1, 'last_page' => 1]);
        }
        if ($tool === 'students.activities') {
            $rows = collect();
            foreach (['house', 'club'] as $kind) {
                $groups = DB::table('student_'.$kind.'s')->where('school_id', $school)->when(!$user->hasPermission('students.activities'), fn ($q) => $q->where('patron_user_id', $user->id))->get();
                foreach ($groups as $group) {
                    $members = DB::table('student_'.$kind.'_memberships as m')->join('students as s', 's.id', '=', 'm.student_id')->where('m.school_id', $school)->where('m.student_'.$kind.'_id', $group->id)->pluck('s.name');
                    $rows->push(['id' => $kind.':'.$group->id, 'title' => $group->name, 'details' => ['Type' => ucfirst($kind), 'Description' => $group->description, 'Members' => $members->implode(', ') ?: 'No members']]);
                }
            }
            $clubs = DB::table('student_clubs')->where('school_id', $school)->when(!$user->hasPermission('students.activities'), fn ($q) => $q->where('patron_user_id', $user->id))->get(['id', 'name']);
            $students = DB::table('students')->where('school_id', $school)->where('status', 'active')->orderBy('name')->get(['id', 'name']);
            $fields = [['key' => 'club_id', 'label' => 'Club', 'options' => $clubs->map(fn ($c) => ['value' => (string) $c->id, 'label' => $c->name])], ['key' => 'student_id', 'label' => 'Learner', 'options' => $students->map(fn ($s) => ['value' => (string) $s->id, 'label' => $s->name])]];
            $forms = array_merge($forms, $clubs->isNotEmpty() ? [['action' => 'add_club_member', 'label' => 'Add club member', 'fields' => $fields], ['action' => 'remove_club_member', 'label' => 'Remove club member', 'fields' => $fields]] : []);
            return $this->ok(['title' => 'Houses & clubs', 'rows' => $rows, 'forms' => $forms, 'page' => 1, 'last_page' => 1]);
        }
        if (in_array($tool, ['parents.index', 'parents.register'], true)) {
            $rows = DB::table('student_guardians as g')->join('students as s', 's.id', '=', 'g.student_id')->where('s.school_id', $school)->orderBy('g.name')->paginate(30, ['g.id', 'g.name', 'g.relationship', 'g.phone', 'g.email', 's.name as learner']);
            return $this->ok(['title' => 'Parents & guardians', 'rows' => collect($rows->items())->map(fn ($r) => ['id' => $r->id, 'title' => $r->name, 'details' => ['Learner' => $r->learner, 'Relationship' => $r->relationship, 'Phone' => $r->phone, 'Email' => $r->email]]), 'forms' => $forms, 'page' => $rows->currentPage(), 'last_page' => $rows->lastPage()]);
        }
        if (in_array($tool, ['students.register', 'students.portal-access', 'students.id-cards', 'graduates.index', 'promotions.index', 'subject-selections.index'], true)) {
            $query = TeacherAcademicScope::scopeStudents(\App\Models\Student::where('school_id', $school), $user, $user->school->currentTerm()?->id);
            if ($tool === 'graduates.index') $query->where('status', 'graduated');
            $rows = $query->with('schoolClass:id,name')->orderBy('name')->paginate(30, ['id', 'name', 'admission_no', 'school_class_id', 'status']);
            return $this->ok(['title' => $entry['label'], 'rows' => collect($rows->items())->map(fn ($r) => ['id' => $r->id, 'title' => $r->name, 'details' => ['Admission number' => $r->admission_no, 'Class' => $r->schoolClass?->name, 'Status' => $r->status]]), 'forms' => $forms, 'page' => $rows->currentPage(), 'last_page' => $rows->lastPage()]);
        }
        if ($tool === 'exams.setup') {
            $rows = \App\Models\Exam::with('schoolClass:id,name', 'term:id,name')->where('school_id', $school)->latest()->paginate(30);
            return $this->ok(['title' => 'Exams', 'rows' => collect($rows->items())->map(fn ($r) => ['id' => $r->id, 'title' => $r->name, 'details' => ['Class' => $r->schoolClass?->name, 'Term' => $r->term?->name, 'Status' => $r->status]]), 'forms' => $forms, 'page' => $rows->currentPage(), 'last_page' => $rows->lastPage()]);
        }
        if ($tool === 'settings.index') {
            $component = \Livewire\Livewire::new(\App\Livewire\SchoolSettings::class); $component->mount();
            $keys = ['name', 'email', 'phone', 'address', 'motto', 'website', 'principal_name'];
            $fields = collect($keys)->map(fn ($key) => ['key' => $key, 'label' => ucfirst(str_replace('_', ' ', $key)), 'default' => $component->{$key}]);
            foreach ($component->settings as $key => $value) $fields->push(['key' => 'setting_'.$key, 'label' => ucfirst(str_replace('_', ' ', $key)), 'default' => (string) $value, 'multiline' => true]);
            return $this->ok(['title' => 'School settings', 'rows' => [['id' => 'school', 'title' => $user->school->name, 'details' => collect($keys)->mapWithKeys(fn ($key) => [ucfirst(str_replace('_', ' ', $key)) => $component->{$key}])]], 'forms' => [['action' => 'save_school_settings', 'label' => 'Edit school settings', 'fields' => $fields]], 'page' => 1, 'last_page' => 1]);
        }
        if (in_array($tool, ['settings.index', 'settings.result-access'], true)) {
            $value = DB::table('school_settings')->where('school_id', $school)->where('key', 'results_fee_clearance_required')->value('value') ?? 'disabled';
            return $this->ok(['title' => 'Result access settings', 'rows' => [['id' => 'fee-clearance', 'title' => 'Require fee clearance for results', 'details' => ['Status' => $value]]], 'forms' => [['action' => 'save_result_access', 'label' => 'Save result access', 'fields' => [['key' => 'value', 'label' => 'Require fee clearance', 'options' => [['value' => 'enabled', 'label' => 'Enabled'], ['value' => 'disabled', 'label' => 'Disabled']]]]]], 'page' => 1, 'last_page' => 1]);
        }
        if ($tool === 'timetable.index') {
            $rows = DB::table('timetable_slots as t')->leftJoin('subjects as s', 's.id', '=', 't.subject_id')->leftJoin('school_classes as c', 'c.id', '=', 't.school_class_id')->leftJoin('users as u', 'u.id', '=', 't.user_id')->where('t.school_id', $school)->orderBy('t.day_of_week')->orderBy('t.starts_at')->paginate(30, ['t.*', 's.name as subject', 'c.name as class_name', 'u.name as teacher']);
            return $this->ok(['title' => 'Timetable', 'rows' => collect($rows->items())->map(fn ($r) => ['id' => $r->id, 'title' => $r->subject ?: ($r->label ?: 'Lesson'), 'details' => ['Class' => $r->class_name, 'Teacher' => $r->teacher, 'Day' => $r->day_of_week, 'Time' => $r->starts_at.' – '.$r->ends_at], 'values' => ['editingId' => $r->id, 'classId' => $r->school_class_id, 'streamId' => $r->stream_id, 'subjectId' => $r->subject_id, 'teacherId' => $r->user_id, 'day' => $r->day_of_week, 'startsAt' => $r->starts_at, 'endsAt' => $r->ends_at, 'label' => $r->label]]), 'forms' => $forms, 'page' => $rows->currentPage(), 'last_page' => $rows->lastPage()]);
        }
        $definitions = [
            'events.index' => ['school_events', 'title', ['event_date', 'type', 'target_audience', 'description']],
            'classes.index' => ['school_classes', 'name', ['education_stage', 'is_graduating_class']],
            'student-categories.index' => ['student_categories', 'name', []],
            'grading-scales.index' => ['grading_scales', 'grade', ['education_stage', 'minimum_percentage', 'maximum_percentage', 'aggregate_points', 'remark']],
            'staff.index' => ['users', 'name', ['staff_number', 'job_title', 'phone', 'email', 'employment_status']],
            'staff.register' => ['users', 'name', ['staff_number', 'job_title', 'employment_status']],
            'staff.attendance' => ['staff_attendance_records', 'attendance_date', ['user_id', 'status', 'note']],
            'payroll.index' => ['payroll_runs', 'period', ['user_id', 'payment_type', 'amount', 'method', 'paid_at']],
            'designations.index' => ['designations', 'name', ['description', 'permissions']],
            'terms.index' => ['terms', 'name', ['year', 'status', 'is_current']],
            'fee-structures.index' => ['fee_structures', 'amount', ['school_class_id', 'student_category_id', 'term_id']],
            'fee-payments.index' => ['fee_payments', 'amount', ['student_id', 'term_id', 'method', 'paid_at', 'notes']],
            'expenses.index' => ['expenses', 'category', ['amount', 'description', 'expense_date', 'payee']],
            'accounting.index' => ['accounting_journals', 'number', ['journal_date', 'description', 'status', 'currency']],
            'accounting.reconciliations' => ['finance_reconciliations', 'period_ending', ['statement_balance', 'ledger_balance', 'difference', 'status', 'notes']],
            'accounting.assets' => ['fixed_assets', 'name', ['asset_tag', 'location', 'acquisition_date', 'cost', 'status']],
            'communications.index' => ['announcements', 'title', ['message', 'target_audience', 'delivery_status', 'sent_at']],
        ];
        abort_unless(isset($definitions[$tool]), 404, 'This native module is not available.');
        [$table, $title, $fields] = $definitions[$tool];
        $query = DB::table($table)->where('school_id', $school);
        if ($table === 'users') $query->whereNotIn('role', ['parent', 'student']);
        if ($table === 'school_events') {
            $fields[] = 'term_id';
            $terms = DB::table('terms')->where('school_id', $school)->where('status', 'open')->where('locked', 0)->get(['id', 'name']);
            $forms[] = ['action' => 'save_event', 'label' => 'Save event', 'fields' => [
                ['key' => 'title', 'label' => 'Event title'], ['key' => 'event_date', 'label' => 'Date', 'placeholder' => 'YYYY-MM-DD'],
                ['key' => 'term_id', 'label' => 'Term', 'options' => $terms->map(fn ($term) => ['value' => (string) $term->id, 'label' => $term->name])],
                ['key' => 'type', 'label' => 'Type', 'options' => collect(['general','academic','staff','parent','sports','holiday'])->map(fn ($v) => ['value' => $v, 'label' => ucfirst($v)])],
                ['key' => 'target_audience', 'label' => 'Audience', 'options' => collect(['all','parents','staff','teachers'])->map(fn ($v) => ['value' => $v, 'label' => ucfirst($v)])],
                ['key' => 'description', 'label' => 'Description', 'multiline' => true],
            ]];
        }
        if ($tool === 'accounting.reconciliations') {
            $forms = [['action' => 'reconcile', 'label' => 'Reconcile account', 'fields' => [
                ['key' => 'financial_account_id', 'label' => 'Account', 'options' => DB::table('financial_accounts')->where('school_id', $school)->where('is_active', true)->orderBy('name')->get(['id','name'])->map(fn ($r) => ['value' => (string) $r->id, 'label' => $r->name])],
                ['key' => 'period_ending', 'label' => 'Statement end date', 'default' => today()->toDateString()], ['key' => 'statement_balance', 'label' => 'Statement balance'], ['key' => 'notes', 'label' => 'Notes', 'multiline' => true],
            ]]];
        }
        if ($tool === 'staff.attendance') {
            $forms = [['action' => 'save_staff_attendance', 'label' => 'Mark staff attendance', 'fields' => [
                ['key' => 'user_id', 'label' => 'Staff member', 'options' => DB::table('users')->where('school_id', $school)->where('employment_status', 'active')->whereNotIn('role', ['parent', 'student'])->orderBy('name')->get(['id', 'name'])->map(fn ($r) => ['value' => (string) $r->id, 'label' => $r->name])],
                ['key' => 'attendance_date', 'label' => 'Date', 'default' => today()->toDateString()],
                ['key' => 'status', 'label' => 'Status', 'options' => collect(\App\Models\StaffAttendanceRecord::STATUSES)->map(fn ($v) => ['value' => $v, 'label' => ucfirst(str_replace('_', ' ', $v))])], ['key' => 'note', 'label' => 'Note', 'multiline' => true],
            ]]];
        }
        $search = $request->validate(['search' => ['nullable', 'string', 'max:100']])['search'] ?? '';
        $rows = $query->when($search, fn ($q) => $q->where($title, 'like', '%'.$search.'%'))->orderByDesc('id')->paginate(30, array_unique(['id', $title, ...$fields]));
        $lookups = [];
        foreach (['user_id' => 'users', 'student_id' => 'students', 'school_class_id' => 'school_classes', 'student_category_id' => 'student_categories', 'term_id' => 'terms'] as $key => $relatedTable) {
            if (in_array($key, $fields, true)) $lookups[$key] = DB::table($relatedTable)->where('school_id', $school)->whereIn('id', collect($rows->items())->pluck($key))->pluck('name', 'id');
        }
        return $this->ok(['title' => $entry['label'], 'rows' => collect($rows->items())->map(fn ($row) => ['id' => $row->id, 'title' => (string) $row->{$title}, 'details' => collect($fields)->mapWithKeys(fn ($field) => [ucfirst(str_replace('_id', '', str_replace('_', ' ', $field))) => isset($lookups[$field]) ? ($lookups[$field][$row->{$field}] ?? '—') : $row->{$field}]), 'values' => $table === 'school_events' ? (array) $row : ($table === 'grading_scales' ? ['editingId' => $row->id, 'stage' => $row->education_stage, 'minimum' => $row->minimum_percentage, 'maximum' => $row->maximum_percentage, 'grade' => $row->grade, 'remark' => $row->remark, 'points' => $row->aggregate_points] : null)]), 'forms' => $forms, 'page' => $rows->currentPage(), 'last_page' => $rows->lastPage()]);
    }

    public function save(Request $request, string $tool)
    {
        $this->authorizeTool($request, $tool);
        if ($tool === 'promotions.index') {
            $school = $request->user()->school_id;
            $data = $request->validate(['action' => ['required', 'in:commit_promotions'], 'sourceTermId' => ['required', Rule::exists('terms', 'id')->where('school_id', $school)->where('status', 'closed')], 'targetTermId' => ['required', Rule::exists('terms', 'id')->where('school_id', $school)->whereIn('status', ['pending', 'open'])]]);
            $component = \Livewire\Livewire::new(\App\Livewire\Promotions::class); $component->sourceTermId = (string) $data['sourceTermId']; $component->targetTermId = (string) $data['targetTermId'];
            $component->loadLearners();
            $classIds = DB::table('school_classes')->where('school_id', $school)->pluck('id')->map(fn ($id) => (int) $id);
            foreach (array_keys($component->outcomes) as $id) {
                $outcome = $request->input('outcome_'.$id, $component->outcomes[$id]);
                abort_unless(in_array($outcome, ['promoted', 'repeated', 'graduated', 'withdrawn'], true), 422, 'Invalid promotion outcome.');
                $target = (int) $request->input('target_'.$id, $component->targetClasses[$id]);
                abort_unless($classIds->contains($target), 422, 'Choose a target class in this school.');
                $component->outcomes[$id] = $outcome; $component->targetClasses[$id] = (string) $target;
            }
            $component->commit();
            return $this->ok(['saved' => true]);
        }
        if ($tool === 'subject-selections.index') {
            $user = $request->user(); $term = $user->school->currentTerm();
            abort_unless($term?->isOpen(), 422, 'Open a current term before changing selections.');
            $data = $request->validate(['action' => ['required', 'in:save_selections'], 'studentId' => ['required', 'integer']]);
            $student = TeacherAcademicScope::scopeStudents(\App\Models\Student::where('school_id', $user->school_id), $user, $term->id)->with('schoolClass')->findOrFail($data['studentId']);
            abort_unless(\App\Services\StudentSubjectSelectionService::classUsesIndividualSelection($student->schoolClass), 422, 'This class does not use individual subject selection.');
            $ids = DB::table('class_subjects')->where('school_id', $user->school_id)->where('term_id', $term->id)->where('school_class_id', $student->school_class_id)->pluck('subject_id');
            $types = array_keys(\App\Services\StudentSubjectSelectionService::typesFor($student->schoolClass));
            $selected = collect();
            foreach ($ids as $id) { $type = $request->input('subject_'.$id); if ($type !== null && $type !== '') { abort_unless(in_array($type, $types, true), 422, 'Invalid subject selection type.'); $selected->put($id, $type); } }
            abort_if($selected->isEmpty(), 422, 'Select at least one subject.');
            if ($student->schoolClass->education_stage === 'advanced_level') abort_unless($selected->filter(fn ($t) => $t === 'principal')->count() === 3 && $selected->contains('subsidiary'), 422, 'Choose exactly three principal subjects and at least one subsidiary.');
            DB::transaction(function () use ($selected, $student, $user, $term) {
                DB::table('student_subject_selections')->where('school_id', $user->school_id)->where('student_id', $student->id)->where('term_id', $term->id)->delete();
                foreach ($selected as $id => $type) DB::table('student_subject_selections')->insert(['school_id' => $user->school_id, 'term_id' => $term->id, 'student_id' => $student->id, 'subject_id' => $id, 'selection_type' => $type, 'created_at' => now(), 'updated_at' => now()]);
            });
            return $this->ok(['saved' => true]);
        }
        if ($tool === 'students.activities' && in_array($request->input('action'), ['add_club_member', 'remove_club_member'], true)) {
            $data = $request->validate(['action' => ['required', 'in:add_club_member,remove_club_member'], 'club_id' => ['required', 'integer'], 'student_id' => ['required', 'integer']]);
            $user = $request->user();
            $club = DB::table('student_clubs')->where('school_id', $user->school_id)->where('id', $data['club_id'])->when(!$user->hasPermission('students.activities'), fn ($q) => $q->where('patron_user_id', $user->id))->first();
            abort_unless($club, 403);
            abort_unless(DB::table('students')->where('school_id', $user->school_id)->where('id', $data['student_id'])->where('status', 'active')->exists(), 422, 'Select an active learner in this school.');
            DB::transaction(function () use ($club, $data, $user) {
                DB::table('student_clubs')->where('id', $club->id)->lockForUpdate()->first();
                $membership = DB::table('student_club_memberships')->where('school_id', $user->school_id)->where('student_club_id', $club->id);
                if ($data['action'] === 'remove_club_member') { (clone $membership)->where('student_id', $data['student_id'])->delete(); return; }
                if ((clone $membership)->where('student_id', $data['student_id'])->exists()) return;
                abort_if($club->maximum_members && $membership->count() >= $club->maximum_members, 422, 'This club is at its member limit.');
                DB::table('student_club_memberships')->insert(['school_id' => $user->school_id, 'student_club_id' => $club->id, 'student_id' => $data['student_id'], 'assigned_by' => $user->id, 'created_at' => now(), 'updated_at' => now()]);
            });
            return $this->ok(['saved' => true]);
        }
        if ($tool === 'settings.index') {
            $component = \Livewire\Livewire::new(\App\Livewire\SchoolSettings::class); $component->mount();
            $keys = ['name', 'email', 'phone', 'address', 'motto', 'website', 'principal_name'];
            $rules = ['action' => ['required', 'in:save_school_settings']];
            foreach ($keys as $key) $rules[$key] = ['sometimes', 'nullable', 'string', 'max:2000'];
            foreach (array_keys($component->settings) as $key) $rules['setting_'.$key] = ['sometimes', 'nullable', 'string', 'max:10000'];
            $data = $request->validate($rules);
            foreach ($keys as $key) if (array_key_exists($key, $data)) $component->{$key} = $data[$key] ?? '';
            foreach (array_keys($component->settings) as $key) if (array_key_exists('setting_'.$key, $data)) $component->settings[$key] = $data['setting_'.$key] ?? '';
            $component->save();
            return $this->ok(['saved' => true]);
        }
        if ($tool === 'staff.attendance') {
            $school = $request->user()->school_id;
            $data = $request->validate(['action' => ['required', 'in:save_staff_attendance'], 'user_id' => ['required', 'integer', Rule::exists('users', 'id')->where(fn ($q) => $q->where('school_id', $school)->where('employment_status', 'active')->whereNotIn('role', ['parent', 'student']))], 'attendance_date' => ['required', 'date'], 'status' => ['required', Rule::in(\App\Models\StaffAttendanceRecord::STATUSES)], 'note' => ['nullable', 'string', 'max:1000']]);
            \App\Models\StaffAttendanceRecord::updateOrCreate(['school_id' => $school, 'user_id' => $data['user_id'], 'attendance_date' => $data['attendance_date']], ['status' => $data['status'], 'note' => $data['note'] ?? null, 'recorded_by' => $request->user()->id]);
            return $this->ok(['saved' => true]);
        }
        if ($tool === 'accounting.reconciliations') {
            $request->validate(['action' => ['required', 'in:reconcile']]);
            app(\App\Http\Controllers\SchoolOperationsController::class)->reconcile($request, app(\App\Services\FinanceLedgerService::class));
            return $this->ok(['saved' => true]);
        }
        if (\App\Support\NativeTeacherActions::run($request, $tool)) return $this->ok(['saved' => true]);
        if (in_array($tool, ['settings.index', 'settings.result-access'], true)) {
            $data = $request->validate(['action' => ['required', 'in:save_result_access'], 'value' => ['required', 'in:enabled,disabled']]);
            \App\Models\SchoolSetting::updateOrCreate(['school_id' => $request->user()->school_id, 'key' => 'results_fee_clearance_required'], ['value' => $data['value']]);
            return $this->ok(['saved' => true]);
        }
        abort_unless($tool === 'events.index', 403);
        $school = $request->user()->school_id;
        $data = $request->validate([
            'action' => ['required', 'in:save_event'], 'id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:255'], 'event_date' => ['required', 'date'],
            'term_id' => ['required', Rule::exists('terms', 'id')->where('school_id', $school)->where('status', 'open')->where('locked', 0)],
            'type' => ['required', 'in:general,academic,staff,parent,sports,holiday'],
            'target_audience' => ['required', 'in:all,parents,staff,teachers'], 'description' => ['nullable', 'string', 'max:2000'],
        ]);
        $event = !empty($data['id']) ? SchoolEvent::where('school_id', $school)->findOrFail($data['id']) : new SchoolEvent(['school_id' => $school]);
        abort_if($event->exists && !$event->term?->isOpen(), 422, 'Events in a closed term cannot be edited.');
        unset($data['action'], $data['id']);
        $event->fill($data)->save();
        return $this->ok(['saved' => true]);
    }
}
