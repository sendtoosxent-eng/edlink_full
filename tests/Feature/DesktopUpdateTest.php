<?php

use App\Models\DesktopRelease;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function updateRelease(string $version, array $changes = []): DesktopRelease
{
    $bytes = 'MZ installer '.$version;
    $checksum = hash('sha256', $bytes);
    $release = DesktopRelease::create(array_merge([
        'platform' => 'windows-x64', 'version' => $version,
        'path' => 'desktop-releases/'.$checksum.'/Edlink-Setup-'.$version.'-x64.exe',
        'filename' => 'Edlink-Setup-'.$version.'-x64.exe', 'size' => strlen($bytes),
        'sha256' => $checksum, 'sha512' => base64_encode(hash('sha512', $bytes, true)),
        'published_at' => now(), 'is_active' => true,
    ], $changes));
    Storage::disk('local')->put($release->path, $bytes);

    return $release;
}

beforeEach(fn () => Storage::fake('local'));

it('serves an updater-compatible manifest with the stored SHA512 and immutable URL', function () {
    $release = updateRelease('1.0.1');
    $response = $this->get(route('updates.windows.manifest'))->assertOk()->assertHeader('Content-Type', 'application/yaml');
    $manifest = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
    expect($manifest['version'])->toBe('1.0.1');
    expect($manifest['files'][0])->toBe([
        'url' => 'files/'.$release->sha256.'/'.$release->filename,
        'sha512' => $release->sha512, 'size' => $release->size,
    ]);
    $this->get('/updates/windows/'.$manifest['files'][0]['url'])->assertOk()->assertDownload($release->filename);
});

it('keeps an in-flight update URL pinned when a newer version is published', function () {
    $first = updateRelease('1.0.1', ['published_at' => now()->subHour()]);
    $latest = updateRelease('1.0.2');
    $this->get(route('updates.windows.manifest'))->assertOk()->assertSee('1.0.2');
    $this->get('/updates/windows/files/'.$first->sha256.'/'.$first->filename)->assertDownload($first->filename);
    $this->get('/updates/windows/files/'.$first->sha256.'/'.$latest->filename)->assertNotFound();
});

it('does not offer missing, inactive, future or legacy releases for updating', function () {
    $this->get(route('updates.windows.manifest'))->assertNotFound();
    $release = updateRelease('1.0.0', ['sha512' => null]);
    $this->get(route('updates.windows.manifest'))->assertNotFound();
    $release->update(['sha512' => 'checksum', 'is_active' => false]);
    $this->get(route('updates.windows.manifest'))->assertNotFound();
    $this->get('/updates/windows/files/'.$release->sha256.'/'.$release->filename)->assertNotFound();
    $release->update(['is_active' => true, 'published_at' => now()->addDay()]);
    $this->get(route('updates.windows.manifest'))->assertNotFound();
    $release->update(['published_at' => now()]);
    Storage::disk('local')->delete($release->path);
    $this->get(route('updates.windows.manifest'))->assertNotFound();
});

it('supports range requests and rejects traversal paths', function () {
    $release = updateRelease('1.0.1');
    $this->get('/updates/windows/files/'.$release->sha256.'/'.$release->filename, ['Range' => 'bytes=0-1'])
        ->assertStatus(206)->assertHeader('Content-Range', 'bytes 0-1/'.$release->size);
    $release->update(['path' => 'private-records.txt']);
    Storage::disk('local')->put('private-records.txt', 'secret');
    $this->get('/updates/windows/files/'.$release->sha256.'/'.$release->filename)->assertNotFound();
});
