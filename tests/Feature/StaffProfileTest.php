<?php

use App\Models\Designation;
use App\Models\PayrollRun;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\StaffAttendanceRecord;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use App\Services\StaffProfileData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->school = School::create(['name' => 'Teacher Profile School', 'slug' => 'teacher-profile-school']);
    $this->admin = User::factory()->create(['school_id' => $this->school->id, 'role' => 'admin']);
    $this->teacher = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher', 'job_title' => 'Teacher', 'base_salary' => 1000000, 'national_id' => 'PRIVATE-NATIONAL-ID', 'bank_account_number' => 'PRIVATE-BANK-ACCOUNT']);
});

it('opens a teacher profile and saves permitted personal and employment fields', function () {
    $this->actingAs($this->admin)->get(route('staff.profile', $this->teacher))->assertOk()->assertSee('Teacher Profile')->assertSee('Classes &amp; subjects', false)->assertSee('PRIVATE-NATIONAL-ID');
    $this->patch(route('staff.profile.update', $this->teacher), [
        'name' => 'Updated Teacher', 'email' => $this->teacher->email, 'job_title' => 'Senior Teacher',
        'joined_at' => '2024-01-01', 'probation_ends_at' => '2024-04-01', 'contract_type' => 'permanent', 'base_salary' => 1200000,
        'bank_name' => 'School Bank', 'role' => 'admin', 'employment_status' => 'inactive',
    ])->assertSessionHasNoErrors();
    expect($this->teacher->fresh())->name->toBe('Updated Teacher')->role->toBe('teacher')->base_salary->toBe('1200000.00')->bank_name->toBe('School Bank');
});

it('hides private HR payroll attendance and leave from directory-only staff', function () {
    $this->teacher->update(['teaching_license_number' => 'PRIVATE-LICENSE', 'academic_qualifications' => 'PRIVATE-QUALIFICATIONS']);
    $designation = Designation::create(['school_id' => $this->school->id, 'name' => 'Directory reader', 'permissions' => ['staff.directory']]);
    $viewer = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher', 'designation_id' => $designation->id]);
    $this->actingAs($viewer)->get(route('staff.profile', $this->teacher))->assertOk()
        ->assertDontSee('PRIVATE-NATIONAL-ID')->assertDontSee('PRIVATE-BANK-ACCOUNT')->assertDontSee('PRIVATE-LICENSE')->assertDontSee('PRIVATE-QUALIFICATIONS')->assertDontSee('Salary and advances')->assertDontSee('Leave overlapping');
    $this->patch(route('staff.profile.update', $this->teacher), ['name' => 'Not allowed'])->assertForbidden();
});

it('deducts only posted salary and advances and scopes attendance to the teacher and month', function () {
    foreach ([['salary', 200000, 'posted'], ['advance', 100000, 'posted'], ['advance', 500000, 'pending']] as [$type, $amount, $status]) {
        $run = PayrollRun::create(['school_id' => $this->school->id, 'user_id' => $this->teacher->id, 'period' => now()->format('Y-m'), 'payment_type' => $type, 'amount' => $amount, 'method' => 'cash', 'paid_at' => now(), 'recorded_by' => $this->admin->id]);
        $run->ledgerEntry()->update(['status' => $status]);
    }
    foreach (['present', 'absent'] as $i => $status) {
        StaffAttendanceRecord::create(['school_id' => $this->school->id, 'user_id' => $this->teacher->id, 'attendance_date' => now()->startOfMonth()->addDays($i), 'status' => $status, 'recorded_by' => $this->admin->id]);
    }
    StaffAttendanceRecord::create(['school_id' => $this->school->id, 'user_id' => $this->admin->id, 'attendance_date' => now()->startOfMonth(), 'status' => 'absent', 'recorded_by' => $this->admin->id]);
    $data = app(StaffProfileData::class)->build($this->teacher, $this->admin, null, now()->format('Y-m'));
    expect($data['remaining'])->toBe(700000.0);
    expect($data['pending'])->toBe(500000.0);
    expect($data['attendanceRate'])->toBe(50.0);
    expect($data['attendance'])->toHaveCount(2);
    $this->actingAs($this->admin)->get(route('staff.profile', $this->teacher))->assertOk()->assertSee('700,000.00')->assertSee('Pending');
});

