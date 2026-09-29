<?php
namespace App\Console\Commands;
use App\Services\BackupService;
use Illuminate\Console\Command;
class CreateVerifiedBackup extends Command { protected $signature='edlink:backup {--restore-test} {--keep-days=30}'; protected $description='Create, verify, restoration-test, and prune Edlink database backups.'; public function handle(BackupService $backups):int{try{$backup=$backups->create();if($this->option('restore-test'))$backup=$backups->verify($backup,true);$pruned=$backups->prune((int)$this->option('keep-days'));$this->info("Verified backup {$backup->path}; pruned {$pruned}.");return self::SUCCESS;}catch(\Throwable $e){report($e);$this->error($e->getMessage());return self::FAILURE;}} }
