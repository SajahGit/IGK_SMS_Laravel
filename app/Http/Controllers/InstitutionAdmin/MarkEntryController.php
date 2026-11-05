<?php

namespace App\Http\Controllers\InstitutionAdmin;

use App\Http\Controllers\Controller;
use App\Models\ExamSubject;
use App\Services\MarksEntryService;
use Illuminate\Http\Request;

class MarkEntryController extends Controller
{
    public function store(Request $request, ExamSubject $examSubject)
    {
        $this->authorize('marks.update');
        $service = new MarksEntryService();
        $service->bulkSave($examSubject, $request->input('marks', []));
        return back()->with('success', 'Marks saved successfully.');
    }
}
