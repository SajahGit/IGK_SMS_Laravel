@extends('Layouts.LayoutApp')
@section('title', 'New Journal Entry')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">New Journal Entry</h3>
    <a href="{{ route('admin.journals.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>
@include('Layouts.LayoutErrors')
<div class="card shadow-sm border-0">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.journals.store') }}">
            @csrf
            <div class="row g-3 mb-3">
                <div class="col-md-3"><label class="form-label">Date <span class="text-danger">*</span></label>
                    <input type="date" name="entry_date" class="form-control" required value="{{ old('entry_date', now()->toDateString()) }}"></div>
                <div class="col-md-5"><label class="form-label">Description <span class="text-danger">*</span></label>
                    <input type="text" name="description" class="form-control" required value="{{ old('description') }}"></div>
                <div class="col-md-2"><label class="form-label">Reference</label>
                    <input type="text" name="reference" class="form-control" value="{{ old('reference') }}"></div>
                <div class="col-md-2"><label class="form-label">Branch</label>
                    <select name="branch_id" class="form-select"><option value="">—</option>
                        @foreach ($branches as $b)<option value="{{ $b->id }}" @selected((string) old('branch_id') === (string) $b->id)>{{ $b->name }}</option>@endforeach
                    </select></div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light"><tr><th style="width:35%">Account</th><th>Debit</th><th>Credit</th><th>Memo</th></tr></thead>
                    <tbody>
                    @for ($i = 0; $i < 4; $i++)
                        <tr>
                            <td><select name="lines[{{ $i }}][account_id]" class="form-select" @if ($i < 2) required @endif>
                                <option value="">— Select —</option>
                                @foreach ($accounts as $a)<option value="{{ $a->id }}" @selected((string) old("lines.$i.account_id") === (string) $a->id)>{{ $a->code }} — {{ $a->name }}</option>@endforeach
                            </select></td>
                            <td><input type="number" step="0.01" min="0" name="lines[{{ $i }}][debit]" class="form-control" value="{{ old("lines.$i.debit") }}"></td>
                            <td><input type="number" step="0.01" min="0" name="lines[{{ $i }}][credit]" class="form-control" value="{{ old("lines.$i.credit") }}"></td>
                            <td><input type="text" name="lines[{{ $i }}][memo]" class="form-control" value="{{ old("lines.$i.memo") }}"></td>
                        </tr>
                    @endfor
                    </tbody>
                </table>
            </div>
            <small class="text-muted">Total debits must equal total credits. Leave unused lines empty.</small>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Post Entry</button>
                <a href="{{ route('admin.journals.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
