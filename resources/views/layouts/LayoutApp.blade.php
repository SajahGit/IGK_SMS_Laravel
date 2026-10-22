<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    @include('layouts.LayoutHead')
    <title>@yield('title', 'Dashboard') - IGK School</title>
</head>
<body>
<div class="bg-orb-3"></div>
@php
    $user = auth()->user();
    // [label, route, active pattern, icon]
    $menus = [
        'admin' => [
            'Overview' => [['Dashboard', 'admin.dashboard', 'admin.dashboard', 'grid-1x2-fill']],
            'Manage' => [
                ['Students', 'admin.students.index', 'admin.students.*', 'people-fill'],
                ['Teachers', 'admin.teachers.index', 'admin.teachers.*', 'person-workspace'],
                ['Parents', 'admin.guardians.index', 'admin.guardians.*', 'person-hearts'],
                ['Classes', 'admin.classes.index', 'admin.classes.*', 'building'],
                ['Subjects', 'admin.subjects.index', 'admin.subjects.*', 'book-fill'],
                ['Class Subjects', 'admin.class-subjects.index', 'admin.class-subjects.*', 'diagram-3-fill'],
            ],
            'Reports' => [
                ['Student Report', 'reports.students', 'reports.students*', 'file-earmark-person'],
                ['Attendance Report', 'reports.attendance', 'reports.attendance*', 'calendar2-check'],
                ['Result Report', 'reports.results', 'reports.results*', 'trophy'],
            ],
        ],
        'teacher' => [
            'Overview' => [['Dashboard', 'teacher.dashboard', 'teacher.dashboard', 'grid-1x2-fill']],
            'Teaching' => [
                ['My Classes', 'teacher.classes.index', 'teacher.classes.*', 'easel2-fill'],
                ['Mark Attendance', 'teacher.attendance.create', 'teacher.attendance.create', 'clipboard2-check-fill'],
                ['Attendance History', 'teacher.attendance.index', 'teacher.attendance.index', 'clock-history'],
                ['Exams and Marks', 'teacher.exams.index', 'teacher.exams.*', 'journal-check'],
            ],
            'Reports' => [
                ['Student Report', 'reports.students', 'reports.students*', 'file-earmark-person'],
                ['Attendance Report', 'reports.attendance', 'reports.attendance*', 'calendar2-check'],
                ['Result Report', 'reports.results', 'reports.results*', 'trophy'],
            ],
        ],
        'student' => [
            'My School' => [
                ['Dashboard', 'student.dashboard', 'student.dashboard', 'grid-1x2-fill'],
                ['My Profile', 'student.profile', 'student.profile*', 'person-badge-fill'],
                ['My Attendance', 'student.attendance', 'student.attendance', 'calendar2-check-fill'],
                ['My Results', 'student.results', 'student.results', 'trophy-fill'],
            ],
        ],
        'parent' => [
            'Family' => [['My Children', 'guardian.dashboard', 'guardian.*', 'people-fill']],
        ],
    ];
    $menu = $menus[$user->role] ?? [];
    $initial = strtoupper(mb_substr($user->name, 0, 1));
@endphp

<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <a href="{{ url('/') }}" class="brand">
            <span class="brand-mark"><i class="bi bi-mortarboard-fill"></i></span>
            <span>
                <div class="brand-name">IGK School</div>
                <div class="brand-sub">MANAGEMENT SYSTEM</div>
            </span>
        </a>

        <nav class="nav-scroll">
            @foreach ($menu as $group => $items)
                <div class="nav-group">{{ $group }}</div>
                @foreach ($items as [$label, $route, $pattern, $icon])
                    <a class="nav-glass {{ request()->routeIs($pattern) ? 'active' : '' }}" href="{{ route($route) }}">
                        <i class="bi bi-{{ $icon }}"></i><span>{{ $label }}</span>
                    </a>
                @endforeach
            @endforeach
        </nav>

        <div class="user-card">
            <div class="avatar">{{ $initial }}</div>
            <div class="user-meta">
                <div class="user-name" title="{{ $user->name }}">{{ $user->name }}</div>
                <span class="role-pill">{{ $user->role }}</span>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button class="icon-btn" title="Logout" aria-label="Logout"><i class="bi bi-box-arrow-right"></i></button>
            </form>
        </div>
    </aside>
    <div class="sidebar-backdrop" id="sbBackdrop"></div>

    <div class="app-main">
        <header class="topbar no-print">
            <button class="icon-btn menu-btn" data-sidebar-toggle aria-label="Menu"><i class="bi bi-list fs-5"></i></button>
            <div class="flex-grow-1">
                <div class="topbar-title">Hello, {{ explode(' ', trim($user->name))[0] }}</div>
                <div class="topbar-sub">{{ $user->role === 'parent' ? 'Parent' : $user->role }} portal</div>
            </div>
            <span class="topbar-date"><i class="bi bi-calendar3 me-1"></i>{{ now()->format('D, d M Y') }}</span>
            <button class="icon-btn" data-theme-toggle title="Switch theme" aria-label="Switch theme"><i data-theme-icon class="bi bi-sun"></i></button>
        </header>

        <main class="content">
            @if (session('success'))
                <div class="alert alert-success d-flex align-items-center gap-2 no-print" data-autohide>
                    <i class="bi bi-check-circle-fill"></i><span class="flex-grow-1">{{ session('success') }}</span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger d-flex align-items-center gap-2 no-print">
                    <i class="bi bi-exclamation-triangle-fill"></i><span class="flex-grow-1">{{ session('error') }}</span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @yield('content')
        </main>

        <footer class="app-footer no-print">IGK School Management System &middot; {{ now()->year }}</footer>
    </div>
</div>

<script defer src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
