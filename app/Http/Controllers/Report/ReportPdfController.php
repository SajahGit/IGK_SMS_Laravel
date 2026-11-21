<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ReportPdfController extends Controller
{
    private const ROW_LIMIT = 500;

    private const TABLE_COLLECTIONS = [
        'students' => 'students',
        'enrollment' => 'rows',
        'attendance' => 'rows',
        'attendance-summary' => 'rows',
        'class-attendance' => 'summary',
        'results' => 'rows',
        'class-result' => 'rows',
        'subject-performance' => 'subjects',
        'fee-collection' => 'payments',
        'defaulters' => 'invoices',
        'student-ledger' => 'ledger',
    ];

    private const FILTER_COLLECTIONS = [
        'students' => ['classes', 'programs', 'academicYears'],
        'enrollment' => ['classes', 'subjects', 'programs', 'terms'],
        'attendance' => ['classes', 'subjects', 'teachers'],
        'attendance-summary' => ['classes', 'terms', 'subjects', 'students'],
        'class-attendance' => ['classes', 'terms', 'subjects'],
        'class-result' => ['classes', 'terms', 'programs'],
        'results' => ['exams'],
        'subject-performance' => ['classes', 'terms', 'programs', 'subjectOptions'],
        'fee-collection' => ['terms', 'programs'],
        'defaulters' => ['classes', 'terms', 'programs'],
        'student-ledger' => ['students'],
    ];

    private const REPORTS = [
        'students' => StudentReportController::class,
        'enrollment' => EnrollmentRegisterReportController::class,
        'attendance' => AttendanceReportController::class,
        'attendance-summary' => AttendanceSummaryPerStudentReportController::class,
        'class-attendance' => ClassAttendanceSummaryReportController::class,
        'results' => ResultReportController::class,
        'class-result' => ClassResultSummaryReportController::class,
        'subject-performance' => SubjectPerformanceReportController::class,
        'fee-collection' => FeeCollectionReportController::class,
        'defaulters' => DefaultersReportController::class,
        'student-ledger' => StudentFinancialLedgerReportController::class,
    ];

    public function download(Request $request, string $report): Response
    {
        abort_unless(isset(self::REPORTS[$report]), 404);

        $previousExecutionLimit = (int) ini_get('max_execution_time');
        ini_set('memory_limit', '512M');
        set_time_limit(120);

        try {
            $request->attributes->set('pdf_export_limit', self::ROW_LIMIT);

            $reportController = app(self::REPORTS[$report]);
            $reportView = $report === 'students'
                ? $reportController->pdf($request)
                : $reportController->index($request);
            abort_unless($reportView instanceof View, 500, 'The report did not return a view.');

            $this->limitViewRows($reportView, $report);
            $reportView->with('pdfExport', true);
            $html = $reportView->render();
            $pdfStyles = <<<'CSS'
<style>
    * { box-sizing: border-box !important; }
    @page { size: A4 landscape; margin: 8mm 10mm; }
    body { background: #fff !important; color: #0f172a !important; font: 9px DejaVu Sans, Arial, sans-serif !important; margin: 0 !important; padding: 0 !important; }
    body::before, body::after, .bg-orb-3 { display: none !important; }
    .sidebar, .sidebar-backdrop, .topbar, .app-footer, footer, .no-print, button, input, select, textarea, form, .sticky-top, .sticky-bottom, .sticky-footer, .pagination, .alert { display: none !important; }
    .app-shell, .app-main, .content { display: block !important; position: static !important; width: 100% !important; margin: 0 !important; padding: 0 !important; }
    .card { box-shadow: none !important; border: 0 !important; background: transparent !important; }
    .stat-card { display: inline-block !important; width: 32% !important; vertical-align: top !important; border: 1px solid #94a3b8 !important; padding: 6px !important; }
    .stat-icon { display: none !important; }
    .stat-label { color: #334155 !important; font-weight: bold !important; }
    .badge { display: inline-block !important; color: #0f172a !important; background: #e2e8f0 !important; border: 1px solid #64748b !important; padding: 2px 5px !important; }
    .text-primary, .text-info, .text-success, .text-danger, .text-warning, .text-secondary { color: #0f172a !important; }
    .table-responsive { overflow: visible !important; width: 100% !important; display: block !important; }
    table { width: 100% !important; min-width: 0 !important; border-collapse: collapse !important; font-size: 8.5px !important; margin-top: 8px !important; }
    th, td { min-width: 0 !important; border: 1px solid #94a3b8 !important; padding: 4px 6px !important; color: #0f172a !important; }
    th { background: #f1f5f9 !important; font-weight: bold !important; text-align: left; }
    td, th { word-break: normal !important; overflow-wrap: break-word !important; }
    tr { page-break-inside: avoid; }
    thead { display: table-header-group; }
    tfoot { display: table-footer-group; }
    .print-nowrap { white-space: nowrap !important; }
    a { color: #0f172a !important; text-decoration: none !important; }
</style>
CSS;
            $html = str_replace('</head>', $pdfStyles.'</head>', $html);

            return Pdf::setOptions(['isRemoteEnabled' => false])
                ->loadHTML($html)
                ->setPaper('a4', 'landscape')
                ->download($report.'-report-'.today()->toDateString().'.pdf');
        } finally {
            set_time_limit($previousExecutionLimit);
        }
    }

    private function limitViewRows(View $view, string $report): void
    {
        $key = self::TABLE_COLLECTIONS[$report] ?? null;
        $data = $view->getData();
        $rows = $key === null ? null : ($data[$key] ?? null);

        if ($rows instanceof Collection && $rows->count() > self::ROW_LIMIT) {
            $view->with($key, $rows->take(self::ROW_LIMIT)->values());
        } elseif (is_array($rows) && count($rows) > self::ROW_LIMIT) {
            $view->with($key, array_slice($rows, 0, self::ROW_LIMIT));
        }

        foreach (self::FILTER_COLLECTIONS[$report] ?? [] as $filterKey) {
            $view->with($filterKey, collect());
        }
    }

    public function downloadStudentLedger(Request $request): Response
    {
        return $this->download($request, 'student-ledger');
    }
}
