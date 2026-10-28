<?php

namespace App\Console\Commands;

use App\Models\DesktopRelease;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PublishDesktopRelease extends Command
{
    protected $signature = 'edlink:publish-desktop {file : Path to the Windows installer} {version : Release version, for example 1.0.0}';

    protected $description = 'Store a Windows installer privately and register its public download in the database';

    public function handle(): int
    {
        $file = $this->argument('file');
        $version = $this->argument('version');
        if (! preg_match('/\A\d+\.\d+\.\d+(?:-[a-zA-Z0-9.-]+)?\z/', $version) || strlen($version) > 50) {
            $this->error('Use a release version such as 1.0.0.');

            return self::FAILURE;
        }
        if (! is_file($file) || ! is_readable($file) || strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'exe') {
            $this->error('Provide a readable .exe installer.');

            return self::FAILURE;
        }
        $stream = fopen($file, 'rb');
        if (fread($stream, 2) !== 'MZ') {
            fclose($stream);
            $this->error('The file is not a Windows executable.');

            return self::FAILURE;
        }
        rewind($stream);
        $checksum = hash_file('sha256', $file);
        $existing = DesktopRelease::where('platform', 'windows-x64')->where('version', $version)->first();
        if ($existing && $existing->sha256 !== $checksum) {
            fclose($stream);
            $this->error('This version already identifies a different installer. Increment the app version before publishing.');

            return self::FAILURE;
        }
        $filename = 'Edlink-Setup-'.$version.'-x64.exe';
        $path = 'desktop-releases/'.$checksum.'/'.$filename;
        try {
            if (! Storage::disk('local')->put($path, $stream)) {
                $this->error('The installer could not be stored. No release was published.');

                return self::FAILURE;
            }
        } finally {
            fclose($stream);
        }
        DesktopRelease::updateOrCreate(['platform' => 'windows-x64', 'version' => $version], [
            'path' => $path, 'filename' => $filename, 'size' => filesize($file),
            'sha256' => $checksum, 'sha512' => base64_encode(hash_file('sha512', $file, true)),
            'published_at' => $existing?->published_at ?? now(), 'is_active' => true,
        ]);
        $this->info('Windows '.$version.' is registered. Download: '.url('/downloads/edlink-windows'));

        return self::SUCCESS;
    }
}
