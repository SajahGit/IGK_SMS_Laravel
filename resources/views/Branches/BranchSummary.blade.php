@extends('Layouts.LayoutApp')
@section('title', 'Consolidated Branch Summary')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div><h3 class="mb-0">Consolidated Branch Summary</h3><small class="text-muted">Students, classes, staff and fee collection per campus.</small></div>
    <a href="{{ route('admin.branches.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>
<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle branch-summary-table">
            <thead class="table-light"><tr><th>Branch</th><th class="text-end">Students</th><th class="text-end">Classes</th><th class="text-end">Staff</th><th class="text-end">Invoiced</th><th class="text-end">Collected</th><th class="text-end">Outstanding</th></tr></thead>
            <tbody>
            @forelse ($rows as $r)
                <tr>
                    <td class="branch-name">{{ $r['branch']->name }} <span class="badge bg-secondary">{{ $r['branch']->code }}</span></td>
                    <td class="text-end">{{ $r['students'] }}</td>
                    <td class="text-end">{{ $r['classes'] }}</td>
                    <td class="text-end">{{ $r['employees'] }}</td>
                    <td class="text-end">{{ \App\Support\Money::formatTotals($r['invoiced']) }}</td>
                    <td class="text-end">{{ \App\Support\Money::formatTotals($r['collected']) }}</td>
                    <td class="text-end">
                        @php
                            $outstanding = [];
                            foreach ($r['invoiced'] as $currency => $amount) {
                                $outstanding[$currency] = \App\Support\Money::sub($amount, $r['collected'][$currency] ?? '0.00');
                            }
                        @endphp
                        {{ \App\Support\Money::formatTotals($outstanding) }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No branches defined.</td></tr>
            @endforelse
            </tbody>
            @if (count($rows))
            <tfoot class="table-light fw-semibold"><tr>
                <td>Total</td>
                <td class="text-end">{{ collect($rows)->sum('students') }}</td>
                <td class="text-end">{{ collect($rows)->sum('classes') }}</td>
                <td class="text-end">{{ collect($rows)->sum('employees') }}</td>
                <td class="text-end">{{ \App\Support\Money::formatTotals($currencyTotals['invoiced']) }}</td>
                <td class="text-end">{{ \App\Support\Money::formatTotals($currencyTotals['collected']) }}</td>
                <td class="text-end">{{ \App\Support\Money::formatTotals($currencyTotals['outstanding']) }}</td>
            </tr></tfoot>
            @endif
        </table>
    </div>
</div>

<div class="card shadow-sm border-0 mt-4">
    <div class="card-header bg-white fw-semibold">Operational footprint per campus</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle branch-summary-table">
            <thead class="table-light"><tr><th>Branch</th><th class="text-end">Inventory items</th><th class="text-end">Suppliers</th><th class="text-end">Purchase orders</th><th class="text-end">Hostels</th><th class="text-end">Vehicles</th><th class="text-end">Library copies</th><th class="text-end">Payroll runs</th></tr></thead>
            <tbody>
            @forelse (($footprint ?? []) as $f)
                <tr>
                    <td class="branch-name">{{ $f['branch']->name }} <span class="badge bg-secondary">{{ $f['branch']->code }}</span></td>
                    <td class="text-end">{{ $f['inventory'] }}</td>
                    <td class="text-end">{{ $f['suppliers'] }}</td>
                    <td class="text-end">{{ $f['purchase_orders'] }}</td>
                    <td class="text-end">{{ $f['hostels'] }}</td>
                    <td class="text-end">{{ $f['vehicles'] }}</td>
                    <td class="text-end">{{ $f['library_copies'] }}</td>
                    <td class="text-end">{{ $f['payroll_runs'] }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No branches defined.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
