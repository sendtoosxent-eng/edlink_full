<?php

namespace App\Services;

use App\Models\School;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class SchoolBackupService
{
    /**
     * Create a portable, compressed export containing only one school's rows.
     *
     * @return array{path: string, filename: string, checksum: string, row_counts: array<string, int>}
     */
    public function create(School $school): array
    {
        $directory = storage_path('app/private/exports');
        File::ensureDirectoryExists($directory);

        $filename = 'edlink-school-'.$school->school_number.'-'.now()->format('Y-m-d_H-i-s').'.json.gz';
        $path = $directory.DIRECTORY_SEPARATOR.$filename;
        $output = gzopen($path, 'wb6');

        if ($output === false) {
            throw new RuntimeException('The school backup archive could not be created.');
        }

        $rowCounts = [];

        try {
            $header = json_encode([
                'format' => 'edlink-school-backup',
                'version' => 1,
                'exported_at' => now()->toIso8601String(),
                'school' => [
                    'id' => $school->id,
                    'school_number' => $school->school_number,
                    'name' => $school->name,
                    'subdomain' => $school->subdomain,
                ],
                'database_driver' => DB::connection()->getDriverName(),
                'migration' => DB::table('migrations')->max('migration'),
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
            $this->write($output, substr($header, 0, -1).',"tables":{');

            $firstTable = true;
            foreach ($this->queries($school) as $table => $query) {
                if (! $firstTable) {
                    $this->write($output, ',');
                }
                $firstTable = false;

                $this->write($output, json_encode($table, JSON_THROW_ON_ERROR).' : [');
                $firstRow = true;
                $rowCounts[$table] = 0;

                foreach ($query->cursor() as $row) {
                    if (! $firstRow) {
                        $this->write($output, ',');
                    }
                    $firstRow = false;
                    $this->write($output, json_encode(
                        (array) $row,
                        JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PRESERVE_ZERO_FRACTION
                    ));
                    $rowCounts[$table]++;
                }

                $this->write($output, ']');
            }

            $this->write($output, '},"row_counts":'.json_encode($rowCounts, JSON_THROW_ON_ERROR).'}');
            gzclose($output);
            $output = null;

            if (! File::exists($path) || File::size($path) < 1) {
                throw new RuntimeException('The school backup archive is empty.');
            }

            return [
                'path' => $path,
                'filename' => $filename,
                'checksum' => hash_file('sha256', $path),
                'row_counts' => $rowCounts,
            ];
        } catch (Throwable $error) {
            if (is_resource($output)) {
                gzclose($output);
            }
            File::delete($path);
            throw $error;
        }
    }

    /** @return array<string, Builder> */
    private function queries(School $school): array
    {
        $queries = ['schools' => DB::table('schools')->where('id', $school->id)];

        foreach ($this->tableNames() as $table) {
            if ($table !== 'schools' && Schema::hasColumn($table, 'school_id')) {
                $queries[$table] = DB::table($table)->where('school_id', $school->id);
            }
        }

        $students = DB::table('students')->select('id')->where('school_id', $school->id);
        $users = DB::table('users')->select('id')->where('school_id', $school->id);
        $userEmails = DB::table('users')->select('email')->where('school_id', $school->id)->whereNotNull('email');
        $exams = DB::table('exams')->select('id')->where('school_id', $school->id);
        $papers = DB::table('exam_papers')->select('id')->whereIn('exam_id', $exams);
        $assignments = DB::table('homework_assignments')->select('id')->where('school_id', $school->id);
        $budgets = DB::table('accounting_budgets')->select('id')->where('school_id', $school->id);

        $this->addIfTableExists($queries, 'student_guardians', fn () => DB::table('student_guardians')->whereIn('student_id', $students));
        $this->addIfTableExists($queries, 'exam_papers', fn () => DB::table('exam_papers')->whereIn('exam_id', $exams));
        $this->addIfTableExists($queries, 'exam_marks', fn () => DB::table('exam_marks')->whereIn('exam_paper_id', $papers));
        $this->addIfTableExists($queries, 'exam_paper_submissions', fn () => DB::table('exam_paper_submissions')->whereIn('exam_paper_id', $papers));
        $this->addIfTableExists($queries, 'homework_submissions', fn () => DB::table('homework_submissions')->whereIn('homework_assignment_id', $assignments));
        $this->addIfTableExists($queries, 'accounting_budget_lines', fn () => DB::table('accounting_budget_lines')->whereIn('accounting_budget_id', $budgets));
        $this->addIfTableExists($queries, 'sessions', fn () => DB::table('sessions')->whereIn('user_id', $users));
        $this->addIfTableExists($queries, 'password_reset_tokens', fn () => DB::table('password_reset_tokens')->whereIn('email', $userEmails));

        if (Schema::hasTable('personal_access_tokens')) {
            $queries['personal_access_tokens'] = DB::table('personal_access_tokens')
                ->where('tokenable_type', 'App\\Models\\User')
                ->whereIn('tokenable_id', $users);
        }
        if (Schema::hasTable('model_has_roles')) {
            $queries['model_has_roles'] = DB::table('model_has_roles')
                ->where('model_type', 'App\\Models\\User')
                ->whereIn('model_id', $users);
        }
        if (Schema::hasTable('model_has_permissions')) {
            $queries['model_has_permissions'] = DB::table('model_has_permissions')
                ->where('model_type', 'App\\Models\\User')
                ->whereIn('model_id', $users);
        }
        if ($school->school_group_id && Schema::hasTable('school_groups')) {
            $queries['school_groups'] = DB::table('school_groups')->where('id', $school->school_group_id);
        }

        ksort($queries);

        return $queries;
    }

    /** @return list<string> */
    private function tableNames(): array
    {
        return collect(Schema::getTables())
            ->map(fn (array $table): string => (string) ($table['name'] ?? ''))
            ->filter()
            ->values()
            ->all();
    }

    /** @param array<string, Builder> $queries */
    private function addIfTableExists(array &$queries, string $table, callable $query): void
    {
        if (Schema::hasTable($table)) {
            $queries[$table] = $query();
        }
    }

    /** @param resource $output */
    private function write($output, string $contents): void
    {
        if (gzwrite($output, $contents) === false) {
            throw new RuntimeException('The school backup archive could not be written.');
        }
    }
}
