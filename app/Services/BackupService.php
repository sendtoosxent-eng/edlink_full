<?php

namespace App\Services;

use App\Models\SystemBackup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

class BackupService
{
    public function create(): SystemBackup
    {
        $connection = config('database.default');
        $configuration = config("database.connections.{$connection}", []);

        return match ($configuration['driver'] ?? null) {
            'sqlite' => $this->createSqliteBackup($connection, $configuration),
            'mysql', 'mariadb' => $this->createMysqlBackup($configuration),
            default => throw new RuntimeException('Automated backups are not supported for the configured database driver.'),
        };
    }

    private function createSqliteBackup(string $connection, array $configuration): SystemBackup
    {
        $source = $configuration['database'] ?? null;
        if (! is_string($source) || ($source !== ':memory:' && ! File::exists($source))) {
            throw new RuntimeException('SQLite database file not found.');
        }

        $path = $this->backupPath('sqlite');
        $quoted = DB::connection($connection)->getPdo()->quote($path);
        DB::connection($connection)->unprepared("VACUUM INTO {$quoted}");

        return $this->record($path);
    }

    private function createMysqlBackup(array $configuration): SystemBackup
    {
        $path = $this->backupPath('sql.gz');
        $credentialsPath = tempnam(sys_get_temp_dir(), '.mysqldump-');

        if ($credentialsPath === false) {
            throw new RuntimeException('Could not create temporary MySQL credentials file.');
        }

        $output = null;
        try {
            File::put($credentialsPath, $this->mysqlCredentials($configuration));
            @chmod($credentialsPath, 0600);

            $output = gzopen($path, 'wb6');
            if ($output === false) {
                throw new RuntimeException('Could not create compressed backup file.');
            }

            $process = new Process([
                (string) config('edlink.backup.mysql_dump_binary', 'mysqldump'),
                '--defaults-extra-file='.$credentialsPath,
                '--single-transaction',
                '--quick',
                '--routines',
                '--triggers',
                '--no-tablespaces',
                (string) ($configuration['database'] ?? ''),
            ], base_path(), null, null, 3600);

            $error = '';
            $exitCode = $process->run(function (string $type, string $buffer) use ($output, &$error): void {
                if ($type === Process::OUT) {
                    gzwrite($output, $buffer);

                    return;
                }

                $error .= substr($buffer, -4000);
            });

            gzclose($output);
            $output = null;

            if ($exitCode !== 0) {
                throw new RuntimeException('MySQL backup failed: '.trim($error ?: $process->getErrorOutput()));
            }

            return $this->record($path);
        } catch (\Throwable $error) {
            if (is_resource($output)) {
                gzclose($output);
            }
            File::delete($path);
            throw $error;
        } finally {
            File::delete($credentialsPath);
        }
    }

    public function verify(SystemBackup $backup, bool $restorationTest = false): SystemBackup
    {
        try {
            if (! File::exists($backup->path) || hash_file('sha256', $backup->path) !== $backup->checksum) {
                throw new RuntimeException('Backup checksum validation failed.');
            }

            $driver = config('database.connections.'.config('database.default').'.driver');
            if (str_ends_with($backup->path, '.sqlite') || $driver === 'sqlite') {
                $pdo = new \PDO('sqlite:'.$backup->path);
                $integrity = $pdo->query('PRAGMA integrity_check')->fetchColumn();
                if ($integrity !== 'ok') {
                    throw new RuntimeException('SQLite integrity check failed: '.$integrity);
                }
            } elseif (str_ends_with($backup->path, '.sql.gz') || in_array($driver, ['mysql', 'mariadb'], true)) {
                $this->verifyMysqlDump($backup->path);
            }

            $backup->update(['status' => 'verified', 'verified_at' => now(), 'restored_tested_at' => $restorationTest ? now() : $backup->restored_tested_at, 'failure' => null]);
        } catch (\Throwable $error) {
            $backup->update(['status' => 'failed', 'failure' => $error->getMessage()]);
            throw $error;
        }

        return $backup->fresh();
    }

