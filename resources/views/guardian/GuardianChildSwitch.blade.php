<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h3 class="mb-0">{{ $student->user->name }} <small class="text-muted fs-6">{{ $student->class->full_name }}</small></h3>
    <div class="d-flex gap-2">
        @if ($children->count() > 1)
            <select class="form-select" onchange="window.location = this.value">
                @foreach ($children as $child)
                    <option value="{{ route($routeName, $child) }}" @selected($child->id === $student->id)>{{ $child->user->name }}</option>
                @endforeach
            </select>
        @endif
        <a href="{{ route('guardian.dashboard') }}" class="btn btn-outline-secondary">Back</a>
    </div>
</div>