it('rejects foreign school profiles invalid months and invalid probation dates', function () {
    $other = School::create(['name' => 'Other Staff School', 'slug' => 'other-staff-school']);
    $foreign = User::factory()->create(['school_id' => $other->id, 'role' => 'teacher']);
    $this->actingAs($this->admin)->get(route('staff.profile', $foreign))->assertNotFound();
    $this->get(route('staff.profile', [$this->teacher, 'period' => '2026-13']))->assertSessionHasErrors('period');
    $this->patch(route('staff.profile.update', $this->teacher), ['name' => $this->teacher->name, 'email' => $this->teacher->email, 'job_title' => 'Teacher', 'joined_at' => '2025-01-01', 'probation_ends_at' => '2024-01-01'])->assertSessionHasErrors('probation_ends_at');
});

it('filters teaching assignments and timetable by term and rejects foreign terms', function () {
    $current = Term::create(['school_id' => $this->school->id, 'name' => 'Current term', 'year' => 2026, 'term_number' => 2, 'is_current' => true]);
    $old = Term::create(['school_id' => $this->school->id, 'name' => 'Old term', 'year' => 2026, 'term_number' => 1, 'is_current' => false]);
    $class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Primary Five', 'class_teacher_user_id' => $this->teacher->id]);
    foreach ([[$current, 'Current Mathematics'], [$old, 'Previous Science']] as [$term, $name]) {
        $subject = Subject::create(['school_id' => $this->school->id, 'name' => $name]);
        DB::table('staff_subjects')->insert(['school_id' => $this->school->id, 'user_id' => $this->teacher->id, 'term_id' => $term->id, 'school_class_id' => $class->id, 'subject_id' => $subject->id]);
        DB::table('timetable_slots')->insert(['school_id' => $this->school->id, 'user_id' => $this->teacher->id, 'term_id' => $term->id, 'school_class_id' => $class->id, 'subject_id' => $subject->id, 'day_of_week' => 'Monday', 'starts_at' => '08:00', 'ends_at' => '09:00']);
    }
    $this->actingAs($this->admin)->get(route('staff.profile', $this->teacher))->assertOk()->assertSee('Current Mathematics')->assertDontSee('Previous Science');
    $this->get(route('staff.profile', [$this->teacher, 'term' => $old->id]))->assertOk()->assertSee('Previous Science')->assertDontSee('Current Mathematics');
    $other = School::create(['name' => 'Other Term School', 'slug' => 'other-term-school']);
    $foreign = Term::create(['school_id' => $other->id, 'name' => 'Foreign term', 'year' => 2026, 'term_number' => 1]);
    $this->get(route('staff.profile', [$this->teacher, 'term' => $foreign->id]))->assertNotFound();
});

it('prevents non-admin staff managers from modifying administrators', function () {
    $designation = Designation::create(['school_id' => $this->school->id, 'name' => 'Staff manager', 'permissions' => ['staff.directory', 'staff.manage']]);
    $viewer = User::factory()->create(['school_id' => $this->school->id, 'role' => 'registrar', 'designation_id' => $designation->id]);
    $this->actingAs($viewer)->get(route('staff.profile', $this->admin))->assertOk()->assertDontSee('Save profile');
    $this->patch(route('staff.profile.update', $this->admin), ['name' => 'Changed'])->assertForbidden();
});