    public function prune(int $keepDays = 30): int
    {
        $expired = SystemBackup::where('created_at', '<', now()->subDays(max(1, $keepDays)))->get();
        foreach ($expired as $backup) {
            $disk = config('edlink.backup.offsite_disk');
            if (filled($disk)) {
                $prefix = trim((string) config('edlink.backup.offsite_prefix'), '/');
                $remotePath = ($prefix !== '' ? $prefix.'/' : '').basename($backup->path);
                Storage::disk($disk)->delete($remotePath);
            }
            File::delete($backup->path);
            $backup->delete();
        }

        return $expired->count();
    }

    private function record(string $path): SystemBackup
    {
        if (! File::exists($path) || File::size($path) < 1) {
            throw new RuntimeException('Backup file was not created or is empty.');
        }

        $backup = SystemBackup::create(['disk' => 'local', 'path' => $path, 'size' => File::size($path), 'checksum' => hash_file('sha256', $path), 'status' => 'created']);
        $backup = $this->verify($backup);
        $this->replicateOffsite($backup);

        return $backup;
    }

    private function replicateOffsite(SystemBackup $backup): void
    {
        $disk = config('edlink.backup.offsite_disk');
        $required = (bool) config('edlink.backup.offsite_required');

        if (! filled($disk)) {
            if ($required) {
                $backup->update([
                    'status' => 'failed',
                    'failure' => 'Off-site backups are required but BACKUP_OFFSITE_DISK is not configured.',
                ]);
                throw new RuntimeException('Off-site backups are required but BACKUP_OFFSITE_DISK is not configured.');
            }

            return;
        }

        $stream = fopen($backup->path, 'rb');
        if ($stream === false) {
            throw new RuntimeException('The verified backup could not be opened for off-site replication.');
        }

        $prefix = trim((string) config('edlink.backup.offsite_prefix'), '/');
        $remotePath = ($prefix !== '' ? $prefix.'/' : '').basename($backup->path);

        try {
            Storage::disk($disk)->writeStream($remotePath, $stream, ['visibility' => 'private']);
            if (! Storage::disk($disk)->exists($remotePath)) {
                throw new RuntimeException('The off-site backup could not be verified after upload.');
            }
        } catch (\Throwable $error) {
            $backup->update(['status' => 'failed', 'failure' => 'Off-site replication failed: '.$error->getMessage()]);
            throw new RuntimeException('Off-site backup replication failed.', previous: $error);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    private function backupDirectory(): string
    {
        $directory = storage_path('app/private/backups');
        File::ensureDirectoryExists($directory);

        return $directory;
    }

    private function backupPath(string $extension): string
    {
        return $this->backupDirectory().DIRECTORY_SEPARATOR
            .'edlink-'.now()->format('Ymd-His').'-'.Str::lower(Str::random(8)).'.'.$extension;
    }

    private function mysqlCredentials(array $configuration): string
    {
        $quote = static fn (mixed $value): string => '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], (string) $value).'"';

        return "[client]\n"
            .'host='.$quote($configuration['host'] ?? '127.0.0.1')."\n"
            .'port='.$quote($configuration['port'] ?? 3306)."\n"
            .'user='.$quote($configuration['username'] ?? '')."\n"
            .'password='.$quote($configuration['password'] ?? '')."\n";
    }

    private function verifyMysqlDump(string $path): void
    {
        $input = gzopen($path, 'rb');
        if ($input === false) {
            throw new RuntimeException('Compressed MySQL backup could not be opened.');
        }

        $sample = gzread($input, 8192);
        gzclose($input);

        if (! str_contains($sample, 'MySQL dump') && ! str_contains($sample, 'MariaDB dump')) {
            throw new RuntimeException('MySQL backup header validation failed.');
        }
    }
}
