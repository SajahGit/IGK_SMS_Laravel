<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentAcademicContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->runningInConsole()) {
            $year = \App\Models\AcademicYear::where('is_current', true)->first();
            $term = \App\Models\Term::where('is_current', true)->first();
            view()->share('currentAcademicYear', $year);
            view()->share('currentTerm', $term);
        }
        return $next($request);
    }
}
