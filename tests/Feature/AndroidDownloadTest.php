<?php

use App\Http\Controllers\AndroidDownloadController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(fn () => Storage::fake('local'));

it('serves the published APK without login and enables the landing page button', function () {
    Storage::disk('local')->put(AndroidDownloadController::PATH, 'test-apk');
    $this->get(route('home'))->assertOk()
        ->assertSee('href="'.route('downloads.android').'"', false)
        ->assertSee('download="Edlink.apk"', false);
    $this->get(route('downloads.android'))->assertOk()->assertDownload('Edlink.apk')
        ->assertHeader('Content-Type', 'application/vnd.android.package-archive')
        ->assertHeader('X-Content-Type-Options', 'nosniff')->assertStreamedContent('test-apk');
});

it('does not advertise or serve a missing APK', function () {
    $this->get(route('downloads.android'))->assertNotFound();
    $this->get(route('home'))->assertOk()
        ->assertDontSee('href="'.route('downloads.android').'"', false);
});

it('publishes APK archives and rejects invalid files', function () {
    $file = sys_get_temp_dir().'/edlink-test-'.bin2hex(random_bytes(8)).'.apk';
    try {
        file_put_contents($file, 'invalid');
        $this->artisan('edlink:publish-android', ['file' => $file])->assertFailed();
        Storage::disk('local')->assertMissing(AndroidDownloadController::PATH);
        $zip = new ZipArchive;
        $zip->open($file, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('AndroidManifest.xml', 'test-manifest');
        $zip->close();
        $this->artisan('edlink:publish-android', ['file' => $file])->assertSuccessful();
        expect(Storage::disk('local')->get(AndroidDownloadController::PATH))->toBe(file_get_contents($file));
    } finally {
        @unlink($file);
    }
});
