<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    @include('Layouts.LayoutHead')
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
                ['Staff Attendance', 'admin.staff-attendance.index', 'admin.staff-attendance.*', 'person-check-fill'],
                ['HR Employees', 'admin.hr.employees.index', 'admin.hr.*', 'person-vcard'],
                ['Leave Management', 'admin.hr.leaves.index', 'admin.hr.leaves.*', 'calendar2-minus'],
                ['Payroll', 'admin.hr.payroll.index', 'admin.hr.payroll.*', 'cash-coin'],
                ['Transport', 'admin.transport.index', 'admin.transport.*', 'bus-front-fill'],
                ['Hostels', 'admin.hostels.index', 'admin.hostels.*', 'house-door-fill'],
                ['Cafeteria', 'admin.cafeteria.index', 'admin.cafeteria.*', 'cup-hot-fill'],
                ['Inventory', 'admin.inventory.index', 'admin.inventory.*', 'boxes'],
                ['Bulk Import', 'admin.imports.index', 'admin.imports.*', 'file-earmark-arrow-up'],
                ['Guardians', 'admin.guardians.index', 'admin.guardians.*', 'person-hearts'],
                ['Classes', 'admin.classes.index', 'admin.classes.*', 'building'],
                ['Subjects', 'admin.subjects.index', 'admin.subjects.*', 'book-fill'],
                ['Class Subjects', 'admin.class-subjects.index', 'admin.class-subjects.*', 'diagram-3-fill'],
                ['Academic Years', 'admin.academic-years.index', 'admin.academic-years.*', 'calendar-range'],
                ['Terms', 'admin.terms.index', 'admin.terms.*', 'calendar2-week'],
                ['Timetable', 'admin.timetable.index', 'admin.timetable.*', 'calendar-week'],
                ['Attendance', 'admin.attendance.index', 'admin.attendance.*', 'calendar2-check'],
                ['Exams & Results', 'admin.exams.index', 'admin.exams.*', 'journal-check'],
                ['Term Results', 'admin.term-results.index', 'admin.term-results.*', 'trophy'],
                ['Grade Scale', 'admin.grade-scale.edit', 'admin.grade-scale.*', 'sliders'],
            ],
            'Library' => [
                ['Dashboard', 'library.dashboard', 'library.dashboard', 'grid-1x2-fill'],
                ['Loans', 'library.loans.index', 'library.loans.*', 'arrow-left-right'],
                ['Copies', 'library.copies.index', 'library.copies.*', 'collection'],
                ['Authors', 'library.authors.index', 'library.authors.*', 'person-lines-fill'],
                ['Categories', 'library.categories.index', 'library.categories.*', 'tags-fill'],
                ['Fines', 'library.fines.index', 'library.fines.*', 'cash-coin'],
            ],
            'Finance' => [
                ['Procurement', 'procurement.orders.index', 'procurement.*', 'cart-check'],
                ['Fee Structures', 'admin.fee-structures.index', 'admin.fee-structures.*', 'cash-stack'],
                ['Invoices & Billing', 'admin.invoices.index', 'admin.invoices.*', 'receipt'],
                ['Record Payment', 'admin.payments.create', 'admin.payments.create', 'credit-card'],
                ['Chart of Accounts', 'admin.accounts.index', 'admin.accounts.*', 'diagram-2'],
                ['Journal Entries', 'admin.journals.index', 'admin.journals.*', 'journal-richtext'],
                ['Trial Balance', 'admin.accounting.trial-balance', 'admin.accounting.trial-balance', 'calculator'],
                ['General Ledger', 'admin.accounting.ledger', 'admin.accounting.ledger', 'journal-text'],
            ],
            'System' => [
                ['Announcements', 'admin.announcements.index', 'admin.announcements.*', 'megaphone-fill'],
                ['Branches', 'admin.branches.index', 'admin.branches.*', 'buildings'],
                ['Audit Trail', 'admin.audit-logs.index', 'admin.audit-logs.*', 'clock-history'],
            ],
            'Reports' => [
                ['Student Report', 'reports.students', 'reports.students*', 'file-earmark-person'],
                ['Attendance Report', 'reports.attendance', 'reports.attendance*', 'calendar2-check'],
                ['Class Attendance Summary', 'reports.class-attendance', 'reports.class-attendance*', 'bar-chart-line'],
                ['Result Report', 'reports.results', 'reports.results*', 'trophy'],
                ['Subject Performance', 'reports.subject-performance', 'reports.subject-performance*', 'graph-up'],
                ['Fee Collection', 'reports.fee-collection', 'reports.fee-collection*', 'cash-coin'],
                ['Defaulters List', 'reports.defaulters', 'reports.defaulters*', 'exclamation-octagon'],
            ],
        ],
        'teacher' => [
            'Overview' => [['Dashboard', 'teacher.dashboard', 'teacher.dashboard', 'grid-1x2-fill']],
            'Teaching' => [
                ['My Classes', 'teacher.classes.index', 'teacher.classes.*', 'easel2-fill'],
                ['Learning Management', 'teacher.lms.index', 'teacher.lms.*', 'book-half'],
                ['My Timetable', 'teacher.timetable.index', 'teacher.timetable.*', 'calendar-week'],
                ['Mark Attendance', 'teacher.attendance.create', 'teacher.attendance.create', 'clipboard2-check-fill'],
                ['Attendance History', 'teacher.attendance.index', 'teacher.attendance.index', 'clock-history'],
                ['Exams & Results', 'teacher.exams.index', 'teacher.exams.*', 'journal-check'],
            ],
            'Reports' => [
                ['Student Report', 'reports.students', 'reports.students*', 'file-earmark-person'],
                ['Attendance Report', 'reports.attendance', 'reports.attendance*', 'calendar2-check'],
                ['Class Attendance Summary', 'reports.class-attendance', 'reports.class-attendance*', 'bar-chart-line'],
                ['Result Report', 'reports.results', 'reports.results*', 'trophy'],
                ['Subject Performance', 'reports.subject-performance', 'reports.subject-performance*', 'graph-up'],
            ],
        ],
        'accountant' => [
            'Finance' => [
                ['Dashboard', 'accountant.dashboard', 'accountant.dashboard', 'grid-1x2-fill'],
                ['Procurement', 'procurement.orders.index', 'procurement.*', 'cart-check'],
                ['Fee Structures', 'accountant.fee-structures.index', 'accountant.fee-structures.*', 'cash-stack'],
                ['Invoices & Billing', 'accountant.invoices.index', 'accountant.invoices.*', 'receipt'],
                ['Record Payment', 'accountant.payments.create', 'accountant.payments.*', 'credit-card'],
            ],
            'Reports' => [
                ['Fee Collection', 'accountant.reports.fee-collection', 'accountant.reports.fee-collection*', 'cash-coin'],
                ['Defaulters List', 'accountant.reports.defaulters', 'accountant.reports.defaulters*', 'exclamation-octagon'],
                ['Student Ledger', 'accountant.reports.student-ledger', 'accountant.reports.student-ledger*', 'journal-text'],
            ],
        ],
        'registrar' => [
            'Overview' => [
                ['Dashboard', 'registrar.dashboard', 'registrar.dashboard', 'grid-1x2-fill'],
            ],
            'Academic administration' => [
                ['Students', 'admin.students.index', 'admin.students.*', 'people-fill'],
                ['Add Student', 'admin.students.create', 'admin.students.create', 'person-plus-fill'],
                ['Guardians', 'admin.guardians.index', 'admin.guardians.*', 'person-hearts'],
                ['Classes', 'admin.classes.index', 'admin.classes.*', 'building'],
                ['Create Class', 'admin.classes.create', 'admin.classes.create', 'building-add'],
                ['Teachers', 'admin.teachers.index', 'admin.teachers.*', 'person-workspace'],
                ['Bulk Import', 'admin.imports.index', 'admin.imports.*', 'file-earmark-arrow-up'],
                ['Departments', 'admin.departments.index', 'admin.departments.*', 'diagram-3'],
                ['Programs', 'admin.programs.index', 'admin.programs.*', 'mortarboard'],
                ['Subjects', 'admin.subjects.index', 'admin.subjects.*', 'book-fill'],
                ['Curriculum', 'admin.curricula.index', 'admin.curricula.*', 'journal-bookmark'],
                ['Academic Years', 'admin.academic-years.index', 'admin.academic-years.*', 'calendar-range'],
                ['Terms', 'admin.terms.index', 'admin.terms.*', 'calendar2-week'],
                ['Class Subjects', 'admin.class-subjects.index', 'admin.class-subjects.*', 'diagram-3-fill'],
                ['Timetable', 'admin.timetable.index', 'admin.timetable.*', 'calendar-week'],
                ['Attendance', 'admin.attendance.index', 'admin.attendance.*', 'calendar2-check'],
                ['Exams & Results', 'admin.exams.index', 'admin.exams.*', 'journal-check'],
                ['Term Results', 'admin.term-results.index', 'admin.term-results.*', 'trophy'],
                ['Grade Scale', 'admin.grade-scale.edit', 'admin.grade-scale.*', 'sliders'],
                ['Announcements', 'admin.announcements.index', 'admin.announcements.*', 'megaphone-fill'],
            ],
            'Academic Reports' => [
                ['Enrollment Register', 'reports.enrollment', 'reports.enrollment*', 'people-fill'],
                ['Attendance Report', 'reports.attendance', 'reports.attendance*', 'calendar2-check'],
                ['Result Report', 'reports.results', 'reports.results*', 'trophy'],
                ['Fee Collection', 'reports.fee-collection', 'reports.fee-collection*', 'cash-coin'],
                ['Student Ledger', 'reports.student-ledger', 'reports.student-ledger*', 'journal-text'],
            ],
        ],
        'librarian' => [
            'Library' => [
                ['Dashboard', 'library.dashboard', 'library.dashboard', 'grid-1x2-fill'],
                ['Loans', 'library.loans.index', 'library.loans.*', 'arrow-left-right'],
                ['Copies', 'library.copies.index', 'library.copies.*', 'collection'],
                ['Authors', 'library.authors.index', 'library.authors.*', 'person-lines-fill'],
                ['Categories', 'library.categories.index', 'library.categories.*', 'tags-fill'],
                ['Fines', 'library.fines.index', 'library.fines.*', 'cash-coin'],
            ],
        ],
        'student' => [
            'My School' => [
                ['Dashboard', 'student.dashboard', 'student.dashboard', 'grid-1x2-fill'],
                ['My Profile', 'student.profile', 'student.profile*', 'person-badge-fill'],
                ['Class Timetable', 'student.timetable', 'student.timetable', 'calendar-week'],
                ['Learning Management', 'student.lms.index', 'student.lms.*', 'book-half'],
                ['My Hostel', 'student.hostel', 'student.hostel', 'house-door-fill'],
                ['Cafeteria', 'student.cafeteria', 'student.cafeteria*', 'cup-hot-fill'],
                ['My Attendance', 'student.attendance', 'student.attendance', 'calendar2-check-fill'],
                ['My Results', 'student.results', 'student.results', 'trophy-fill'],
                ['Report Card', 'student.report-card', 'student.report-card', 'award-fill'],
                ['Academic Transcript', 'student.transcript', 'student.transcript*', 'journal-bookmark-fill'],
                ['Fee Invoices', 'student.invoices', 'student.invoices', 'receipt'],
                ['My Financial Ledger', 'student.ledger', 'student.ledger*', 'journal-text'],
            ],
        ],
        'parent' => [
            'Family' => [
                ['My Children', 'guardian.dashboard', 'guardian.dashboard', 'people-fill'],
                ['Fee Statements', 'guardian.invoices', 'guardian.invoices', 'receipt'],
            ],
        ],
    ];
    $menuRole = match (strtolower($user->role)) {
        'super-admin', 'super admin', 'institution admin' => 'admin',
        default => strtolower($user->role),
    };
    $menu = $menus[$menuRole] ?? [];
    $menu['Communication'][] = ['Private Messages', 'messages.index', 'messages.*', 'envelope'];
    if ($user->isSuperAdmin()) {
        $menu['System'][] = ['Roles & Permissions', 'admin.roles.index', 'admin.roles.*', 'shield-lock-fill'];
    }
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
                    @php
                        $permission = match (true) {
                            str_starts_with($route, 'messages.') => null,
                            str_starts_with($route, 'reports.') => str_contains($route, 'attendance') ? 'reports.attendance' : (in_array($route, ['reports.fee-collection', 'reports.defaulters', 'reports.student-ledger'], true) ? 'reports.financial' : 'reports.academic'),
                            str_starts_with($route, 'admin.imports.') => 'imports.create',
                            str_starts_with($route, 'admin.staff-attendance.') => 'staff_attendance.read',
                            str_starts_with($route, 'admin.inventory.') => 'inventory.read',
                            str_starts_with($route, 'library.') => 'library.read',
                            str_starts_with($route, 'procurement.') => 'procurement.read',
                            $route === 'admin.students.index' => 'students.read',
                            $route === 'admin.students.create' => 'students.create',
                            $route === 'admin.guardians.index' => 'guardians.read',
                            $route === 'admin.classes.index' => 'classes.read',
                            $route === 'admin.classes.create' => 'classes.create',
                            $route === 'admin.teachers.index' => 'staff.read',
                            $route === 'admin.departments.index' => 'departments.read',
                            $route === 'admin.programs.index' => 'programs.read',
                            $route === 'admin.subjects.index' => 'subjects.read',
                            $route === 'admin.curricula.index' => 'curriculum.read',
                            $route === 'admin.academic-years.index' => 'academic_years.read',
                            $route === 'admin.terms.index' => 'terms.read',
                            $route === 'admin.class-subjects.index' => 'class_subjects.read',
                            $route === 'admin.timetable.index' => 'timetable.read',
                            $route === 'admin.attendance.index' => 'attendance.read',
                            $route === 'admin.exams.index' => 'exams.read',
                            $route === 'admin.term-results.index' => 'results.read',
                            $route === 'admin.grade-scale.edit' => 'grades.read',
                            $route === 'admin.announcements.index' => 'announcements.read',
                            str_starts_with($route, 'admin.') && str_ends_with($route, '.dashboard') => null,
                            default => null,
                        };
                        $roleRoutePrefix = $menuRole === 'parent' ? 'guardian.' : $menuRole.'.';
                        $canSeeRoute = str_starts_with($route, 'messages.')
                            || $user->isAdmin()
                            || ($permission && $user->can($permission))
                            || (! $permission && str_starts_with($route, $roleRoutePrefix));
                    @endphp
                    @if (\Illuminate\Support\Facades\Route::has($route) && $canSeeRoute)
                        <a class="nav-glass {{ request()->routeIs($pattern) ? 'active' : '' }}" href="{{ route($route) }}">
                            <i class="bi bi-{{ $icon }}"></i><span>{{ $label }}</span>
                        </a>
                    @endif
                @endforeach
            @endforeach
        </nav>

        <div class="user-card">
            <div class="avatar">{{ $initial }}</div>
            <div class="user-meta">
                <div class="user-name" title="{{ $user->name }}">{{ $user->name }}</div>
                <span class="role-pill">{{ $user->role === 'parent' ? 'guardian' : $user->role }}</span>
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
                <div class="topbar-sub">{{ $user->role === 'parent' ? 'Guardian' : $user->role }} portal</div>
            </div>
            @if (($branchContextOptions ?? collect())->isNotEmpty())
                <form method="POST" action="{{ route('admin.branch-context') }}" class="d-flex align-items-center gap-1">
                    @csrf
                    <label class="visually-hidden" for="active-branch">Active campus</label>
                    <select class="form-select form-select-sm" id="active-branch" name="branch_id">
                        @if ($user->isSuperAdmin())
                            <option value="all" @selected($allCampusesSelected ?? false)>All campuses</option>
                        @endif
                        @foreach ($branchContextOptions as $branchOption)
                            <option value="{{ $branchOption->id }}" @selected(($activeBranch?->id ?? null) === $branchOption->id)>{{ $branchOption->name }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-sm btn-outline-secondary">Switch campus</button>
                </form>
            @endif
            <span class="topbar-date"><i class="bi bi-calendar3 me-1"></i><time data-live-date data-timezone="{{ config('app.timezone') }}">{{ now()->format('D, d M Y') }}</time></span>
            <div class="dropdown">
                <button class="icon-btn position-relative" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
                    <i class="bi bi-bell"></i>
                    @if ($user->unreadNotifications()->exists())
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">{{ $user->unreadNotifications()->count() }}</span>
                    @endif
                </button>
                <div class="dropdown-menu dropdown-menu-end p-2" style="min-width: 320px">
                    <div class="d-flex justify-content-between align-items-center px-2 py-1">
                        <strong>Notifications</strong>
                        <form method="POST" action="{{ route('notifications.read-all') }}">
                            @csrf
                            <button class="btn btn-sm btn-link p-0">Mark all read</button>
                        </form>
                    </div>
                    @forelse ($user->notifications()->latest()->limit(5)->get() as $notification)
                        <form method="POST" action="{{ route('notifications.read', $notification) }}" class="border-top py-2 px-2">
                            @csrf
                            <button class="btn btn-link text-start text-decoration-none p-0 w-100">
                                <span class="d-block fw-semibold">{{ $notification->data['title'] ?? 'Notification' }}</span>
                                <span class="small text-muted">{{ \Illuminate\Support\Str::limit($notification->data['body'] ?? '', 90) }}</span>
                                @if (! $notification->read_at)<span class="badge bg-primary ms-1">New</span>@endif
                            </button>
                        </form>
                    @empty
                        <div class="text-muted small px-2 py-3">No notifications yet.</div>
                    @endforelse
                </div>
            </div>
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

            @php
                $activeAnnouncements = \App\Models\Announcement::activeForUser($user)->take(2)->get();
            @endphp
            @if ($activeAnnouncements->isNotEmpty())
                <div class="mb-4 no-print">
                    @foreach ($activeAnnouncements as $announcement)
                        <div class="alert alert-info d-flex align-items-start gap-2 shadow-sm border-0 mb-2">
                            <i class="bi bi-megaphone-fill fs-5 mt-1 text-primary"></i>
                            <div class="flex-grow-1">
                                <strong class="d-block">{{ $announcement->title }}</strong>
                                <div class="small">{{ $announcement->body }}</div>
                                <small class="text-muted">{{ $announcement->published_at->diffForHumans() }}</small>
                            </div>
                        </div>
                    @endforeach
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
