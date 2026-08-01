<?php

namespace App\Services;

use App\Models\Designation;
use App\Models\FeeStructure;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Stream;
use App\Models\Student;
use App\Models\StudentCategory;
use App\Models\StudentEnrolment;
use App\Models\StudentGuardian;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PlatformSchoolImportService
{
    public const STUDENT_HEADERS = ['name','admission_no','class','stream','category','date_of_birth','gender','admission_date','guardian_name','guardian_relationship','guardian_phone','guardian_email'];
    public const TEACHER_HEADERS = ['name','email','phone','job_title','designation','temporary_password','joined_at','base_salary','employment_status'];

    public function importStudents(School $school, UploadedFile $file): int
    {
        $rows = $this->rows($file, self::STUDENT_HEADERS);
        $term = $school->currentTerm();
        if (! $term?->isOpen()) {
            throw ValidationException::withMessages(['file' => 'Open a current term for this school before importing students.']);
        }

        $prepared = [];
        $errors = [];
        $seenAdmissions = [];
        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $validator = Validator::make($row, [
                'name' => ['required','string','max:255'],
                'admission_no' => ['nullable','string','max:255'],
                'class' => ['required','string','max:255'],
                'stream' => ['nullable','string','max:255'],
                'category' => ['required','string','max:255'],
                'date_of_birth' => ['nullable','date'],
                'gender' => ['nullable','in:male,female'],
                'admission_date' => ['required','date'],
                'guardian_name' => ['required','string','max:255'],
                'guardian_relationship' => ['nullable','string','max:100'],
                'guardian_phone' => ['nullable','string','max:50'],
                'guardian_email' => ['nullable','email','max:255'],
            ]);
            if ($validator->fails()) {
                $errors["row_$line"] = "Row $line: ".$validator->errors()->first();
                continue;
            }

            $class = SchoolClass::where('school_id', $school->id)->whereRaw('lower(name) = ?', [mb_strtolower($row['class'])])->first();
            $category = StudentCategory::where('school_id', $school->id)->whereRaw('lower(name) = ?', [mb_strtolower($row['category'])])->first();
            $stream = null;
            if ($class && $row['stream'] !== '') {
                $stream = Stream::where('school_class_id', $class->id)->whereRaw('lower(name) = ?', [mb_strtolower($row['stream'])])->first();
            }
            if (! $class) $errors["row_$line"] = "Row $line: class '{$row['class']}' does not exist at this school.";
            elseif (! $category) $errors["row_$line"] = "Row $line: category '{$row['category']}' does not exist at this school.";
            elseif ($row['stream'] !== '' && ! $stream) $errors["row_$line"] = "Row $line: stream '{$row['stream']}' does not belong to class '{$row['class']}'.";

            $admission = mb_strtoupper(trim($row['admission_no']));
            if ($admission !== '') {
                $key = mb_strtolower($admission);
                if (isset($seenAdmissions[$key]) || Student::where('school_id', $school->id)->whereRaw('lower(admission_no) = ?', [$key])->exists()) {
                    $errors["row_$line"] = "Row $line: admission number '$admission' is already in use.";
                }
                $seenAdmissions[$key] = true;
            }
            if ($class && $category && ! isset($errors["row_$line"])) {
                $prepared[] = compact('row','class','stream','category','admission');
            }
        }
        if ($errors) throw ValidationException::withMessages($errors);
        if ($school->license_student_limit !== null && $school->activeStudentCount() + count($prepared) > $school->license_student_limit) {
            throw ValidationException::withMessages(['file' => 'This import would exceed the school licence limit of '.number_format($school->license_student_limit).' active students.']);
        }

        return DB::transaction(function () use ($prepared, $school, $term): int {
            $sequence = Student::where('school_id', $school->id)->count() + 1;
            foreach ($prepared as $item) {
                $row = $item['row'];
                $admission = $item['admission'] ?: $this->nextAdmissionNumber($school, $sequence++);
                $student = Student::create([
                    'school_id' => $school->id, 'school_class_id' => $item['class']->id,
                    'stream_id' => $item['stream']?->id, 'student_category_id' => $item['category']->id,
                    'term_id' => $term->id, 'status' => 'active', 'name' => $row['name'],
                    'admission_no' => $admission, 'date_of_birth' => $row['date_of_birth'] ?: null,
                    'gender' => $row['gender'] ?: null, 'admission_date' => $row['admission_date'],
                ]);
                StudentGuardian::create([
                    'student_id' => $student->id, 'name' => $row['guardian_name'],
                    'relationship' => $row['guardian_relationship'] ?: 'Parent', 'phone' => $row['guardian_phone'] ?: null,
                    'email' => $row['guardian_email'] ?: null, 'is_primary' => true,
                ]);
                $fee = FeeStructure::where(['school_id'=>$school->id,'term_id'=>$term->id,'school_class_id'=>$student->school_class_id,'student_category_id'=>$student->student_category_id])->first();
                StudentEnrolment::create([
                    'school_id'=>$school->id,'student_id'=>$student->id,'term_id'=>$term->id,
                    'school_class_id'=>$student->school_class_id,'stream_id'=>$student->stream_id,
                    'student_category_id'=>$student->student_category_id,'fee_structure_id'=>$fee?->id,
                    'base_fee_amount'=>$fee?->amount ?? 0,'status'=>'active','enrolled_at'=>$student->admission_date,
                ]);
            }
            return count($prepared);
        });
    }

    public function importTeachers(School $school, UploadedFile $file): int
    {
        $rows = $this->rows($file, self::TEACHER_HEADERS);
        $prepared = [];
        $errors = [];
        $seenEmails = [];
        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $row['email'] = mb_strtolower(trim($row['email']));
            $validator = Validator::make($row, [
                'name'=>['required','string','max:255'],'email'=>['required','email','max:255'],
                'phone'=>['nullable','string','max:50'],'job_title'=>['required','string','max:100'],
                'designation'=>['required','string','max:100'],'temporary_password'=>['required','string','min:8'],
                'joined_at'=>['required','date'],'base_salary'=>['nullable','numeric','min:0'],
                'employment_status'=>['required','in:active,inactive'],
            ]);
            if ($validator->fails()) {
                $errors["row_$line"] = "Row $line: ".$validator->errors()->first();
                continue;
            }
            $designation = Designation::where('school_id', $school->id)->whereRaw('lower(name) = ?', [mb_strtolower($row['designation'])])->first();
            if (! $designation) $errors["row_$line"] = "Row $line: designation '{$row['designation']}' does not exist at this school.";
            if (isset($seenEmails[$row['email']]) || User::where('school_id', $school->id)->where('email', $row['email'])->exists()) {
                $errors["row_$line"] = "Row $line: email '{$row['email']}' is already in use at this school.";
            }
            $seenEmails[$row['email']] = true;
            if ($designation && ! isset($errors["row_$line"])) $prepared[] = compact('row','designation');
        }
        if ($errors) throw ValidationException::withMessages($errors);

        return DB::transaction(function () use ($prepared, $school): int {
            $sequence = $school->users()->count() + 1;
            foreach ($prepared as $item) {
                $row = $item['row'];
                User::create([
                    'school_id'=>$school->id,'designation_id'=>$item['designation']->id,
                    'staff_number'=>$this->nextStaffNumber($school, $sequence++),'name'=>$row['name'],
                    'email'=>$row['email'],'phone'=>$row['phone'] ?: null,'password'=>$row['temporary_password'],
                    'job_title'=>$row['job_title'],'role'=>'teacher','joined_at'=>$row['joined_at'],
                    'base_salary'=>$row['base_salary'] ?: 0,'employment_status'=>$row['employment_status'],
                ])->forceFill(['email_verified_at'=>now()])->save();
            }
            return count($prepared);
        });
    }

    private function rows(UploadedFile $file, array $requiredHeaders): array
    {
        $handle = fopen($file->getRealPath(), 'rb');
        $headers = fgetcsv($handle);
        if (! is_array($headers)) throw ValidationException::withMessages(['file' => 'The CSV file is empty.']);
        $headers = array_map(fn ($header) => mb_strtolower(trim((string) $header, " \t\n\r\0\x0B\xEF\xBB\xBF")), $headers);
        $missing = array_diff($requiredHeaders, $headers);
        if ($missing) throw ValidationException::withMessages(['file' => 'Missing CSV columns: '.implode(', ', $missing).'.']);
        $rows = [];
        while (($values = fgetcsv($handle)) !== false) {
            if (count(array_filter($values, fn ($value) => trim((string) $value) !== '')) === 0) continue;
            $values = array_pad($values, count($headers), '');
            $row = array_combine($headers, array_slice($values, 0, count($headers)));
            $rows[] = array_map(fn ($value) => trim((string) $value), $row);
            if (count($rows) > 2000) throw ValidationException::withMessages(['file' => 'A single import cannot exceed 2,000 rows.']);
        }
        fclose($handle);
        if (! $rows) throw ValidationException::withMessages(['file' => 'The CSV file contains no data rows.']);
        return $rows;
    }

    private function nextAdmissionNumber(School $school, int $sequence): string
    {
        do { $number = 'ADM-'.$school->id.'-'.str_pad((string) $sequence++, 4, '0', STR_PAD_LEFT); }
        while (Student::where('school_id', $school->id)->where('admission_no', $number)->exists());
        return $number;
    }

    private function nextStaffNumber(School $school, int $sequence): string
    {
        do { $number = 'STF-'.$school->id.'-'.str_pad((string) $sequence++, 4, '0', STR_PAD_LEFT); }
        while (User::where('staff_number', $number)->exists());
        return $number;
    }
}