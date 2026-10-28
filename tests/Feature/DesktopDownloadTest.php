<?php

use App\Models\DesktopRelease;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function windowsRelease(array $attributes = []): DesktopRelease
{
    $checksum = hash('sha256', 'MZ-test-installer');
    $release = DesktopRelease::create(array_merge([
        'platform' => 'windows-x64', 'version' => '1.0.0',
        'path' => 'desktop-releases/'.$checksum.'/Edlink-Setup-1.0.0-x64.exe',
        'filename' => 'Edlink-Setup-1.0.0-x64.exe', 'size' => 16, 'sha256' => $checksum,
        'published_at' => now(), 'is_active' => true,
    ], $attributes));
    Storage::disk('local')->put($release->path, 'MZ-test-installer');

    return $release;
}

beforeEach(fn () => Storage::fake('local'));

it('shows the registered Windows release and serves an attachment without login', function () {
    $release = windowsRelease();
    $this->get(route('home'))->assertOk()->assertSee('Download for Windows')
        ->assertSee(route('downloads.windows'), false)->assertSee('v1.0.0')->assertSee('edlink-desktop-mockup.png');
    $this->get(route('downloads.windows'))->assertOk()
        ->assertDownload($release->filename)->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Checksum-SHA256', $release->sha256)->assertStreamedContent('MZ-test-installer');
});

it('does not expose missing, inactive or future installers', function () {
    $this->get(route('downloads.windows'))->assertNotFound();
    $this->get(route('home'))->assertOk()->assertDontSee('Download for Windows');
    $release = windowsRelease(['is_active' => false]);
    $this->get(route('downloads.windows'))->assertNotFound();
    $release->update(['is_active' => true, 'published_at' => now()->addDay()]);
    $this->get(route('downloads.windows'))->assertNotFound();
    $release->update(['published_at' => now()]);
    Storage::disk('local')->delete($release->path);
    $this->get(route('downloads.windows'))->assertNotFound();
    $this->get(route('home'))->assertOk()->assertDontSee('Download for Windows');
});

it('serves the latest published release and rejects storage paths outside releases', function () {
    windowsRelease(['published_at' => now()->subDay()]);
    $release = windowsRelease(['version' => '1.1.0', 'filename' => 'Edlink-Setup-1.1.0-x64.exe']);
    $this->get(route('downloads.windows'))->assertDownload('Edlink-Setup-1.1.0-x64.exe');
    $release->update(['path' => 'private-records.txt']);
    Storage::disk('local')->put('private-records.txt', 'not public');
    $this->get(route('downloads.windows'))->assertNotFound();
});

it('registers a real file with its checksum and rejects invalid installers', function () {
    $file = tempnam(sys_get_temp_dir(), 'edlink-release-').'.exe';
    try {
        file_put_contents($file, 'MZ-test-installer');
        $this->artisan('edlink:publish-desktop', ['file' => $file, 'version' => '1.2.0'])->assertSuccessful();
        $release = DesktopRelease::firstOrFail();
        expect($release->sha256)->toBe(hash_file('sha256', $file));
        expect($release->sha512)->toBe(base64_encode(hash_file('sha512', $file, true)));
        expect($release->size)->toBe(filesize($file));
        Storage::disk('local')->assertExists($release->path);
        file_put_contents($file, 'MZ different installer');
        $this->artisan('edlink:publish-desktop', ['file' => $file, 'version' => '1.2.0'])->assertFailed();
        expect($release->fresh()->sha256)->toBe(hash('sha256', 'MZ-test-installer'));
        $this->artisan('edlink:publish-desktop', ['file' => $file, 'version' => '../bad'])->assertFailed();
        file_put_contents($file, 'not an executable');
        $this->artisan('edlink:publish-desktop', ['file' => $file, 'version' => '2.0.0'])->assertFailed();
        expect(DesktopRelease::count())->toBe(1);
    } finally {
        @unlink($file);
        @unlink(substr($file, 0, -4));
    }
});
