<?php

namespace App\Console\Commands;

use App\Http\Controllers\AndroidDownloadController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class PublishAndroidRelease extends Command
{
    protected $signature = 'edlink:publish-android {file : Path to the Android APK}';

    protected $description = 'Store an Android APK privately for the landing page download';

    public function handle(): int
    {
        $file = $this->argument('file');
        $zip = new ZipArchive;
        if (! is_file($file) || ! is_readable($file) || strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'apk') {
            $this->error('Provide a readable .apk installer.');

            return self::FAILURE;
        }
        if ($zip->open($file) !== true) {
            $this->error('The file is not an Android APK.');

            return self::FAILURE;
        }
        $valid = $zip->locateName('AndroidManifest.xml') !== false;
        $zip->close();
        if (! $valid) {
            $this->error('The APK is missing AndroidManifest.xml.');

            return self::FAILURE;
        }
        $stream = fopen($file, 'rb');
        try {
            $stored = Storage::disk('local')->put(AndroidDownloadController::PATH, $stream);
        } finally {
            fclose($stream);
        }
        if (! $stored) {
            $this->error('The APK could not be stored.');

            return self::FAILURE;
        }
        $this->info('Android APK published. Download: '.route('downloads.android'));

        return self::SUCCESS;
    }
}
