<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Invoice;
use App\Models\SubjectResult;
use App\Models\TermResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentDataController extends Controller
{
    public function attendance(Request $request): JsonResponse
    {
        abort_unless($request->user()->tokenCan('academic:read'), 403);
        $student = $request->user()->student;
        abort_unless($student, 404);

        $records = Attendance::query()
            ->where('student_id', $student->id)
            ->orderByDesc('date')
            ->paginate(min(max($request->integer('per_page', 25), 1), 100));

        return response()->json([
            'data' => $records->getCollection()->map(fn (Attendance $record) => [
                'date' => $record->date,
                'status' => $record->status,
                'remarks' => $record->remarks,
            ]),
            'meta' => ['current_page' => $records->currentPage(), 'last_page' => $records->lastPage()],
        ]);
    }

    public function results(Request $request): JsonResponse
    {
        abort_unless($request->user()->tokenCan('academic:read'), 403);
        $student = $request->user()->student;
        abort_unless($student, 404);
        $publishedTermIds = TermResult::query()
            ->where('student_id', $student->id)
            ->where('publication_status', 'published')
            ->pluck('term_id');
        $results = SubjectResult::query()
            ->with(['subject:id,name,code', 'term:id,name,academic_year_id'])
            ->where('student_id', $student->id)
            ->whereIn('term_id', $publishedTermIds)
            ->orderByDesc('term_id')
            ->paginate(min(max($request->integer('per_page', 25), 1), 100));

        return response()->json([
            'data' => $results->getCollection()->map(fn (SubjectResult $result) => [
                'term' => $result->term?->name,
                'subject' => $result->subject?->name,
                'percentage' => $result->percentage,
                'grade' => $result->grade,
                'grade_point' => $result->grade_point,
                'status' => $result->result_status,
            ]),
            'meta' => ['current_page' => $results->currentPage(), 'last_page' => $results->lastPage()],
        ]);
    }

    public function invoices(Request $request): JsonResponse
    {
        abort_unless($request->user()->tokenCan('finance:read'), 403);
        $student = $request->user()->student;
        abort_unless($student, 404);
        $invoices = Invoice::query()
            ->with('term:id,name')
            ->where('student_id', $student->id)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->orderByDesc('issue_date')
            ->paginate(min(max($request->integer('per_page', 25), 1), 100));

        return response()->json([
            'data' => $invoices->getCollection()->map(fn (Invoice $invoice) => [
                'invoice_no' => $invoice->invoice_no,
                'term' => $invoice->term?->name,
                'issue_date' => $invoice->issue_date?->toDateString(),
                'due_date' => $invoice->due_date?->toDateString(),
                'total' => $invoice->total,
                'amount_paid' => $invoice->amount_paid,
                'balance' => $invoice->balance,
                'status' => $invoice->status,
            ]),
            'meta' => ['current_page' => $invoices->currentPage(), 'last_page' => $invoices->lastPage()],
        ]);
    }
}
