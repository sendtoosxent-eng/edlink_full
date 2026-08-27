<?php

use App\Models\School;
use App\Models\User;
use App\Services\AccountingAuditReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('builds every tenant-scoped audit report and exports pdf and excel', function () {
    $school = School::create(['name' => 'Audit Reports School', 'slug' => 'audit-reports-school']);
    $admin = User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);

    foreach (array_keys(AccountingAuditReportService::REPORTS) as $report) {
        $data = app(AccountingAuditReportService::class)->build($school, $report, ['from' => now()->startOfYear()->toDateString(), 'to' => now()->toDateString()]);
        expect($data['title'])->not->toBeEmpty()->and($data['columns'])->not->toBeEmpty();
    }

    $this->actingAs($admin)->get(route('accounting.exports', ['report' => 'trial-balance', 'format' => 'pdf']))
        ->assertOk()->assertHeader('content-type', 'application/pdf');

    $response = $this->actingAs($admin)->get(route('accounting.exports', ['report' => 'trial-balance', 'format' => 'xlsx']))
        ->assertOk()->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    $path = tempnam(sys_get_temp_dir(), 'verify-xlsx-');
    file_put_contents($path, $response->streamedContent());
    $zip = new \ZipArchive;
    expect($zip->open($path))->toBeTrue()->and($zip->locateName('xl/worksheets/sheet1.xml'))->not->toBeFalse();
    $zip->close();
    unlink($path);
});
