<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Mark;
use App\Services\GradingService;

class PublishResultController extends Controller
{
    public function __invoke(Exam $exam, GradingService $grading)
    {
        abort_unless($exam->created_by === auth()->id(), 403, 'Only the creator of this exam can publish it.');

        if ($exam->isPublished()) {
            return back()->with('error', 'This exam is already published.');
        }

        $hasMarks = Mark::whereIn('exam_subject_id', $exam->examSubjects()->pluck('id'))->exists();
        if (! $hasMarks) {
            return back()->with('error', 'Enter marks for at least one subject before publishing.');
        }

        $grading->publish($exam);

        return back()->with('success', 'Results published. Students and parents can see them now. Marks are locked.');
    }
}
