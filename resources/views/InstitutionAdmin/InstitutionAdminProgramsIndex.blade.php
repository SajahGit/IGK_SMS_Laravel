@extends('Layouts.LayoutApp')
@section('title', 'Academic Programs')
@section('content')
@php
    $user = auth()->user();
    $canManagePrograms = $user->isSuperAdmin() 
        || $user->hasAnyRole(['Super Admin', 'Institution Admin']) 
        || $user->can('programs.update') 
        || $user->can('programs.delete');
    $canCreateProgram = $user->isSuperAdmin() 
        || $user->hasAnyRole(['Super Admin', 'Institution Admin']) 
        || $user->can('programs.create');
    $canViewCurriculum = $user->isSuperAdmin() 
        || $user->hasAnyRole(['Super Admin', 'Institution Admin', 'Registrar', 'Teacher']) 
        || $user->can('curriculum.read');
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h3 class="mb-1">Academic Programs</h3>
        <p class="text-muted small mb-0">
            Define curriculum frameworks (e.g. Cambridge CAIE, Edexcel, IB, AP, National) governing classes, subjects, and student enrollments.
        </p>
    </div>
    @if ($canCreateProgram)
    <a href="{{ route('admin.programs.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Add Program
    </a>
    @endif
</div>

@include('Layouts.LayoutErrors')

<!-- Informative Purpose Callout -->
<div class="card bg-body-tertiary border-0 mb-3 shadow-none">
    <div class="card-body py-2 px-3 d-flex flex-wrap align-items-center justify-content-between gap-2 small">
        <div class="text-muted">
            <i class="bi bi-info-circle-fill text-primary me-2"></i>
            <strong>Role of Programs:</strong> Every class and admitted student is mapped to an Academic Program to determine their subject curriculum, grading scheme, and fee structures.
        </div>
        <div>
            <a href="{{ route('admin.curricula.index') }}" class="text-decoration-none fw-semibold">
                <i class="bi bi-diagram-3 me-1"></i> View Curriculum Matrix &rarr;
            </a>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Program Name</th>
                    <th>Code</th>
                    <th>Department</th>
                    <th>Linked Classes</th>
                    <th>Enrolled Students</th>
                    <th>Status</th>
                    @if ($canManagePrograms || $canViewCurriculum)
                    <th class="text-end">Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody>
            @forelse ($programs as $program)
                <tr>
                    <td>
                        <div class="fw-semibold text-body">{{ $program->name }}</div>
                        @if ($program->gradeScale)
                            <small class="text-muted"><i class="bi bi-award me-1"></i>Scale: {{ $program->gradeScale->name }}</small>
                        @endif
                    </td>
                    <td><span class="badge bg-secondary-subtle text-body border">{{ $program->code }}</span></td>
                    <td>
                        <span class="text-muted">{{ $program->department->name ?? 'General' }}</span>
                    </td>
                    <td>
                        <span class="badge bg-primary-subtle text-primary border">
                            <i class="bi bi-mortarboard me-1"></i>{{ $program->classes_count ?? 0 }} Classes
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-info-subtle text-info border">
                            <i class="bi bi-people me-1"></i>{{ $program->students_count ?? 0 }} Students
                        </span>
                    </td>
                    <td>
                        @if ($program->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </td>
                    @if ($canManagePrograms || $canViewCurriculum)
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            @if ($canViewCurriculum)
                            <a href="{{ route('admin.curricula.index', ['program_id' => $program->id]) }}" class="btn btn-outline-info" title="View Program Curriculum">
                                <i class="bi bi-diagram-3 me-1"></i>Curriculum
                            </a>
                            @endif
                            @if ($canManagePrograms)
                                @can('update', $program)
                                <a href="{{ route('admin.programs.edit', $program) }}" class="btn btn-outline-primary" title="Edit Program">
                                    <i class="bi bi-pencil-square me-1"></i>Edit
                                </a>
                                @endcan
                                @can('delete', $program)
                                <form method="POST" action="{{ route('admin.programs.destroy', $program) }}" class="d-inline" onsubmit="return confirm('Delete program {{ $program->name }}? This will fail if classes or students are still linked.')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger" title="Delete Program">
                                        <i class="bi bi-trash3 me-1"></i>Delete
                                    </button>
                                </form>
                                @endcan
                            @endif
                        </div>
                    </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ ($canManagePrograms || $canViewCurriculum) ? 7 : 6 }}" class="text-center text-muted py-4">
                        No academic programs defined yet.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $programs->links('pagination::bootstrap-5') }}</div>
@endsection
