<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

/** Explicit native form bindings to the existing, permission-checked school actions. */
class NativeTeacherActions
{
    public static function definitions(): array
    {
        return [
            'students.activities' => [\App\Livewire\StudentActivities::class, ['createHouse' => ['Create house', ['houseName', 'houseColor', 'housePatronId', 'houseDescription']], 'createClub' => ['Create club', ['clubName', 'clubColor', 'clubPatronId', 'clubDescription', 'clubMaximumMembers']], 'rebalanceHouses' => ['Rebalance house membership', []]]],
            'accounting.index' => [\App\Livewire\Accounting::class, ['saveJournal' => ['Save journal', ['journalDate', 'journalReference', 'journalDescription', 'journalLines']], 'submitJournal' => ['Submit journal', ['id']], 'approveJournal' => ['Approve journal', ['id']], 'postJournal' => ['Post journal', ['id']], 'reverseJournal' => ['Reverse journal', ['id', 'actionReason']], 'addAccount' => ['Add ledger account', ['accountCode', 'accountName', 'accountClass', 'accountSubtype', 'normalBalance', 'acceptsPostings', 'parentId']]]],
            'fee-payments.index' => [\App\Livewire\FeePayments::class, ['recordPayment' => ['Record fee payment', ['payingStudentId', 'amount', 'method', 'financialAccountId', 'notes', 'transaction_id', 'bank_slip_number']]]],
            'terms.index' => [\App\Livewire\TermManagement::class, ['add' => ['Add term', ['termNumber', 'year']], 'openTerm' => ['Open term', ['id']], 'closeWithoutRoll' => ['Close term without rolling arrears', ['id']], 'closeWithRoll' => ['Close term and permanently lock after rolling arrears', ['id']]]],
            'designations.index' => [\App\Livewire\Designations::class, ['save' => ['Save designation and access', ['editingId', 'name', 'description', 'permissions']]]],
            'accounting.assets' => [\App\Livewire\FixedAssets::class, ['saveAsset' => ['Register asset', ['assetTag', 'name', 'categoryId', 'financialAccountId', 'custodianId', 'serialNumber', 'location', 'description', 'acquisitionDate', 'inServiceDate', 'cost', 'residualValue', 'settlementType']], 'saveCategory' => ['Add asset category', ['categoryCode', 'categoryName', 'categoryLife', 'categoryMethod', 'categoryRate', 'assetAccountId', 'accumulatedAccountId', 'expenseAccountId']]]],
            'students.portal-access' => [\App\Livewire\PortalAccess::class, ['linkExisting' => ['Link existing account', ['studentId', 'userId', 'relationship']], 'createAndLink' => ['Create learner or parent login', ['studentId', 'role', 'name', 'email', 'password', 'relationship']]]],
            'exams.setup' => [\App\Livewire\ExamSetup::class, ['addExam' => ['Create exam', ['name', 'classId', 'streamId']], 'addPaper' => ['Add exam paper', ['examId', 'subjectId', 'maximumScore', 'weighting']]]],
            'grading-scales.index' => [\App\Livewire\GradingScales::class, ['save' => ['Save grade band', ['editingId', 'stage', 'minimum', 'maximum', 'grade', 'remark', 'points']], 'delete' => ['Delete grade band', ['id']]]],
            'timetable.index' => [\App\Livewire\Timetable::class, ['saveSlot' => ['Save timetable lesson', ['editingId', 'classId', 'streamId', 'day', 'startsAt', 'endsAt', 'subjectId', 'teacherId', 'label']], 'deleteSlot' => ['Delete timetable lesson', ['id']]]],
            'student-categories.index' => [\App\Livewire\StudentCategories::class, ['add' => ['Add category', ['name']], 'delete' => ['Delete category', ['id']]]],
            'classes.index' => [\App\Livewire\ClassesAndStreams::class, ['addClass' => ['Add class', ['class_name', 'education_stage']], 'addStream' => ['Add stream', ['school_class_id', 'stream_name']], 'saveClass' => ['Rename class', ['editingClassId', 'editingClassName']], 'saveStream' => ['Rename stream', ['editingStreamId', 'editingStreamName']], 'assignClassTeacher' => ['Assign class teacher', ['classId', 'teacherId']], 'setGraduatingClass' => ['Set graduating class', ['classId']], 'deleteClass' => ['Delete class', ['id']], 'deleteStream' => ['Delete stream', ['id']]]],
            'parents.register' => [\App\Livewire\ParentRegister::class, ['save' => ['Register parent', ['name', 'email', 'phone', 'relationship', 'password', 'studentIds']]]],
            'students.register' => [\App\Livewire\StudentRegister::class, ['register' => ['Register student', ['name', 'date_of_birth', 'gender', 'admission_date', 'school_class_id', 'stream_id', 'student_category_id', 'guardian_name', 'guardian_relationship', 'guardian_phone', 'guardian_email', 'guardian_address', 'nationality', 'religion', 'blood_group', 'home_address', 'medical_notes']]]],
            'communications.index' => [\App\Livewire\Communications::class, ['send' => ['Send school announcement', ['title', 'message', 'sendEmail', 'sendSms']]]],
            'fee-structures.index' => [\App\Livewire\FeeStructures::class, ['add' => ['Save fee structure', ['school_class_id', 'student_category_id', 'amount']]]],
            'payroll.index' => [\App\Livewire\Payroll::class, ['recordPayment' => ['Record payroll payment', ['period', 'selectedStaffId', 'paymentType', 'amount', 'method', 'financialAccountId', 'transactionId', 'bankSlipNumber', 'notes', 'paidOn']]]],
            'expenses.index' => [\App\Livewire\Expenses::class, ['add' => ['Record expense', ['termId', 'category', 'amount', 'description', 'expense_date', 'reference_number', 'financialAccountId', 'expenseLedgerAccountId', 'settlementType', 'payee', 'costCentreId', 'fundId']]]],
            'staff.register' => [\App\Livewire\StaffRegister::class, ['register' => ['Register staff', ['name', 'email', 'phone', 'password', 'password_confirmation', 'job_title', 'role', 'designation_id', 'joined_at', 'base_salary', 'employment_status', 'contract_type', 'probation_ends_at', 'emergency_contact_name', 'emergency_contact_phone', 'national_id', 'bank_name', 'bank_account_name', 'bank_account_number', 'admin_confirmation', 'has_teaching_duties', 'is_class_teacher', 'class_teacher_class_id', 'teaching_assignments']]]],
        ];
    }

