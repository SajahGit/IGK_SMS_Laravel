@extends('Layouts.LayoutApp')
@section('title', 'Pay Invoice Online')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div><h3 class="mb-1">Pay Invoice Online</h3><div class="text-muted">{{ $invoice->invoice_no }} · Balance {{ \App\Support\Money::format($invoice->balance) }}</div></div>
            <a href="{{ route('student.invoices') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
        @if (! config('services.payhere.merchant_id') || ! config('services.payhere.merchant_secret'))
            <div class="alert alert-warning">Online payments are not configured. Contact the school office.</div>
        @else
            <form method="POST" action="{{ route('student.payhere.store', $invoice) }}" class="card shadow-sm border-0">
                @csrf
                <div class="card-body">
                    @foreach ([['first_name', 'First name'], ['last_name', 'Last name'], ['email', 'Email'], ['phone', 'Phone'], ['address', 'Billing address'], ['city', 'City'], ['country', 'Country']] as [$field, $label])
                        <div class="mb-3">
                            <label for="{{ $field }}" class="form-label">{{ $label }}</label>
                            <input id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $field === 'email' ? auth()->user()->email : '') }}" class="form-control @error($field) is-invalid @enderror" required maxlength="{{ $field === 'address' ? 255 : 120 }}">
                            @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    @endforeach
                    <button class="btn btn-primary" type="submit"><i class="bi bi-shield-lock me-1"></i>Continue to PayHere</button>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection