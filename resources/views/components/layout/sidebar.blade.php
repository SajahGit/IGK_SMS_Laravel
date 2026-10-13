@php
    // route name => label. A link is only shown if that route exists.
    $menus = [
        'admin' => [
            'admin.dashboard' => 'Dashboard',
            'admin.users.index' => 'Users',
            'admin.academic-years.index' => 'Academic Years',
            'admin.terms.index' => 'Terms',
            'admin.classes.index' => 'Classes',
            'admin.sections.index' => 'Sections',
            'admin.subjects.index' => 'Subjects',
            'admin.teachers.index' => 'Teachers',
            'admin.students.index' => 'Students',
            'admin.guardians.index' => 'Parents / Guardians',
            'admin.class-subjects.index' => 'Class Subjects',
            'reports.index' => 'Reports',
        ],
        'teacher' => [
            'teacher.dashboard' => 'Dashboard',
            'teacher.my-classes.index' => 'My Classes',
            'teacher.attendance.index' => 'Attendance',
            'teacher.exams.index' => 'Exams & Marks',
            'reports.index' => 'Reports',
        ],
        'student' => [
            'student.dashboard' => 'Dashboard',
            'student.profile.show' => 'My Profile',
            'student.attendance.index' => 'My Attendance',
            'student.results.index' => 'My Results',
        ],
        'parent' => [
            'parent.dashboard' => 'My Children',
        ],
    ];
    $links = $menus[auth()->user()->role] ?? [];
@endphp

<nav class="p-3 space-y-1">
    @foreach ($links as $routeName => $label)
        @if (Route::has($routeName))
            @php $active = request()->routeIs(str_replace('.index', '.*', $routeName)); @endphp
            <a href="{{ route($routeName) }}" class="nav-link {{ $active ? 'active' : '' }}">{{ $label }}</a>
        @endif
    @endforeach
</nav>