it('edits staff location and adds private qualifications without losing existing documents', function () {
    Storage::fake('public');
    Storage::fake('local');
    $this->actingAs($this->admin)->patch(route('staff.profile.update', $this->teacher), [
        'name' => $this->teacher->name, 'email' => $this->teacher->email, 'job_title' => 'Teacher',
        'location' => 'Wakiso', 'national_id' => 'NIN-UPDATED', 'bank_name' => 'New Bank', 'bank_account_name' => 'Teacher Account', 'bank_account_number' => '0000012345',
        'photo' => UploadedFile::fake()->image('staff-photo.png'),
        'qualification_documents' => [UploadedFile::fake()->create('certificate.pdf', 20, 'application/pdf')],
    ])->assertSessionHasNoErrors();
    $staff = $this->teacher->fresh();
    expect($staff->location)->toBe('Wakiso')->and($staff->national_id)->toBe('NIN-UPDATED')->and($staff->bank_account_number)->toBe('0000012345');
    Storage::disk('public')->assertExists($staff->avatar_path);
    $document = $staff->staffDocuments()->firstOrFail();
    $this->get(route('staff.profile', $staff))->assertOk()->assertSee('Wakiso')->assertSee('certificate.pdf')->assertSee($staff->avatarUrl());
    $this->patch(route('staff.profile.update', $staff), ['name' => $staff->name, 'email' => $staff->email, 'job_title' => 'Teacher'])->assertSessionHasNoErrors();
    expect($staff->staffDocuments()->count())->toBe(1);
    $this->get(route('staff.documents.download', [$staff, $document]))->assertOk()->assertDownload('certificate.pdf');
    $this->get(route('staff.documents.download', [$this->admin, $document]))->assertNotFound();
    $designation = Designation::create(['school_id' => $this->school->id, 'name' => 'Document viewer without HR access', 'permissions' => ['staff.directory']]);
    $viewer = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher', 'designation_id' => $designation->id]);
    $this->actingAs($viewer)->get(route('staff.profile', $staff))->assertOk()->assertDontSee('certificate.pdf')->assertDontSee('Wakiso');
    $this->get(route('staff.documents.download', [$staff, $document]))->assertForbidden();
    $other = School::create(['name' => 'Foreign HR School', 'slug' => 'foreign-hr-school']);
    $otherAdmin = User::factory()->create(['school_id' => $other->id, 'role' => 'admin']);
    $this->actingAs($otherAdmin)->get(route('staff.documents.download', [$staff, $document]))->assertNotFound();
});

it('edits optional professional details from the staff profile and validates their format', function () {
    $details = [
        'name' => $this->teacher->name, 'email' => $this->teacher->email, 'job_title' => 'Senior Teacher',
        'title' => 'Mrs', 'date_of_birth' => '1988-04-20', 'gender' => 'Female', 'nationality' => 'Ugandan',
        'teaching_license_number' => 'TL-12345', 'academic_qualifications' => 'Master of Education',
        'qualification_institution' => 'Teaching University', 'qualification_year' => '2018', 'base_salary' => 880000,
    ];
    $this->actingAs($this->admin)->patch(route('staff.profile.update', $this->teacher), $details)->assertSessionHasNoErrors();
    $staff = $this->teacher->fresh();
    expect($staff->title)->toBe('Mrs')->and($staff->teaching_license_number)->toBe('TL-12345')
        ->and($staff->academic_qualifications)->toBe('Master of Education')->and($staff->qualification_year)->toBe(2018)
        ->and($staff->date_of_birth->toDateString())->toBe('1988-04-20')->and($staff->base_salary)->toBe('880000.00');
    $this->get(route('staff.profile', $staff))->assertOk()->assertSee('Mrs '.$staff->name)
        ->assertSee('TL-12345')->assertSee('Master of Education')->assertSee('Teaching University');
    $this->patch(route('staff.profile.update', $staff), array_replace($details, [
        'date_of_birth' => now()->addDay()->toDateString(), 'teaching_license_number' => str_repeat('x', 101),
    ]))->assertSessionHasErrors(['date_of_birth', 'teaching_license_number']);
    expect($staff->fresh()->teaching_license_number)->toBe('TL-12345');
});