    public static function forms(string $tool, $user): array
    {
        $definition = self::definitions()[$tool] ?? null;
        if (!$definition) return [];
        if ($tool === 'students.activities' && !$user->hasPermission('students.activities')) return [];
        if ($tool === 'accounting.assets' && !$user->hasPermission('accounting.assets.manage')) return [];
        return collect($definition[1])->filter(function ($definition, $action) use ($tool, $user) {
            if ($tool !== 'accounting.index') return true;
            $permission = ['saveJournal' => 'accounting.journals.create', 'submitJournal' => 'accounting.journals.submit', 'approveJournal' => 'accounting.journals.approve', 'postJournal' => 'accounting.journals.post', 'reverseJournal' => 'accounting.journals.reverse', 'addAccount' => 'accounting.accounts.manage'][$action];
            return $user->hasPermission($permission);
        })->map(fn ($action, $key) => ['action' => $key, 'label' => $action[0], 'fields' => collect($action[1])->map(fn ($field) => self::field($field, $user, $tool, $key))->values()])->values()->all();
    }

    private static function field(string $key, $user, string $tool, string $action): array
    {
        $field = ['key' => $key, 'label' => ucfirst(trim(preg_replace('/([a-z])([A-Z])/', '$1 $2', str_replace('_', ' ', $key))))];
        $choices = [
            'day' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
            'stage' => \App\Services\SchoolAcademicSetup::stagesFor($user->school),
            'gender' => ['male', 'female'], 'education_stage' => \App\Services\SchoolAcademicSetup::stagesFor($user->school),
            'role' => $tool === 'students.portal-access' ? ['parent', 'student'] : ['teacher', 'registrar', 'bursar', 'academic_admin', 'admin'], 'employment_status' => ['active', 'inactive'],
            'contract_type' => ['permanent', 'contract', 'temporary', 'volunteer'], 'paymentType' => ['salary', 'advance', 'bonus'],
            'accountClass' => ['asset', 'liability', 'equity', 'income', 'expense'], 'normalBalance' => ['debit', 'credit'],
            'categoryMethod' => ['straight_line', 'reducing_balance'],
            'method' => ['cash', 'bank', 'mobile_money'], 'settlementType' => ['immediate', 'credit'],
        ];
        if (isset($choices[$key])) $field['options'] = collect($choices[$key])->map(fn ($value) => ['value' => $value, 'label' => ucfirst(str_replace('_', ' ', $value))])->values();
        $tables = ['housePatronId' => 'users', 'clubPatronId' => 'users', 'editingClassId' => 'school_classes', 'editingStreamId' => 'streams', 'parentId' => 'ledger_accounts', 'payingStudentId' => 'students', 'categoryId' => 'fixed_asset_categories', 'custodianId' => 'users', 'assetAccountId' => 'ledger_accounts', 'accumulatedAccountId' => 'ledger_accounts', 'expenseAccountId' => 'ledger_accounts', 'studentId' => 'students', 'userId' => 'users', 'examId' => 'exams', 'classId' => 'school_classes', 'streamId' => 'streams', 'subjectId' => 'subjects', 'teacherId' => 'users', 'school_class_id' => 'school_classes', 'class_teacher_class_id' => 'school_classes', 'stream_id' => 'streams', 'student_category_id' => 'student_categories', 'designation_id' => 'designations', 'studentIds' => 'students', 'selectedStaffId' => 'users', 'financialAccountId' => 'financial_accounts', 'expenseLedgerAccountId' => 'ledger_accounts', 'costCentreId' => 'cost_centres', 'fundId' => 'accounting_funds', 'termId' => 'terms'];
        if (isset($tables[$key])) {
            $query = DB::table($tables[$key])->where('school_id', $user->school_id);
            if ($key === 'userId') $query->whereIn('role', ['student', 'parent']);
            if (in_array($key, ['selectedStaffId', 'teacherId'])) $query->whereNotIn('role', ['student', 'parent']);
            $field['options'] = $query->orderBy('name')->get(['id', 'name'])->map(fn ($row) => ['value' => (string) $row->id, 'label' => $row->name]);
        }
        if ($key === 'id') {
            [$table, $title] = match ($tool) { 'classes.index' => [$action === 'deleteStream' ? 'streams' : 'school_classes', 'name'], 'accounting.index' => ['accounting_journals', 'number'], 'terms.index' => ['terms', 'name'], 'student-categories.index' => ['student_categories', 'name'], 'grading-scales.index' => ['grading_scales', 'grade'], 'timetable.index' => ['timetable_slots', 'label'], default => ['', 'name'] };
            if ($table) $field['options'] = DB::table($table)->where('school_id', $user->school_id)->get()->map(fn ($r) => ['value' => (string) $r->id, 'label' => $table === 'timetable_slots' ? $r->day_of_week.' '.$r->starts_at.' '.($r->label ?: 'Lesson') : $r->{$title}]);
            $field['label'] = 'Record';
        }
        if ($key === 'journalLines') {
            $field['repeat'] = [['key' => 'ledger_account_id', 'label' => 'Ledger account', 'options' => DB::table('ledger_accounts')->where('school_id', $user->school_id)->orderBy('name')->get(['id','name'])->map(fn ($r) => ['value' => (string) $r->id, 'label' => $r->name])], ['key' => 'description', 'label' => 'Description'], ['key' => 'debit', 'label' => 'Debit'], ['key' => 'credit', 'label' => 'Credit']];
        }
        if ($key === 'teaching_assignments') {
            $field['repeat'] = [['key' => 'class_id', 'label' => 'Class', 'options' => DB::table('school_classes')->where('school_id', $user->school_id)->orderBy('name')->get(['id','name'])->map(fn ($r) => ['value' => (string) $r->id, 'label' => $r->name])], ['key' => 'subject_ids', 'label' => 'Subjects', 'multiple' => true, 'options' => DB::table('subjects')->where('school_id', $user->school_id)->orderBy('name')->get(['id','name'])->map(fn ($r) => ['value' => (string) $r->id, 'label' => $r->name])]];
        }
        $field['hidden'] = $key === 'editingId';
        $field['multiple'] = in_array($key, ['studentIds', 'permissions']);
        if ($key === 'permissions') $field['options'] = collect(DesignationPermissions::groups())->flatMap(fn ($group) => collect($group['rights'])->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values())->values();
        $field['secure'] = str_contains($key, 'password');
        $field['multiline'] = in_array($key, ['message', 'description', 'notes', 'medical_notes', 'home_address', 'guardian_address']);
        if (in_array($key, ['acceptsPostings', 'sendEmail', 'sendSms', 'admin_confirmation', 'has_teaching_duties', 'is_class_teacher'])) $field['options'] = [['value' => '1', 'label' => 'Yes'], ['value' => '0', 'label' => 'No']];
        return $field;
    }

