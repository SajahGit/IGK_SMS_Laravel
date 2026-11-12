<?php

namespace App\Http\Controllers\InstitutionAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\InstitutionAdmin\BulkImportRequest;
use App\Services\BulkCsvImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BulkImportController extends Controller
{
    public function index(): View
    {
        $this->authorizeImport();

        return view('InstitutionAdmin.InstitutionAdminImportsIndex');
    }

    public function store(BulkImportRequest $request, BulkCsvImportService $importer): View|RedirectResponse
    {
        $result = $importer->inspect($request->string('entity')->toString(), $request->file('file'));
        if ($request->input('mode') === 'commit') {
            if (! $result['can_import']) {
                return view('InstitutionAdmin.InstitutionAdminImportsIndex', [
                    'result' => $result,
                    'entity' => $request->input('entity'),
                ]);
            }

            $count = $importer->import($request->input('entity'), $result['valid_rows']);

            return redirect()->route('admin.imports.index')
                ->with('success', "{$count} row(s) imported. New user accounts must set their own password before signing in.");
        }

        return view('InstitutionAdmin.InstitutionAdminImportsIndex', [
            'result' => $result,
            'entity' => $request->input('entity'),
        ]);
    }

    private function authorizeImport(): void
    {
        abort_unless(auth()->user()->isAdmin() || auth()->user()->can('imports.create'), 403);
    }
}
