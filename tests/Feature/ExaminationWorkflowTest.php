<?php

use App\Livewire\BulkTermReports;
use App\Livewire\ExamCategories;
use App\Livewire\ExamSetup;
use App\Livewire\MarksEntry;
use App\Livewire\ReportSettings;
use App\Livewire\StudentTermReport;
use App\Models\Designation;
use App\Models\Exam;
use App\Models\ExamCategory;
use App\Models\ExamCategoryGroup;
use App\Models\ExamCategoryGroupAssignment;
use App\Models\ExamPaper;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Stream;
use App\Models\Student;
use App\Models\StudentCategory;
use App\Models\StudentEnrolment;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use App\Services\PromotionStatement;
use App\Services\SchoolAcademicSetup;
use App\Services\StageAssessmentCalculator;
use App\Support\MobileAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function examinationManager(School $school, array $permissions, string $name = 'Examination Manager'): User
{
    $designation = Designation::create(['school_id' => $school->id, 'name' => $name, 'permissions' => $permissions]);

    return User::factory()->create(['school_id' => $school->id, 'designation_id' => $designation->id, 'role' => 'academic_admin']);
}

function examinationTerm(School $school, int $number = 1, int $year = 2026): Term
{
    return Term::create(['school_id' => $school->id, 'name' => 'Term '.$number, 'term_number' => $number, 'year' => $year,
        'start_date' => "$year-01-01", 'end_date' => "$year-04-01", 'is_current' => true, 'status' => 'open', 'locked' => false]);
}

it('creates class assessment groups with independent category weights', function () {
    $school = School::create(['name' => 'Grouped School', 'slug' => 'grouped-school', 'school_type' => 'primary']);
    $term = examinationTerm($school);
    $lower = SchoolClass::create(['school_id' => $school->id, 'name' => 'Primary 2', 'education_stage' => 'primary', 'sort_order' => 2]);
    $upper = SchoolClass::create(['school_id' => $school->id, 'name' => 'Primary 6', 'education_stage' => 'primary', 'sort_order' => 6]);
    $manager = examinationManager($school, ['exams.setup']);

    Livewire::actingAs($manager)->test(ExamCategories::class)
        ->set('groups', [
            ['id' => null, 'name' => 'Lower Primary', 'class_ids' => [(string) $lower->id], 'categories' => [
                ['id' => null, 'name' => 'Continuous Assessment', 'weight' => '40'],
                ['id' => null, 'name' => 'End of Term', 'weight' => '60'],
            ]],
            ['id' => null, 'name' => 'Upper Primary', 'class_ids' => [(string) $upper->id], 'categories' => [
                ['id' => null, 'name' => 'Coursework', 'weight' => '30'],
                ['id' => null, 'name' => 'Final Examination', 'weight' => '70'],
            ]],
        ])->call('save')->assertHasNoErrors();

    expect(ExamCategoryGroup::where('term_id', $term->id)->count())->toBe(2)
        ->and(ExamCategoryGroupAssignment::where('term_id', $term->id)->count())->toBe(2)
        ->and((float) ExamCategory::whereHas('group', fn ($query) => $query->where('name', 'Lower Primary'))->sum('weight'))->toBe(100.0);
});

it('only offers exam categories from the selected class group', function () {
    $school = School::create(['name' => 'Scoped Categories', 'slug' => 'scoped-categories', 'school_type' => 'primary']);
    $term = examinationTerm($school);
    $classA = SchoolClass::create(['school_id' => $school->id, 'name' => 'Primary 2', 'education_stage' => 'primary']);
    $classB = SchoolClass::create(['school_id' => $school->id, 'name' => 'Primary 6', 'education_stage' => 'primary']);
    $manager = examinationManager($school, ['exams.setup']);
    $groupA = ExamCategoryGroup::create(['school_id' => $school->id, 'term_id' => $term->id, 'name' => 'Lower']);
    $groupB = ExamCategoryGroup::create(['school_id' => $school->id, 'term_id' => $term->id, 'name' => 'Upper']);
    $categoryA = ExamCategory::create(['school_id' => $school->id, 'term_id' => $term->id, 'exam_category_group_id' => $groupA->id, 'name' => 'Lower Final', 'weight' => 100]);
    $categoryB = ExamCategory::create(['school_id' => $school->id, 'term_id' => $term->id, 'exam_category_group_id' => $groupB->id, 'name' => 'Upper Final', 'weight' => 100]);
    foreach ([[$classA, $groupA], [$classB, $groupB]] as [$class, $group]) {
        ExamCategoryGroupAssignment::create([
            'school_id' => $school->id, 'term_id' => $term->id, 'school_class_id' => $class->id, 'exam_category_group_id' => $group->id,
        ]);
    }

    Livewire::actingAs($manager)->test(ExamSetup::class)
        ->set('name', 'Wrong Category Exam')->set('classId', (string) $classA->id)->set('categoryId', (string) $categoryB->id)
        ->call('addExam')->assertHasErrors('categoryId');

    Livewire::actingAs($manager)->test(ExamSetup::class)
        ->set('name', 'Lower Primary Final')->set('classId', (string) $classA->id)->set('categoryId', (string) $categoryA->id)
        ->call('addExam')->assertHasNoErrors();

    expect(Exam::where('name', 'Lower Primary Final')->value('exam_category_id'))->toBe($categoryA->id);
});

