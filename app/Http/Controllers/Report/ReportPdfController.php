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

        ini_set('memory_limit', '512M');
        set_time_limit(120);
        $request->attributes->set('pdf_export_limit', self::ROW_LIMIT);

        $reportController = app(self::REPORTS[$report]);
        $reportView = $report === 'students'
            ? $reportController->pdf($request)
            : $reportController->index($request);
        abort_unless($reportView instanceof View, 500, 'The report did not return a view.');

        $this->limitViewRows($reportView, $report);
        $html = $reportView->render();
        $pdfStyles = <<<'CSS'
<style>
    @page { size: A4 landscape; margin: 10mm; }
    body { background: #fff !important; color: #111 !important; font: 10px Arial, sans-serif !important; }
    .sidebar, .sidebar-backdrop, .topbar, .app-footer, footer, .no-print, button, input, select, textarea, form[method="GET"], .sticky-top, .sticky-bottom, .sticky-footer, .pagination { display: none !important; }
    .app-shell, .app-main, .content { display: block !important; position: static !important; width: auto !important; margin: 0 !important; padding: 0 !important; }
    .card { box-shadow: none !important; border: 0 !important; }
    table { width: 100% !important; border-collapse: collapse !important; font-size: 9px !important; }
    th, td { border: 1px solid #555 !important; padding: 4px !important; color: #111 !important; }
    td, th { word-break: normal !important; overflow-wrap: break-word !important; }
    .print-nowrap { white-space: nowrap !important; }
    .pdf-export-notice { border: 1px solid #b6c2d1; background: #f1f5f9; color: #1f2937; padding: 8px; margin-bottom: 10px; }
    a { color: #111 !important; text-decoration: none !important; }
</style>
CSS;
        $html = str_replace(
            '<main class="content">',
            '<main class="content"><div class="pdf-export-notice">Showing first 500 records. Please apply filters (Class, Term, or Date Range) or use CSV Export for full datasets.</div>',
            $html
        );
        $html = str_replace('</head>', $pdfStyles.'</head>', $html);

        return Pdf::setOptions(['isRemoteEnabled' => false])
            ->loadHTML($html)
            ->setPaper('a4', 'landscape')
            ->download($report.'-report-'.today()->toDateString().'.pdf');
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
