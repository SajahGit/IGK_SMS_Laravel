<?php

namespace App\Http\Controllers\InstitutionAdmin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateTermResults;
use App\Models\ClassSubject;
use App\Models\SubjectResult;
use App\Models\Term;
use App\Models\TermResult;
use App\Models\TermResultGeneration;
use App\Services\TermResultService;
use App\Support\AuditLogger;
use App\Support\SchoolHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TermResultController extends Controller
{
    public function index(Request $request): View
    {
        $classes = SchoolHelper::allowedClasses($request->user());
        $terms = Term::with('academicYear')->orderByDesc('start_date')->get();
        $validated = $request->validate([
            'term_id' => ['nullable', 'integer', 'exists:terms,id'],
            'class_id' => ['nullable', 'integer', 'exists:classes,id'],
        ]);
        $term = isset($validated['term_id']) ? $terms->firstWhere('id', (int) $validated['term_id']) : $terms->first();
        $classId = $validated['class_id'] ?? null;
        if ($classId) {
            abort_unless($classes->contains('id', (int) $classId), 403, 'You cannot manage results for this class.');
        }

        $results = collect();
        $subjectResults = collect();
        $generation = null;
        if ($term) {
            $query = TermResult::with(['student.user', 'schoolClass'])
                ->where('term_id', $term->id)
                ->whereIn('class_id', $classes->pluck('id'))
                ->when($classId, fn ($builder) => $builder->where('class_id', $classId))
                ->orderBy('class_id')
                ->orderBy('student_id');
            $results = $query->paginate(30)->withQueryString();
            $subjectResults = SubjectResult::with('subject')
                ->where('term_id', $term->id)
                ->whereIn('class_id', $classes->pluck('id'))
                ->when($classId, fn ($builder) => $builder->where('class_id', $classId))
                ->get()
                ->groupBy(fn (SubjectResult $result) => $result->term_id.':'.$result->class_id.':'.$result->student_id);
            if ($classId) {
                $generation = TermResultGeneration::where('term_id', $term->id)
                    ->where('class_id', $classId)
                    ->first();
            }
        }

        $offerings = $term
            ? ClassSubject::with('subject')->whereIn('class_id', $classes->pluck('id'))
                ->where(fn ($query) => $query->where('term_id', $term->id)->orWhereNull('term_id'))
                ->get()
                ->groupBy('class_id')
                ->map(fn ($items) => $items->map(fn (ClassSubject $item) => $item->subject)->unique('id')->sortBy('name'))
            : collect();

        return view('InstitutionAdmin.InstitutionAdminTermResultsIndex', compact(
            'classes', 'terms', 'term', 'classId', 'results', 'subjectResults', 'offerings', 'generation'
        ));
    }

    public function generate(Request $request, Term $term, TermResultService $service): RedirectResponse
    {
        $data = $request->validate(['class_id' => ['required', 'integer', 'exists:classes,id']]);
        $this->authorizeClass($request, (int) $data['class_id']);
        $studentCount = $service->enrolledStudentCount($term, (int) $data['class_id']);

        if ($studentCount > 200) {
            $existingGeneration = TermResultGeneration::where('term_id', $term->id)
                ->where('class_id', (int) $data['class_id'])
                ->first();
            if ($existingGeneration && in_array($existingGeneration->status, ['queued', 'running'], true)) {
                return back()->withErrors(['results' => 'A term-result generation job is already in progress.']);
            }

            TermResultGeneration::updateOrCreate(
                ['term_id' => $term->id, 'class_id' => (int) $data['class_id']],
                [
                    'initiated_by' => $request->user()->id,
                    'status' => 'queued',
                    'error' => null,
                    'started_at' => null,
                    'completed_at' => null,
                ]
            );

            GenerateTermResults::dispatch($term->id, (int) $data['class_id']);
            AuditLogger::log('queued_term_result_generation', $term, null, [
                'class_id' => (int) $data['class_id'],
                'enrolled_student_count' => $studentCount,
            ]);

            return $this->redirectToTerm($term, (int) $data['class_id'])
                ->with('success', "Term result generation queued for {$studentCount} students. Refresh this page to check its status.");
        }

        $summary = $service->generate($term, (int) $data['class_id']);
        TermResultGeneration::updateOrCreate(
            ['term_id' => $term->id, 'class_id' => (int) $data['class_id']],
            [
                'initiated_by' => $request->user()->id,
                'status' => 'completed',
                'error' => null,
                'started_at' => now(),
                'completed_at' => now(),
            ]
        );
        AuditLogger::log('generated_term_results', $term, null, [
            'class_id' => (int) $data['class_id'],
            'generated_subject_results' => $summary['generated_subject_results'],
            'missing_pairs' => count($summary['missing_pairs']),
        ]);

        return $this->redirectToTerm($term, (int) $data['class_id'])
            ->with('success', 'Term results generated; missing mark pairs: '.count($summary['missing_pairs']).'.')
            ->with('missing_result_pairs', $summary['missing_pairs']);
    }

    public function submit(Request $request, Term $term, TermResultService $service): RedirectResponse
    {
        $data = $request->validate(['class_id' => ['required', 'integer', 'exists:classes,id']]);
        $this->authorizeClass($request, (int) $data['class_id']);
        $count = $service->submitForApproval($term, (int) $data['class_id'], $request->user());
        AuditLogger::log('submitted_term_results', $term, null, [
            'class_id' => (int) $data['class_id'],
            'student_count' => $count,
        ]);

        return $this->redirectToTerm($term, (int) $data['class_id'])
            ->with('success', "{$count} term result(s) submitted for approval.");
    }

    public function publish(Request $request, Term $term, TermResultService $service): RedirectResponse
    {
        $data = $request->validate(['class_id' => ['required', 'integer', 'exists:classes,id']]);
        $this->authorizeClass($request, (int) $data['class_id']);
        $count = $service->publishClass($term, (int) $data['class_id'], $request->user());
        AuditLogger::log('published_term_results', $term, null, [
            'class_id' => (int) $data['class_id'],
            'student_count' => $count,
        ]);

        return $this->redirectToTerm($term, (int) $data['class_id'])
            ->with('success', "{$count} term result(s) published.");
    }

    public function unpublish(Request $request, Term $term, TermResultService $service): RedirectResponse
    {
        $data = $request->validate([
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);
        $this->authorizeClass($request, (int) $data['class_id']);
        $count = $service->unpublishClass($term, (int) $data['class_id'], $data['reason']);
        AuditLogger::log('unpublished_term_results', $term, null, [
            'class_id' => (int) $data['class_id'],
            'student_count' => $count,
            'reason' => $data['reason'],
        ]);

        return $this->redirectToTerm($term, (int) $data['class_id'])
            ->with('success', "{$count} term result(s) returned to draft.");
    }

    private function authorizeClass(Request $request, int $classId): void
    {
        abort_unless(
            SchoolHelper::allowedClasses($request->user())->contains('id', $classId),
            403,
            'You cannot manage results for this class.'
        );
    }

    private function redirectToTerm(Term $term, int $classId): RedirectResponse
    {
        return redirect()->route('admin.term-results.index', [
            'term_id' => $term->id,
            'class_id' => $classId,
        ]);
    }
}