it('calculates with the assigned class group and hides unpublished assessments from portal calculations', function () {
    $school = School::create(['name' => 'Calculation School', 'slug' => 'calculation-school', 'school_type' => 'primary']);
    $term = examinationTerm($school);
    $class = SchoolClass::create(['school_id' => $school->id, 'name' => 'Primary 3', 'education_stage' => 'primary']);
    $student = Student::create(['school_id' => $school->id, 'school_class_id' => $class->id, 'term_id' => $term->id, 'name' => 'Learner', 'status' => 'active']);
    $subject = Subject::create(['school_id' => $school->id, 'name' => 'Mathematics']);
    $group = ExamCategoryGroup::create(['school_id' => $school->id, 'term_id' => $term->id, 'name' => 'Lower Primary']);
    $continuous = ExamCategory::create(['school_id' => $school->id, 'term_id' => $term->id, 'exam_category_group_id' => $group->id, 'name' => 'Continuous', 'weight' => 40]);
    $final = ExamCategory::create(['school_id' => $school->id, 'term_id' => $term->id, 'exam_category_group_id' => $group->id, 'name' => 'Final', 'weight' => 60]);
    ExamCategoryGroupAssignment::create(['school_id' => $school->id, 'term_id' => $term->id, 'school_class_id' => $class->id, 'exam_category_group_id' => $group->id]);

    foreach ([[$continuous, 50, true], [$final, 100, false]] as [$category, $score, $published]) {
        $exam = Exam::create(['school_id' => $school->id, 'term_id' => $term->id, 'school_class_id' => $class->id,
            'exam_category_id' => $category->id, 'name' => $category->name, 'published_at' => $published ? now() : null]);
        $paper = ExamPaper::create(['exam_id' => $exam->id, 'subject_id' => $subject->id, 'maximum_score' => 100, 'weighting' => 1]);
        DB::table('exam_marks')->insert(['exam_paper_id' => $paper->id, 'student_id' => $student->id, 'score' => $score, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('exam_paper_submissions')->insert(['exam_paper_id' => $paper->id, 'status' => 'approved', 'created_at' => now(), 'updated_at' => now()]);
    }

    $settings = ['calculation_method' => 'single_exam', 'missing_assessment_rule' => 'incomplete', 'assessment_weights' => []];
    $internal = StageAssessmentCalculator::marks($student, $term, $settings)->first();
    $portal = StageAssessmentCalculator::marks($student, $term, $settings, null, true)->first();

    expect($internal['percentage'])->toBe(80.0)->and($internal['incomplete'])->toBeFalse()
        ->and($portal['percentage'])->toBe(20.0)->and($portal['incomplete'])->toBeTrue()
        ->and($portal['assessments'])->toHaveCount(1);
});

it('keeps submitted web marks read only until an approver reopens them', function () {
    $school = School::create(['name' => 'Approval School', 'slug' => 'approval-school', 'school_type' => 'primary']);
    $term = examinationTerm($school);
    $class = SchoolClass::create(['school_id' => $school->id, 'name' => 'Primary 4', 'education_stage' => 'primary']);
    $student = Student::create(['school_id' => $school->id, 'school_class_id' => $class->id, 'term_id' => $term->id, 'name' => 'Learner', 'status' => 'active']);
    $subject = Subject::create(['school_id' => $school->id, 'name' => 'English']);
    $exam = Exam::create(['school_id' => $school->id, 'term_id' => $term->id, 'school_class_id' => $class->id, 'name' => 'Midterm']);
    $paper = ExamPaper::create(['exam_id' => $exam->id, 'subject_id' => $subject->id, 'maximum_score' => 100, 'weighting' => 1]);
    $user = examinationManager($school, ['exams.marks'], 'Marks Officer');
    DB::table('exam_marks')->insert(['exam_paper_id' => $paper->id, 'student_id' => $student->id, 'score' => 50, 'created_at' => now(), 'updated_at' => now()]);
    DB::table('exam_paper_submissions')->insert(['exam_paper_id' => $paper->id, 'status' => 'submitted', 'submitted_by' => $user->id, 'submitted_at' => now(), 'created_at' => now(), 'updated_at' => now()]);

    Livewire::actingAs($user)->test(MarksEntry::class)->set('paperId', (string) $paper->id)
        ->set('scores.'.$student->id, '90')->call('saveDraft')->assertHasErrors('paperId');

    expect((float) DB::table('exam_marks')->where('exam_paper_id', $paper->id)->value('score'))->toBe(50.0)
        ->and(DB::table('exam_paper_submissions')->where('exam_paper_id', $paper->id)->value('status'))->toBe('submitted');
});

it('limits mobile mark rosters to the exam stream', function () {
    $school = School::create(['name' => 'Stream School', 'slug' => 'stream-school', 'school_type' => 'secondary']);
    $term = examinationTerm($school);
    $class = SchoolClass::create(['school_id' => $school->id, 'name' => 'Senior 2', 'education_stage' => 'lower_secondary']);
    $streamA = Stream::create(['school_id' => $school->id, 'school_class_id' => $class->id, 'name' => 'A']);
    $streamB = Stream::create(['school_id' => $school->id, 'school_class_id' => $class->id, 'name' => 'B']);
    $studentA = Student::create(['school_id' => $school->id, 'school_class_id' => $class->id, 'stream_id' => $streamA->id, 'name' => 'A Learner', 'status' => 'active']);
    Student::create(['school_id' => $school->id, 'school_class_id' => $class->id, 'stream_id' => $streamB->id, 'name' => 'B Learner', 'status' => 'active']);
    $subject = Subject::create(['school_id' => $school->id, 'name' => 'Mathematics']);
    $teacher = examinationManager($school, ['exams.marks'], 'Subject Teacher');
    $teacher->update(['role' => 'teacher']);
    DB::table('staff_subjects')->insert(['school_id' => $school->id, 'term_id' => $term->id, 'user_id' => $teacher->id,
        'school_class_id' => $class->id, 'subject_id' => $subject->id, 'created_at' => now(), 'updated_at' => now()]);

    $ids = MobileAccess::teacherStudentQuery($teacher, $class->id, $subject->id, $term->id, $streamA->id)->pluck('id');
    expect($ids->all())->toBe([$studentA->id]);
});

it('opens historical portal reports using the learner term enrolment', function () {
    $school = School::create(['name' => 'History School', 'slug' => 'history-school', 'school_type' => 'primary']);
    $oldTerm = Term::create(['school_id' => $school->id, 'name' => 'Term 3', 'term_number' => 3, 'year' => 2025, 'status' => 'closed', 'is_current' => false]);
    $currentTerm = examinationTerm($school, 1, 2026);
    $oldClass = SchoolClass::create(['school_id' => $school->id, 'name' => 'Primary 4', 'education_stage' => 'primary']);
    $currentClass = SchoolClass::create(['school_id' => $school->id, 'name' => 'Primary 5', 'education_stage' => 'primary']);
    $studentCategory = StudentCategory::create(['school_id' => $school->id, 'name' => 'Day']);
    $student = Student::create(['school_id' => $school->id, 'school_class_id' => $currentClass->id, 'term_id' => $currentTerm->id, 'name' => 'Promoted Learner', 'status' => 'active']);
    StudentEnrolment::create(['school_id' => $school->id, 'student_id' => $student->id, 'term_id' => $oldTerm->id,
        'school_class_id' => $oldClass->id, 'student_category_id' => $studentCategory->id, 'status' => 'active', 'enrolled_at' => '2025-09-01']);
    $exam = Exam::create(['school_id' => $school->id, 'term_id' => $oldTerm->id, 'school_class_id' => $oldClass->id,
        'name' => 'Old Final', 'status' => 'published', 'published_at' => now()]);
    $parent = User::factory()->create(['school_id' => $school->id, 'role' => 'parent']);
    DB::table('portal_user_students')->insert(['school_id' => $school->id, 'user_id' => $parent->id, 'student_id' => $student->id,
        'relationship' => 'parent', 'created_at' => now(), 'updated_at' => now()]);

    Livewire::actingAs($parent)->test(StudentTermReport::class, ['student' => $student, 'exam' => $exam])->assertOk();
});

it('supports examination report settings for every configured school stage', function () {
    $school = School::create(['name' => 'College', 'slug' => 'college', 'school_type' => 'tertiary']);
    SchoolClass::create(['school_id' => $school->id, 'name' => 'Diploma Year 1', 'education_stage' => 'tertiary']);
    $manager = examinationManager($school, ['settings.manage']);

    expect(SchoolAcademicSetup::stagesFor($school))->toBe(['tertiary']);
    Livewire::actingAs($manager)->test(ReportSettings::class)->set('stage', 'tertiary')->call('save')->assertHasNoErrors();
});

it('keeps promotion off normal reports and enables it for third term end of year reports', function () {
    $school = School::create(['name' => 'Annual Report School', 'slug' => 'annual-report-school', 'school_type' => 'primary']);
    $firstTerm = examinationTerm($school, 1, 2026);
    $firstTerm->update(['is_current' => false]);
    $thirdTerm = Term::create(['school_id' => $school->id, 'name' => 'Term 3', 'term_number' => 3, 'year' => 2026,
        'start_date' => '2026-09-01', 'end_date' => '2026-12-01', 'is_current' => true, 'status' => 'open', 'locked' => false]);
    $class = SchoolClass::create(['school_id' => $school->id, 'name' => 'Primary 6', 'education_stage' => 'primary']);
    $manager = examinationManager($school, ['reports.view']);

    Livewire::actingAs($manager)->test(BulkTermReports::class)
        ->assertSet('reportType', 'term')
        ->call('showEndOfYearReports')
        ->assertSet('reportType', 'end_of_year')
        ->assertSet('termId', (string) $thirdTerm->id)
        ->assertSee('End-of-year reports');
});

it('builds a clear promotion statement from the next enrolment', function () {
    $school = School::create(['name' => 'Promotion Report School', 'slug' => 'promotion-report-school', 'school_type' => 'primary']);
    $thirdTerm = Term::create(['school_id' => $school->id, 'name' => 'Term 3', 'term_number' => 3, 'year' => 2026, 'status' => 'closed']);
    $nextTerm = Term::create(['school_id' => $school->id, 'name' => 'Term 1', 'term_number' => 1, 'year' => 2027, 'status' => 'pending']);
    $oldClass = SchoolClass::create(['school_id' => $school->id, 'name' => 'Primary 5', 'education_stage' => 'primary']);
    $newClass = SchoolClass::create(['school_id' => $school->id, 'name' => 'Primary 6', 'education_stage' => 'primary']);
    $category = StudentCategory::create(['school_id' => $school->id, 'name' => 'Day']);
    $student = Student::create(['school_id' => $school->id, 'school_class_id' => $newClass->id, 'term_id' => $nextTerm->id, 'name' => 'Promoted Learner', 'status' => 'active']);
    $source = StudentEnrolment::create(['school_id' => $school->id, 'student_id' => $student->id, 'term_id' => $thirdTerm->id,
        'school_class_id' => $oldClass->id, 'student_category_id' => $category->id, 'status' => 'active', 'promotion_outcome' => 'promoted', 'enrolled_at' => '2026-09-01']);
    StudentEnrolment::create(['school_id' => $school->id, 'student_id' => $student->id, 'term_id' => $nextTerm->id,
        'school_class_id' => $newClass->id, 'student_category_id' => $category->id, 'status' => 'pending', 'enrolled_at' => '2027-01-01']);

    expect(PromotionStatement::for($student, $thirdTerm, $source))->toBe('Promoted to Primary 6.');
});
