@extends('Layouts.LayoutApp')
@section('title', $type === 'income' ? 'Record Other Income' : 'Record School Expense')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div><h3 class="mb-1">{{ $type === 'income' ? 'Record Other Income' : 'Record School Expense' }}</h3><p class="text-muted mb-0">{{ $type === 'income' ? 'Student invoices and fee payments post automatically. Use this for donations, grants, interest, and other non-fee income.' : 'Record operating costs such as payroll, utilities, learning materials, transport, or maintenance.' }}</p></div>
    <a href="{{ route('admin.journals.index') }}" class="btn btn-outline-secondary">Journal entries</a>
</div>
@include('Layouts.LayoutErrors')
<div class="card shadow-sm border-0"><form method="POST" action="{{ route('admin.accounting.transactions.store') }}">
    @csrf
    <input type="hidden" name="type" value="{{ $type }}">
    <div class="card-body row g-3">
        <div class="col-md-6"><label class="form-label" for="description">Description</label><input id="description" name="description" class="form-control" maxlength="255" value="{{ old('description') }}" required></div>
        <div class="col-md-3"><label class="form-label" for="entry_date">Date</label><input id="entry_date" name="entry_date" type="date" class="form-control" max="{{ today()->toDateString() }}" value="{{ old('entry_date', today()->toDateString()) }}" required></div>
        <div class="col-md-3"><label class="form-label" for="amount">Amount ({{ config('school.default_currency', 'LKR') }})</label><input id="amount" name="amount" type="number" min="0.01" max="999999999999.99" step="0.01" class="form-control" value="{{ old('amount') }}" required></div>
        <div class="col-md-6"><label class="form-label" for="category_account_id">{{ $type === 'income' ? 'Income category' : 'Expense category' }}</label><select id="category_account_id" name="category_account_id" class="form-select" required><option value="">Select category</option>@foreach ($categories as $account)<option value="{{ $account->id }}" @selected((string) old('category_account_id') === (string) $account->id)>{{ $account->code }} · {{ $account->name }}</option>@endforeach</select></div>
        <div class="col-md-6"><label class="form-label" for="cash_account_id">Paid into / paid from</label><select id="cash_account_id" name="cash_account_id" class="form-select" required>@foreach ($cashAccounts as $account)<option value="{{ $account->id }}" @selected((string) old('cash_account_id') === (string) $account->id)>{{ $account->code }} · {{ $account->name }}</option>@endforeach</select></div>
        <div class="col-md-6"><label class="form-label" for="branch_id">Campus</label><select id="branch_id" name="branch_id" class="form-select"><option value="">Institution-wide / not assigned</option>@foreach ($branches as $branch)<option value="{{ $branch->id }}" @selected((string) old('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>@endforeach</select></div>
        <div class="col-md-6"><label class="form-label" for="reference">Receipt / reference</label><input id="reference" name="reference" class="form-control" maxlength="80" value="{{ old('reference') }}"></div>
    </div>
    <div class="card-footer bg-transparent d-flex justify-content-end gap-2"><a href="{{ route('admin.journals.index') }}" class="btn btn-outline-secondary">Cancel</a><button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle me-1"></i>Post {{ $type }}</button></div>
</form></div>
@endsection