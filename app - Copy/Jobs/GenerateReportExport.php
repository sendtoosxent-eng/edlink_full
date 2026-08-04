<?php

namespace App\Jobs;

use App\Livewire\Reports;
use App\Models\ReportExport;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateReportExport implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;
    public int $timeout = 600;

    public function __construct(public int $exportId) {}

    public function handle(): void
    {
        $export = ReportExport::findOrFail($this->exportId);
        $export->update(['status'=>'processing','failure'=>null]);
        $user = User::findOrFail($export->user_id);
        Auth::guard('web')->login($user);

        $component = app(Reports::class);
        foreach ($export->filters as $key => $value) $component->{$key} = $value;
        $component->exporting = true;
        $result = $component->render()->getData()['result'];

        $stream = tmpfile();
        if ($stream === false) throw new \RuntimeException('Unable to create the export stream.');
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, $result['columns']);
        $count = 0;
        foreach ($result['rows'] as $row) {
            fputcsv($stream, collect($row)->map(fn ($value) => strip_tags((string) $value))->all());
            $count++;
        }
        if ($result['summaryLabel'] ?? null) {
            fputcsv($stream, []);
            fputcsv($stream, [$result['summaryLabel'], $result['summaryValue']]);
        }
        rewind($stream);
        $path = 'report-exports/'.$export->id.'.csv';
        Storage::disk($export->disk)->put($path, $stream);
        fclose($stream);

        $export->update(['status'=>'completed','path'=>$path,'row_count'=>$count,'completed_at'=>now(),'expires_at'=>now()->addDay()]);
        Auth::guard('web')->logout();
    }

    public function failed(?Throwable $error): void
    {
        ReportExport::whereKey($this->exportId)->update(['status'=>'failed','failure'=>str($error?->getMessage() ?? 'Export failed.')->limit(1000)]);
    }
}
