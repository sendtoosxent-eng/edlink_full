<?php

namespace App\Http\Controllers;

use App\Models\SchoolSetting;
use App\Services\AccountingAuditReportService;
use App\Services\SimpleXlsxExportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AccountingExportController extends Controller
{
    public function __invoke(Request $request, string $report, AccountingAuditReportService $reports, SimpleXlsxExportService $xlsx): Response
    {
        abort_unless($request->user()->hasPermission('accounting.reports.export'), 403);
        abort_unless(isset(AccountingAuditReportService::REPORTS[$report]), 404);
        $filters = $request->validate(['from' => 'nullable|date', 'to' => 'nullable|date|after_or_equal:from', 'format' => 'nullable|in:pdf,xlsx']);
        $format = $filters['format'] ?? 'pdf';
        unset($filters['format']);
        $data = $reports->build($request->user()->school, $report, $filters);
        $data['currency'] = strtoupper((string) SchoolSetting::getValue($request->user()->school_id, 'accounting_currency', 'UGX'));
        $filename = $report.'-'.now()->format('Ymd-His').'.'.$format;

        if ($format === 'xlsx') {
            $path = $xlsx->make($data);
            return response()->download($path, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
        }

        $orientation = in_array($report, ['general-ledger', 'journal-register', 'expense-analysis', 'audit-trail'], true) ? 'landscape' : 'portrait';
        return Pdf::loadView('accounting.reports.audit-report', $data)->setPaper('a4', $orientation)->download($filename);
    }
}
