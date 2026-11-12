<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ReportPdfController extends Controller
{
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

        $reportView = app(self::REPORTS[$report])->index($request);
        abort_unless($reportView instanceof View, 500, 'The report did not return a view.');

        $html = $reportView->render();
        $pdfStyles = <<<'CSS'
<style>
    @page { size: A4 landscape; margin: 12mm; }
    body { background: #fff !important; color: #111 !important; font: 10px Arial, sans-serif !important; }
    .sidebar, .sidebar-backdrop, .topbar, .app-footer, .no-print { display: none !important; }
    .app-shell, .app-main, .content { display: block !important; position: static !important; width: auto !important; margin: 0 !important; padding: 0 !important; }
    .card { box-shadow: none !important; border: 0 !important; }
    table { width: 100% !important; border-collapse: collapse !important; font-size: 9px !important; }
    th, td { border: 1px solid #555 !important; padding: 4px !important; color: #111 !important; }
    a { color: #111 !important; text-decoration: none !important; }
</style>
CSS;
        $html = str_replace('</head>', $pdfStyles.'</head>', $html);

        return Pdf::setOptions(['isRemoteEnabled' => false])
            ->loadHTML($html)
            ->setPaper('a4', 'landscape')
            ->download($report.'-report-'.today()->toDateString().'.pdf');
    }

    public function downloadStudentLedger(Request $request): Response
    {
        return $this->download($request, 'student-ledger');
    }
}
