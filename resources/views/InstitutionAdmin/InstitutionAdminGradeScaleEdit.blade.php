@extends('Layouts.LayoutApp')
@section('title', 'Grade Scale')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h3 class="mb-0">Grade Scale</h3>
        <p class="text-muted mb-0">Ranges must cover 0–100 without gaps or overlaps.</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('admin.term-results.index') }}">Back to Term Results</a>
</div>

@include('Layouts.LayoutErrors')

<form method="POST" action="{{ route('admin.grade-scale.update') }}" class="card border-0 shadow-sm">
    @csrf
    @method('PUT')
    <div class="card-body">
        <div class="mb-3">
            <label for="name" class="form-label">Scale name</label>
            <input id="name" name="name" class="form-control" maxlength="100" required value="{{ old('name', $scale?->name ?? 'Standard Grade Scale') }}">
        </div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Grade</th>
                        <th>Minimum %</th>
                        <th>Maximum %</th>
                        <th>Grade Points</th>
                        <th>Passing</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="grade-items">
                    @foreach (old('items', $items->toArray()) as $index => $item)
                        <tr>
                            <td><input name="items[{{ $index }}][grade]" class="form-control" maxlength="10" required value="{{ $item['grade'] }}"></td>
                            <td><input type="number" step="0.01" min="0" max="100" name="items[{{ $index }}][min_percent]" class="form-control" required value="{{ $item['min_percent'] }}"></td>
                            <td><input type="number" step="0.01" min="0" max="100" name="items[{{ $index }}][max_percent]" class="form-control" required value="{{ $item['max_percent'] }}"></td>
                            <td><input type="number" step="0.01" min="0" max="4" name="items[{{ $index }}][grade_point]" class="form-control" required value="{{ $item['grade_point'] }}"></td>
                            <td>
                                <select name="items[{{ $index }}][is_pass]" class="form-select">
                                    <option value="1" @selected((bool) $item['is_pass'])>Pass</option>
                                    <option value="0" @selected(! (bool) $item['is_pass'])>Fail</option>
                                </select>
                            </td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger remove-grade">Remove</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <button type="button" id="add-grade" class="btn btn-outline-secondary">Add Grade</button>
    </div>
    <div class="card-footer d-flex justify-content-end">
        <button type="submit" class="btn btn-primary">Save Grade Scale</button>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const rows = document.getElementById('grade-items');
        let nextIndex = rows.querySelectorAll('tr').length;
        document.getElementById('add-grade').addEventListener('click', function () {
            const index = nextIndex++;
            rows.insertAdjacentHTML('beforeend', `<tr>
                <td><input name="items[${index}][grade]" class="form-control" maxlength="10" required></td>
                <td><input type="number" step="0.01" min="0" max="100" name="items[${index}][min_percent]" class="form-control" required></td>
                <td><input type="number" step="0.01" min="0" max="100" name="items[${index}][max_percent]" class="form-control" required></td>
                <td><input type="number" step="0.01" min="0" max="4" name="items[${index}][grade_point]" class="form-control" required></td>
                <td><select name="items[${index}][is_pass]" class="form-select"><option value="1">Pass</option><option value="0">Fail</option></select></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger remove-grade">Remove</button></td>
            </tr>`);
        });
        rows.addEventListener('click', function (event) {
            if (event.target.matches('.remove-grade')) {
                event.target.closest('tr').remove();
            }
        });
    });
</script>
@endsection
