<?php

namespace App\Http\Controllers\InstitutionAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResultPublicationController extends Controller
{
    public function publish(Request $request)
    {
        $term = \App\Models\Term::findOrFail($request->term_id);
        $service = new \App\Services\ResultPublicationService();
        $count = $service->publish($term, (int) $request->school_class_id);
        return back()->with('success', $count.' result(s) published.');
    }
}
