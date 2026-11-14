@extends('Layouts.LayoutApp')
@section('title', 'Fee Structures')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h3 class="mb-0">Fee Structures</h3>
        <small class="text-muted">Define fee structures per program + academic year (SOP-exact), or — as before — per class and term. Break a structure into fee items (mandatory or optional) to bill each one as its own invoice line.</small>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createFeeModal">
        <i class="bi bi-plus-lg me-1"></i> Add Fee Item
    </button>
</div>

@include('Layouts.LayoutErrors')

<!-- Filter by class -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body py-3">
        <form method="GET" action="{{ route($financeRoutePrefix.'.fee-structures.index') }}" class="row g-2 align-items-center">
            <div class="col-auto">
                <label for="filter_program" class="col-form-label fw-semibold"><i class="bi bi-funnel me-1"></i> Filter by Program:</label>
            </div>
            <div class="col-md-3">
                <select name="program_id" id="filter_program" class="form-select" onchange="this.form.submit()">
                    <option value="">All Programs</option>
                    @foreach ($programs as $p)
                        <option value="{{ $p->id }}" @selected($programId == $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <label for="filter_class" class="col-form-label fw-semibold">or Class:</label>
            </div>
            <div class="col-md-3">
                <select name="class_id" id="filter_class" class="form-select" onchange="this.form.submit()">
                    <option value="">All Classes</option>
                    @foreach ($classes as $c)
                        <option value="{{ $c->id }}" @selected($classId == $c->id)>{{ $c->full_name }}</option>
                    @endforeach
                </select>
            </div>
            @if ($classId || $programId)
                <div class="col-auto">
                    <a href="{{ route($financeRoutePrefix.'.fee-structures.index') }}" class="btn btn-outline-secondary">Clear Filter</a>
                </div>
            @endif
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Fee Name</th>
                    <th>Scope</th>
                    <th>Amount / Currency</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($feeStructures as $fee)
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $fee->name }}</div>
                        </td>
                        <td>
                            @if ($fee->isProgramWide())
                                <span class="badge bg-info text-dark mb-1">Program-wide</span><br>
                                {{ $fee->program?->name ?? '—' }}
                                <div class="small text-muted">{{ $fee->academicYear?->name ?? 'All Academic Years' }}</div>
                            @else
                                {{ $fee->schoolClass?->full_name }}
                                <div class="small text-muted">{{ $fee->term ? $fee->term->name : 'All Terms (General)' }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="fw-bold text-primary">{{ \App\Support\Money::format($fee->amount, $fee->currency.' ') }}</span>
                            @if ($fee->items->isNotEmpty())
                                <div class="small text-muted">sum of mandatory items</div>
                            @endif
                        </td>
                        <td>
                            @if ($fee->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#fee-items-{{ $fee->id }}" aria-expanded="false" aria-controls="fee-items-{{ $fee->id }}">
                                    <i class="bi bi-list-ul me-1"></i> Items ({{ $fee->items->count() }})
                                </button>
                                <button type="button" class="btn btn-outline-primary edit-fee-btn"
                                        data-id="{{ $fee->id }}"
                                        data-scope="{{ $fee->isProgramWide() ? 'program' : 'class' }}"
                                        data-class="{{ $fee->class_id }}"
                                        data-term="{{ $fee->term_id }}"
                                        data-program="{{ $fee->program_id }}"
                                        data-academic-year="{{ $fee->academic_year_id }}"
                                        data-name="{{ $fee->name }}"
                                        data-amount="{{ $fee->amount }}"
                                        data-currency="{{ $fee->currency }}"
                                        data-has-items="{{ $fee->items->isNotEmpty() ? 1 : 0 }}"
                                        data-active="{{ $fee->is_active ? 1 : 0 }}"
                                        data-action="{{ route($financeRoutePrefix.'.fee-structures.update', $fee) }}">
                                    <i class="bi bi-pencil-square me-1"></i> Edit
                                </button>
                                <form method="POST" action="{{ route($financeRoutePrefix.'.fee-structures.destroy', $fee) }}" class="d-inline" onsubmit="return confirm('Delete fee item \'{{ $fee->name }}\'?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger" title="Delete Fee">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="5" class="p-0 border-0">
                            <div class="collapse" id="fee-items-{{ $fee->id }}">
                                <div class="bg-body-tertiary p-3 border-bottom">
                                    <div class="fw-semibold mb-2">Fee items of "{{ $fee->name }}"</div>
                                    @if ($fee->items->isNotEmpty())
                                        <table class="table table-sm mb-3 bg-white">
                                            <thead>
                                                <tr><th>Item</th><th>Amount ({{ $fee->currency }})</th><th>Billing</th><th class="text-end"></th></tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($fee->items as $item)
                                                    <tr>
                                                        <td>{{ $item->name }}</td>
                                                        <td>{{ \App\Support\Money::format($item->amount, $fee->currency.' ') }}</td>
                                                        <td>
                                                            @if ($item->is_mandatory)
                                                                <span class="badge bg-primary">Mandatory</span>
                                                            @else
                                                                <span class="badge bg-secondary">Optional</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-end">
                                                            <form method="POST" action="{{ route($financeRoutePrefix.'.fee-items.destroy', $item) }}" class="d-inline" onsubmit="return confirm('Remove this fee item?')">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button class="btn btn-sm btn-outline-danger" title="Remove item"><i class="bi bi-trash3"></i></button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @else
                                        <p class="small text-muted mb-3">No items yet: this structure bills its own amount as one line. Add an item to split it up.</p>
                                    @endif
                                    <form method="POST" action="{{ route($financeRoutePrefix.'.fee-structures.items.store', $fee) }}" class="row g-2 align-items-end">
                                        @csrf
                                        <div class="col-md-4">
                                            <label class="form-label small mb-1">Item name</label>
                                            <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Lab fee" maxlength="80" pattern="[A-Za-z][A-Za-z0-9\s\x28\x29\x26\x2d]*" data-live-filter="letter-start-fee" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small mb-1">Amount ({{ $fee->currency }})</label>
                                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-sm" placeholder="0.00" required>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="hidden" name="is_mandatory" value="0">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="is_mandatory" value="1" id="mandatory-{{ $fee->id }}" checked>
                                                <label class="form-check-label small" for="mandatory-{{ $fee->id }}">Mandatory</label>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-plus-lg me-1"></i> Add item</button>
                                        </div>
                                    </form>
                                    <div class="small text-muted mt-2">Items cannot be changed once an invoice has been issued from this structure.</div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">
                            <i class="bi bi-cash-stack fs-2 d-block mb-1"></i>
                            No fee structures defined yet. Click "Add Fee Item" to create one.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createFeeModal" tabindex="-1" aria-labelledby="createFeeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route($financeRoutePrefix.'.fee-structures.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="createFeeModalLabel"><i class="bi bi-cash-coin me-2"></i>Add Fee Structure</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label d-block">Scope <span class="text-danger">*</span></label>
                        <div class="btn-group" role="group">
                            <input type="radio" class="btn-check" name="scope_toggle" id="create_scope_program" autocomplete="off" checked>
                            <label class="btn btn-outline-primary btn-sm" for="create_scope_program">Program + Academic Year (SOP)</label>
                            <input type="radio" class="btn-check" name="scope_toggle" id="create_scope_class" autocomplete="off">
                            <label class="btn btn-outline-primary btn-sm" for="create_scope_class">One Class (legacy)</label>
                        </div>
                    </div>
                    <div id="create_program_fields">
                        <div class="mb-3">
                            <label for="create_program" class="form-label">Program <span class="text-danger">*</span></label>
                            <select name="program_id" id="create_program" class="form-select">
                                <option value="">Select Program</option>
                                @foreach ($programs as $p)
                                    <option value="{{ $p->id }}" @selected(old('program_id', $programId) == $p->id)>{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="create_academic_year" class="form-label">Academic Year (Optional)</label>
                            <select name="academic_year_id" id="create_academic_year" class="form-select">
                                <option value="">All Academic Years / General Fee</option>
                                @foreach ($academicYears as $y)
                                    <option value="{{ $y->id }}" @selected(old('academic_year_id') == $y->id)>{{ $y->name }} {{ $y->is_current ? '(Current)' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div id="create_class_fields" class="d-none">
                        <div class="mb-3">
                            <label for="create_class" class="form-label">Class <span class="text-danger">*</span></label>
                            <select name="class_id" id="create_class" class="form-select">
                                <option value="">Select Target Class</option>
                                @foreach ($classes as $c)
                                    <option value="{{ $c->id }}" @selected(old('class_id', $classId) == $c->id)>{{ $c->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="create_term" class="form-label">Term / Semester (Optional)</label>
                            <select name="term_id" id="create_term" class="form-select">
                                <option value="">All Terms / General Fee</option>
                                @foreach ($terms as $t)
                                    <option value="{{ $t->id }}" @selected(old('term_id') == $t->id)>{{ $t->name }} {{ $t->is_current ? '(Current Active)' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="create_name" class="form-label">Fee Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="create_name" class="form-control" placeholder="e.g. Term Tuition Fee, Lab Fee" maxlength="80" pattern="[A-Za-z][A-Za-z0-9\s\x28\x29\x26\x2d]*" data-live-filter="letter-start-fee" required value="{{ old('name') }}">
                    </div>
                    <div class="mb-3">
                        <label for="create_amount" class="form-label">Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="amount" id="create_amount" class="form-control" placeholder="0.00" required value="{{ old('amount') }}">
                    </div>
                    <div class="mb-3">
                        <label for="create_currency" class="form-label">Billing currency</label>
                        <select name="currency" id="create_currency" class="form-select">
                            @foreach (config('school.currencies', ['LKR']) as $currency)
                                <option value="{{ $currency }}" @selected(old('currency', config('school.default_currency', 'LKR')) === $currency)>{{ $currency }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" id="create_active" value="1" checked>
                        <label class="form-check-label" for="create_active">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Save Fee Structure</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editFeeModal" tabindex="-1" aria-labelledby="editFeeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="editFeeForm" action="">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="editFeeModalLabel"><i class="bi bi-pencil-square me-2"></i>Edit Fee Structure</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label d-block">Scope <span class="text-danger">*</span></label>
                        <div class="btn-group" role="group">
                            <input type="radio" class="btn-check" name="edit_scope_toggle" id="edit_scope_program" autocomplete="off">
                            <label class="btn btn-outline-primary btn-sm" for="edit_scope_program">Program + Academic Year (SOP)</label>
                            <input type="radio" class="btn-check" name="edit_scope_toggle" id="edit_scope_class" autocomplete="off">
                            <label class="btn btn-outline-primary btn-sm" for="edit_scope_class">One Class (legacy)</label>
                        </div>
                    </div>
                    <div id="edit_program_fields">
                        <div class="mb-3">
                            <label for="edit_program" class="form-label">Program <span class="text-danger">*</span></label>
                            <select name="program_id" id="edit_program" class="form-select">
                                <option value="">Select Program</option>
                                @foreach ($programs as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_academic_year" class="form-label">Academic Year (Optional)</label>
                            <select name="academic_year_id" id="edit_academic_year" class="form-select">
                                <option value="">All Academic Years / General Fee</option>
                                @foreach ($academicYears as $y)
                                    <option value="{{ $y->id }}">{{ $y->name }} {{ $y->is_current ? '(Current)' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div id="edit_class_fields" class="d-none">
                        <div class="mb-3">
                            <label for="edit_class" class="form-label">Class <span class="text-danger">*</span></label>
                            <select name="class_id" id="edit_class" class="form-select">
                                <option value="">Select Target Class</option>
                                @foreach ($classes as $c)
                                    <option value="{{ $c->id }}">{{ $c->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_term" class="form-label">Term / Semester (Optional)</label>
                            <select name="term_id" id="edit_term" class="form-select">
                                <option value="">All Terms / General Fee</option>
                                @foreach ($terms as $t)
                                    <option value="{{ $t->id }}">{{ $t->name }} {{ $t->is_current ? '(Current Active)' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Fee Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit_name" class="form-control" maxlength="80" pattern="[A-Za-z][A-Za-z0-9\s\x28\x29\x26\x2d]*" data-live-filter="letter-start-fee" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_amount" class="form-label">Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="amount" id="edit_amount" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_currency" class="form-label">Billing currency</label>
                        <select name="currency" id="edit_currency" class="form-select">
                            @foreach (config('school.currencies', ['LKR']) as $currency)
                                <option value="{{ $currency }}">{{ $currency }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" id="edit_active" value="1">
                        <label class="form-check-label" for="edit_active">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Update Fee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // --- Scope toggle: Program+Year (SOP) vs one Class (legacy) ---
        function wireScopeToggle(prefix) {
            var programRadio = document.getElementById(prefix + '_scope_program');
            var classRadio = document.getElementById(prefix + '_scope_class');
            var programFields = document.getElementById(prefix + '_program_fields');
            var classFields = document.getElementById(prefix + '_class_fields');
            var programSelect = document.getElementById(prefix + '_program');
            var classSelect = document.getElementById(prefix + '_class');
            if (!programRadio || !classRadio) return;

            function sync() {
                var isProgram = programRadio.checked;
                programFields.classList.toggle('d-none', !isProgram);
                classFields.classList.toggle('d-none', isProgram);
                // Only the active scope's select should be required / submitted meaningfully.
                programSelect.required = isProgram;
                classSelect.required = !isProgram;
                if (isProgram) {
                    classSelect.value = '';
                } else {
                    programSelect.value = '';
                }
            }

            programRadio.addEventListener('change', sync);
            classRadio.addEventListener('change', sync);
            sync();
        }

        wireScopeToggle('create');
        wireScopeToggle('edit');

        // --- Edit modal: populate from the clicked row's data-* attributes ---
        var editModalEl = document.getElementById('editFeeModal');
        if (!editModalEl) return;
        var editModal = new bootstrap.Modal(editModalEl);
        var form = document.getElementById('editFeeForm');
        var classSelect = document.getElementById('edit_class');
        var termSelect = document.getElementById('edit_term');
        var programSelect = document.getElementById('edit_program');
        var academicYearSelect = document.getElementById('edit_academic_year');
        var scopeProgramRadio = document.getElementById('edit_scope_program');
        var scopeClassRadio = document.getElementById('edit_scope_class');
        var nameInput = document.getElementById('edit_name');
        var amountInput = document.getElementById('edit_amount');
        var currencyInput = document.getElementById('edit_currency');
        var activeInput = document.getElementById('edit_active');

        document.querySelectorAll('.edit-fee-btn').forEach(function (button) {
            button.addEventListener('click', function () {
                form.action = this.dataset.action;

                var isProgramWide = this.dataset.scope === 'program';
                scopeProgramRadio.checked = isProgramWide;
                scopeClassRadio.checked = !isProgramWide;
                scopeProgramRadio.dispatchEvent(new Event('change'));

                classSelect.value = this.dataset.class || '';
                termSelect.value = this.dataset.term || '';
                programSelect.value = this.dataset.program || '';
                academicYearSelect.value = this.dataset.academicYear || '';

                nameInput.value = this.dataset.name;
                amountInput.value = this.dataset.amount;
                currencyInput.value = this.dataset.currency || 'LKR';
                amountInput.readOnly = this.dataset.hasItems === '1';
                activeInput.checked = this.dataset.active === '1';

                editModal.show();
            });
        });
    });
</script>
@endsection
