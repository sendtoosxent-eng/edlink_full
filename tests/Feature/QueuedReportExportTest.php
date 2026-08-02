<?php

use App\Jobs\GenerateReportExport;
use App\Livewire\Reports;
use App\Models\ReportExport;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('queues a report export without generating it in the web request', function () {
    Queue::fake();
    $school=School::create(['name'=>'Export School','slug'=>'export-school']);
    $admin=User::factory()->create(['school_id'=>$school->id,'role'=>'admin']);

    Livewire::actingAs($admin)->test(Reports::class)->call('queueExport');

    $export=ReportExport::firstOrFail();
    expect($export->status)->toBe('queued')->and($export->filters['report'])->toBe('student_register');
    Queue::assertPushed(GenerateReportExport::class,fn($job)=>$job->exportId===$export->id);
});

it('generates a private CSV and prevents another school from downloading it', function () {
    Storage::fake('local');
    $school=School::create(['name'=>'Export Owner','slug'=>'export-owner']);
    $other=School::create(['name'=>'Other School','slug'=>'other-export-school']);
    $admin=User::factory()->create(['school_id'=>$school->id,'role'=>'admin']);
    $outsider=User::factory()->create(['school_id'=>$other->id,'role'=>'admin']);
    Student::create(['school_id'=>$school->id,'name'=>'CSV Learner','status'=>'active']);
    $export=ReportExport::create([
        'school_id'=>$school->id,'user_id'=>$admin->id,'report'=>'student_register','status'=>'queued','disk'=>'local','filename'=>'students.csv',
        'filters'=>['report'=>'student_register','schoolScope'=>(string)$school->id,'termId'=>'','dateFrom'=>'','dateTo'=>'','gender'=>'','categoryId'=>'','debtorStatus'=>''],
    ]);

    (new GenerateReportExport($export->id))->handle();
    $export->refresh();
    expect($export->status)->toBe('completed')->and($export->row_count)->toBe(1);
    Storage::disk('local')->assertExists($export->path);

    $this->actingAs($outsider)->get(route('reports.exports.download',$export))->assertNotFound();
    $this->actingAs($admin)->get(route('reports.exports.download',$export))->assertOk()->assertDownload('students.csv');
});
