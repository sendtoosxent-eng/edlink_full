<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\Student;
use App\Models\StudentEnrolment;
use App\Models\Term;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StageAssessmentCalculator
{
    public static function marks(Student $student, Term $term, array $settings, ?Exam $selectedExam = null, bool $publishedOnly = false): Collection
    {
        $enrolment = StudentEnrolment::where([
            'school_id' => $student->school_id, 'student_id' => $student->id, 'term_id' => $term->id,
        ])->first();
        $classId = (int) ($selectedExam?->school_class_id ?? $enrolment?->school_class_id ?? $student->school_class_id);
        $streamId = $selectedExam?->stream_id ?? $enrolment?->stream_id ?? $student->stream_id;
        $groupId = DB::table('exam_category_group_assignments')->where([
            'school_id' => $student->school_id, 'term_id' => $term->id, 'school_class_id' => $classId,
        ])->value('exam_category_group_id');
        $categories = $groupId ? DB::table('exam_categories')->where('exam_category_group_id', $groupId)
            ->orderBy('sort_order')->orderBy('id')->get(['id', 'name', 'weight']) : collect();
        $useCategories = $groupId !== null && $categories->isNotEmpty();

        $rows = DB::table('exam_marks')
            ->join('exam_papers', 'exam_papers.id', '=', 'exam_marks.exam_paper_id')
            ->join('exams', 'exams.id', '=', 'exam_papers.exam_id')
            ->join('exam_paper_submissions', 'exam_paper_submissions.exam_paper_id', '=', 'exam_papers.id')
            ->join('subjects', 'subjects.id', '=', 'exam_papers.subject_id')
            ->leftJoin('exam_categories', 'exam_categories.id', '=', 'exams.exam_category_id')
            ->where('exam_marks.student_id', $student->id)
            ->where('exams.school_id', $student->school_id)
            ->where('exams.term_id', $term->id)
            ->where('exams.school_class_id', $classId)
            ->where(fn ($query) => $query->whereNull('exams.stream_id')->when($streamId, fn ($stream) => $stream->orWhere('exams.stream_id', $streamId)))
            ->where('exam_paper_submissions.status', 'approved')
            ->whereNotNull('exam_marks.score')
            ->when($publishedOnly, fn ($query) => $query->whereNotNull('exams.published_at'))
            ->when($useCategories, fn ($query) => $query->whereIn('exams.exam_category_id', $categories->pluck('id')))
            ->when(! $useCategories && ($settings['calculation_method'] ?? 'single_exam') === 'single_exam' && $selectedExam,
                fn ($query) => $query->where('exams.id', $selectedExam->id))
            ->select('subjects.id as subject_id', 'subjects.name as subject_name', 'exams.id as exam_id', 'exams.name as exam_name', 'exams.exam_category_id', 'exam_marks.score', 'exam_papers.maximum_score', 'exam_papers.weighting')
            ->orderBy('exams.created_at')->orderBy('exams.id')
            ->get();

        $weights = collect($settings['assessment_weights'] ?? [])->mapWithKeys(
            fn (array $item) => [Str::slug($item['name'] ?? '') => (float) ($item['weight'] ?? 0)]
        )->filter(fn (float $weight, string $name) => $name !== '' && $weight > 0);

        return $rows->groupBy('subject_id')->map(function (Collection $subjectRows) use ($settings, $weights, $categories, $useCategories): array {
            $first = $subjectRows->first();
            $attempts = $subjectRows->map(fn (object $row) => [
                'exam_id' => (int) $row->exam_id,
                'name' => $row->exam_name,
                'key' => Str::slug($row->exam_name),
                'category_id' => $row->exam_category_id,
                'score' => (float) $row->score,
                'maximum' => (float) $row->maximum_score,
                'percentage' => (float) $row->maximum_score > 0 ? (float) $row->score / (float) $row->maximum_score * 100 : 0,
                'weighting' => (float) $row->weighting,
            ])->values();
            $method = $settings['calculation_method'] ?? 'single_exam';
            $incomplete = false;

            if ($useCategories) {
                $byCategory = $attempts->filter(fn (array $attempt) => $attempt['category_id'] !== null)->groupBy('category_id');
                $presentWeights = collect();
                $weighted = 0.0;
                foreach ($categories as $category) {
                    $categoryAttempts = $byCategory->get($category->id, collect());
                    if ($categoryAttempts->isEmpty()) {
                        continue;
                    }
                    $weight = (float) $category->weight;
                    $presentWeights->put($category->id, $weight);
                    $weighted += (float) $categoryAttempts->avg('percentage') * $weight;
                }
                $missingRule = $settings['missing_assessment_rule'] ?? 'incomplete';
                $incomplete = $presentWeights->count() < $categories->count() && $missingRule === 'incomplete';
                $denominator = $missingRule === 'redistribute' ? $presentWeights->sum() : 100.0;
                $percentage = $denominator > 0 ? $weighted / $denominator : 0;
                $score = $percentage;
                $maximum = 100.0;
            } elseif ($method === 'weighted' && $weights->isNotEmpty()) {
                $present = $attempts->keyBy('key');
                $missing = $weights->keys()->diff($present->keys());
                $missingRule = $settings['missing_assessment_rule'] ?? 'incomplete';
                $incomplete = $missing->isNotEmpty() && $missingRule === 'incomplete';
                $denominator = $missingRule === 'redistribute' ? $weights->only($present->keys())->sum() : $weights->sum();
                $weighted = $weights->sum(fn (float $weight, string $key) => ($present->get($key)['percentage'] ?? 0) * $weight);
                $percentage = $denominator > 0 ? $weighted / $denominator : 0;
                $score = $percentage;
                $maximum = 100.0;
            } elseif ($method === 'best_exam') {
                $best = $attempts->sortByDesc('percentage')->first();
                $percentage = $best['percentage'] ?? 0;
                $score = $best['score'] ?? 0;
                $maximum = $best['maximum'] ?? 100;
            } elseif ($method === 'average') {
                $percentage = (float) $attempts->avg('percentage');
                $score = $percentage;
                $maximum = 100.0;
            } else {
                $attempt = $attempts->last();
                $percentage = $attempt['percentage'] ?? 0;
                $score = $attempt['score'] ?? 0;
                $maximum = $attempt['maximum'] ?? 100;
            }

            return [
                'subject_id' => (int) $first->subject_id,
                'subject' => $first->subject_name,
                'score' => round($score, 2),
                'maximum' => round($maximum, 2),
                'percentage' => round($percentage, 2),
                'weighting' => max(0.01, (float) $attempts->avg('weighting')),
                'incomplete' => $incomplete,
                'assessments' => $attempts,
            ];
        })->values()->sortBy('subject')->values();
    }

    public static function weightedAverage(Collection $marks): float
    {
        $weightedMaximum = $marks->sum(fn (array|object $mark) => (float) data_get($mark, 'weighting', 1));
        if ($weightedMaximum <= 0) {
            return 0.0;
        }

        return round($marks->sum(fn (array|object $mark) => (float) data_get($mark, 'percentage', 0) * (float) data_get($mark, 'weighting', 1)) / $weightedMaximum, 2);
    }
}
