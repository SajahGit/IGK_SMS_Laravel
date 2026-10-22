@extends('layouts.LayoutApp')
@section('title', 'Admin Dashboard')
@section('content')
<div class="mb-4"><h3 class="mb-1">Admin Dashboard</h3><p class="text-muted mb-0">Overview of your school at a glance.</p></div>

<div class="row g-3 mb-4">
    @foreach ([['Students', $studentCount, 'admin.students.index', 'people-fill', 'indigo'], ['Teachers', $teacherCount, 'admin.teachers.index', 'person-workspace', 'cyan'],
               ['Parents', $guardianCount, 'admin.guardians.index', 'person-hearts', 'rose'], ['Classes', $classCount, 'admin.classes.index', 'building', 'green'],
               ['Subjects', $subjectCount, 'admin.subjects.index', 'book-fill', 'amber']] as [$label, $count, $route, $icon, $tone])
        <div class="col-6 col-xl">
            <a href="{{ route($route) }}" class="text-decoration-none d-block h-100">
                @include('layouts.LayoutStat', ['icon' => $icon, 'value' => $count, 'label' => $label, 'tone' => $tone])
            </a>
        </div>
    @endforeach
</div>

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between">
        <span><i class="bi bi-person-plus me-2"></i>Latest students</span>
        <a href="{{ route('admin.students.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Add student</a>
    </div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Admission No</th><th>Name</th><th>Class</th></tr></thead>
            <tbody>
            @forelse ($latestStudents as $s)
                <tr><td>{{ $s->admission_no }}</td><td>{{ $s->user->name }}</td><td>{{ $s->class->full_name }}</td></tr>
            @empty
                <tr><td colspan="3" class="text-center text-muted">No students yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
