@extends('Layouts.LayoutApp')
@section('title', 'Bulk import')
@section('content')
<div class="mb-3">
    <h3 class="mb-0">Bulk CSV import</h3>
    <small class="text-muted">Validate the complete file first. Imports are all-or-nothing and capped at 5,000 rows.</small>
</div>
@include('Layouts.LayoutErrors')

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.imports.store') }}" enctype="multipart/form-data" class="row g-3">
            @csrf
            <div class="col-md-4">
                <label for="entity" class="form-label">Records</label>
                <select id="entity" name="entity" class="form-select" required>
                    <option value="students" @selected(old('entity', $entity ?? '') === 'students')>Students</option>
                    <option value="staff" @selected(old('entity', $entity ?? '') === 'staff')>Staff / teachers</option>
                    <option value="subjects" @selected(old('entity', $entity ?? '') === 'subjects')>Subjects</option>
                    <option value="guardians" @selected(old('entity', $entity ?? '') === 'guardians')>Guardians</option>
                    <option value="inventory" @selected(old('entity', $entity ?? '') === 'inventory')>Inventory items</option>
                    <option value="library_books" @selected(old('entity', $entity ?? '') === 'library_books')>Library books</option>
                </select>
            </div>
            <div class="col-md-5">
                <label for="file" class="form-label">CSV file</label>
                <input id="file" name="file" type="file" accept=".csv,text/csv" class="form-control" required>
                <div class="form-text">Maximum 5 MB. Required columns are listed below.</div>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button class="btn btn-outline-primary" name="mode" value="dry_run">Validate file</button>
                <button class="btn btn-primary" name="mode" value="commit">Import rows</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-transparent"><strong>CSV column formats</strong></div>
    <div class="card-body">
        <p class="mb-2"><strong>Students:</strong> <code>name,email,guardian_id,class_id</code> (optional: <code>program_id,academic_year_id,admission_no,admission_date,dob,gender,national_id,phone,status</code>)</p>
        <p class="mb-2"><strong>Staff / teachers:</strong> <code>name,email</code> (optional: <code>phone,department_id,status</code>)</p>
        <p class="mb-2"><strong>Subjects:</strong> <code>name,code</code> (optional: <code>credit_hours</code>)</p>
        <p class="mb-2"><strong>Guardians:</strong> <code>name,email</code> (optional: <code>phone</code>)</p>
        <p class="mb-2"><strong>Inventory items:</strong> <code>sku,name,category,unit</code> (optional: <code>quantity_on_hand,reorder_level,description</code>)</p>
        <p class="mb-0"><strong>Library books:</strong> <code>title,author,category</code> (optional: <code>isbn,publication_year,copies</code>)</p>
        <p class="small text-muted mt-3 mb-0">Imported user accounts get a random, undisclosed password and are required to set a new password. Existing user credentials are not modified.</p>
    </div>
</div>

@isset($result)
<div class="card shadow-sm border-0">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <strong>Import validation report</strong>
        <span>{{ $result['valid_count'] }} valid · {{ $result['invalid_count'] }} invalid</span>
    </div>
    <div class="card-body">
        @foreach ($result['file_errors'] as $error)
            <div class="alert alert-danger">{{ $error }}</div>
        @endforeach
        @if ($result['can_import'])
            <div class="alert alert-success mb-0">Every row passed validation. Select the CSV again and choose “Import rows” to save it.</div>
        @else
            <div class="alert alert-warning">No rows were imported. Correct the reported issues and validate the file again.</div>
        @endif
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light"><tr><th>CSV line</th><th>Record</th><th>Issues</th></tr></thead>
            <tbody>
            @foreach ($result['rows'] as $row)
                <tr>
                    <td>{{ $row['line'] }}</td>
                    <td>{{ $row['data']['name'] ?? $row['data']['code'] ?? '—' }}</td>
                    <td>@if ($row['errors'] === [])<span class="text-success">Valid</span>@else<ul class="mb-0">@foreach ($row['errors'] as $error)<li>{{ $error }}</li>@endforeach</ul>@endif</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endisset
@endsection
