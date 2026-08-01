<?php

namespace App\Services;

use App\Models\SystemBackup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

class BackupService
{
    public function create(): SystemBackup
    {
        $connection = config('database.default');
        $configuration = config("database.connections.{$connection}");
        if (($configuration['driver'] ?? null) !== 'sqlite') throw new RuntimeException('Automated database snapshots currently require SQLite.');
        $source = $configuration['database'];
        if (! is_string($source) || ! File::exists($source)) throw new RuntimeException('Database file not found.');
        $directory = storage_path('app/private/backups');
        File::ensureDirectoryExists($directory);
        $path = $directory.DIRECTORY_SEPARATOR.'edlink-'.now()->format('Ymd-His').'.sqlite';
        $quoted = DB::connection($connection)->getPdo()->quote($path);
        DB::connection($connection)->unprepared("VACUUM INTO {$quoted}");
        $backup = SystemBackup::create(['path'=>$path,'size'=>File::size($path),'checksum'=>hash_file('sha256',$path),'status'=>'created']);
        return $this->verify($backup);
    }

    public function verify(SystemBackup $backup, bool $restorationTest = false): SystemBackup
    {
        try {
            if (! File::exists($backup->path) || hash_file('sha256',$backup->path) !== $backup->checksum) throw new RuntimeException('Backup checksum validation failed.');
            $pdo = new \PDO('sqlite:'.$backup->path);
            $integrity = $pdo->query('PRAGMA integrity_check')->fetchColumn();
            if ($integrity !== 'ok') throw new RuntimeException('SQLite integrity check failed: '.$integrity);
            $backup->update(['status'=>'verified','verified_at'=>now(),'restored_tested_at'=>$restorationTest?now():$backup->restored_tested_at,'failure'=>null]);
        } catch (\Throwable $error) {
            $backup->update(['status'=>'failed','failure'=>$error->getMessage()]);
            throw $error;
        }
        return $backup->fresh();
    }

    public function prune(int $keepDays = 30): int
    {
        $expired = SystemBackup::where('created_at','<',now()->subDays($keepDays))->get();
        foreach ($expired as $backup) { File::delete($backup->path); $backup->delete(); }
        return $expired->count();
    }
}