    public static function run(Request $request, string $tool): bool
    {
        $definition = self::definitions()[$tool] ?? null;
        if (!$definition) return false;
        $action = $request->validate(['action' => ['required', 'string', 'max:80']])['action'];
        abort_unless(isset($definition[1][$action]), 422, 'Unknown action.');
        $component = Livewire::new($definition[0]);
        if (method_exists($component, 'mount')) app()->call([$component, 'mount']);
        $fields = $definition[1][$action][1];
        foreach (['journalLines', 'teaching_assignments'] as $key) { if (in_array($key, $fields, true) && is_string($request->input($key))) { $decoded = json_decode($request->input($key), true); abort_unless(is_array($decoded), 422, 'Invalid entries.'); $request->merge([$key => $decoded]); } }
        $rules = collect($fields)->mapWithKeys(fn ($field) => [$field => in_array($field, ['studentIds', 'permissions', 'journalLines', 'teaching_assignments']) ? ['sometimes', 'array', 'max:1000'] : ['sometimes', 'nullable', 'string', 'max:10000']])->all();
        $rules['studentIds.*'] = ['integer'];
        $rules['permissions.*'] = ['string', \Illuminate\Validation\Rule::in(collect(DesignationPermissions::groups())->flatMap(fn ($g) => array_keys($g['rights']))->all())];
        $values = $request->validate($rules);
        if ($tool === 'grading-scales.index' && !empty($values['editingId'])) abort_unless(DB::table('grading_scales')->where('school_id', $request->user()->school_id)->where('id', $values['editingId'])->exists(), 404);
        foreach ($fields as $field) {
            if (!array_key_exists($field, $values)) continue;
            if (!property_exists($component, $field)) continue;
            $type = (new \ReflectionProperty($component, $field))->getType()?->getName();
            $value = $values[$field];
            $component->{$field} = match ($type) { 'bool' => $value === '1', 'int' => $value === null || $value === '' ? null : (int) $value, 'array' => $value ?? [], default => $value ?? '' };
        }
        session()->forget(['status', 'error']);
        if ($tool === 'students.register') {
            $component->goToStep2(); $component->goToStep3(); $component->goToStep4();
        }
        $parameters = [];
        foreach ((new \ReflectionMethod($component, $action))->getParameters() as $parameter) {
            $name = $parameter->getName();
            if (!in_array($name, $fields, true)) continue;
            $parameters[$name] = $parameter->getType()?->getName() === 'int' ? (int) $request->validate([$name => ['required', 'integer', 'min:1']])[$name] : ($values[$name] ?? '');
        }
        app()->call([$component, $action], $parameters);
        if ($component->getErrorBag()->isNotEmpty()) throw ValidationException::withMessages($component->getErrorBag()->messages());
        if (session()->has('error')) throw ValidationException::withMessages(['action' => session()->pull('error')]);
        return true;
    }
}
