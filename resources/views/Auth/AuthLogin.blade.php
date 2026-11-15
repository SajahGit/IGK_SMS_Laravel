<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    @include('Layouts.LayoutHead')
    <title>Sign In - IGK School Management System</title>
    <style>
        .feature-compact {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--glass-border);
            transition: transform 0.15s ease, background-color 0.15s ease;
        }
        [data-bs-theme="light"] .feature-compact {
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid rgba(0, 0, 0, 0.08);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
        }
        .feature-compact:hover {
            transform: translateX(4px);
            background: rgba(255, 255, 255, 0.07);
        }
        .demo-chip.active-chip {
            border-color: var(--bs-primary) !important;
            background: rgba(59, 130, 246, 0.15) !important;
            color: var(--bs-primary) !important;
        }
    </style>
</head>
<body>
<div class="bg-orb-3"></div>
<div class="auth-wrap">
    <div class="auth-grid" style="max-width: 1080px;">

        <!-- Left Hero Section: Institutional Highlights -->
        <section class="glass auth-hero p-4 p-xl-5 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="brand p-0 m-0">
                        <span class="brand-mark"><i class="bi bi-mortarboard-fill"></i></span>
                        <span>
                            <div class="brand-name">IGK School</div>
                            <div class="brand-sub">MANAGEMENT SYSTEM</div>
                        </span>
                    </div>
                    <span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 small">
                        <i class="bi bi-patch-check-fill me-1"></i> Multi-Campus SIS
                    </span>
                </div>

                <h1 class="h2 fw-bold text-body mb-2" style="letter-spacing: -0.02em;">Everything your school needs, in one place.</h1>
                <p class="text-muted small mb-4" style="line-height: 1.5; max-width: 460px;">
                    Manage students, mark attendance, record exam marks, and generate institutional reports from one unified dashboard.
                </p>

                <!-- Compact Value Highlights -->
                <div class="d-flex flex-column gap-3 my-4">
                    <!-- Feature 1: Separate access for admin, teacher, student and guardian -->
                    <div class="feature-compact d-flex align-items-center gap-3 p-3 rounded-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-primary flex-shrink-0"
                             style="width: 42px; height: 42px; background: rgba(59, 130, 246, 0.15); font-size: 1.35rem;">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <span class="text-body fw-semibold" style="font-size: 0.95rem;">
                            Separate access for admin, teacher, student and guardian
                        </span>
                    </div>

                    <!-- Feature 2: Attendance and marks entered once, visible everywhere -->
                    <div class="feature-compact d-flex align-items-center gap-3 p-3 rounded-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-success flex-shrink-0"
                             style="width: 42px; height: 42px; background: rgba(16, 185, 129, 0.15); font-size: 1.35rem;">
                            <i class="bi bi-clipboard2-check"></i>
                        </div>
                        <span class="text-body fw-semibold" style="font-size: 0.95rem;">
                            Attendance and marks entered once, visible everywhere
                        </span>
                    </div>

                    <!-- Feature 3: Printable reports with CSV download -->
                    <div class="feature-compact d-flex align-items-center gap-3 p-3 rounded-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-warning flex-shrink-0"
                             style="width: 42px; height: 42px; background: rgba(245, 158, 11, 0.15); font-size: 1.35rem;">
                            <i class="bi bi-file-earmark-bar-graph"></i>
                        </div>
                        <span class="text-body fw-semibold" style="font-size: 0.95rem;">
                            Printable reports with CSV download
                        </span>
                    </div>
                </div>
            </div>

            <!-- Trust and Security Ribbon -->
            <div class="d-flex align-items-center justify-content-between pt-3 mt-3 border-top border-secondary-subtle text-muted small" style="font-size: 0.78rem;">
                <div><i class="bi bi-shield-lock-fill text-primary me-1"></i> Two-Factor Auth</div>
                <div><i class="bi bi-diagram-3-fill text-info me-1"></i> Multi-Campus Scoped</div>
                <div><i class="bi bi-journal-check text-success me-1"></i> Audit Compliance</div>
            </div>
        </section>

        <!-- Right Sign-In Form -->
        <section class="glass auth-card p-4 p-xl-5">
            <!-- Mobile Brand Header -->
            <div class="d-lg-none brand p-0 mb-3">
                <span class="brand-mark"><i class="bi bi-mortarboard-fill"></i></span>
                <span><div class="brand-name">IGK School</div><div class="brand-sub">MANAGEMENT SYSTEM</div></span>
            </div>

            <!-- Mobile Feature Highlights Badge (Visible only on small screens) -->
            <div class="d-lg-none mb-3 p-2 rounded-2 bg-body-tertiary border text-center small text-muted">
                <div class="fw-semibold text-body mb-1">Unified Multi-Role SIS</div>
                <div style="font-size: 0.75rem;">Separate Portals • Live Attendance & Marks • A4/CSV Reports</div>
            </div>

            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h2 class="h3 fw-bold mb-1">Welcome back</h2>
                    <p class="text-muted small mb-0">Sign in to your school portal account</p>
                </div>
                <button type="button" class="icon-btn" data-theme-toggle title="Switch theme">
                    <i data-theme-icon class="bi bi-sun"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('login.attempt') }}" id="loginForm">
                @csrf
                <div class="mb-3">
                    <label class="form-label small fw-medium" for="email">Email Address</label>
                    <div class="input-icon">
                        <i class="bi bi-envelope"></i>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="you@school.test"
                               class="form-control @error('email') is-invalid @enderror" required autofocus autocomplete="username">
                    </div>
                    @error('email')
                        <div class="text-danger small mt-1 d-flex align-items-center gap-1">
                            <i class="bi bi-exclamation-circle-fill"></i>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <label class="form-label small fw-medium" for="password">Password</label>
                    </div>
                    <div class="input-icon">
                        <i class="bi bi-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Enter your password"
                               class="form-control @error('password') is-invalid @enderror" required autocomplete="current-password">
                        <button type="button" class="pw-toggle" id="pwToggle" aria-label="Show password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    @error('password')
                        <div class="text-danger small mt-1 d-flex align-items-center gap-1">
                            <i class="bi bi-exclamation-circle-fill"></i>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label small" for="remember">Remember me</label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-box-arrow-in-right"></i>
                    <span>Sign In to School Portal</span>
                </button>
            </form>

            @if (app()->environment(['local', 'testing']))
                <hr class="my-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small text-muted fw-medium">Local demo accounts (click to fill the account's password)</span>
                    <span id="chipNotice" class="badge bg-success-subtle text-success border border-success-subtle" style="display: none;">
                        <i class="bi bi-check-circle-fill me-1"></i> Filled
                    </span>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <span class="demo-chip" data-email="superadmin@school.test" data-password="password">
                        <i class="bi bi-shield-lock-fill text-danger"></i>Super Admin
                    </span>
                    <span class="demo-chip" data-email="admin@school.test" data-password="password">
                        <i class="bi bi-shield-check text-primary"></i>Institution Admin
                    </span>
                    <span class="demo-chip" data-email="registrar@school.test" data-password="password">
                        <i class="bi bi-person-badge text-info"></i>Registrar
                    </span>
                    <span class="demo-chip" data-email="teacher@school.test" data-password="password">
                        <i class="bi bi-person-workspace text-success"></i>Teacher
                    </span>
                    <span class="demo-chip" data-email="accountant@school.test" data-password="password">
                        <i class="bi bi-currency-dollar text-warning"></i>Accountant
                    </span>
                    <span class="demo-chip" data-email="student@school.test" data-password="password">
                        <i class="bi bi-mortarboard text-info"></i>Student
                    </span>
                    <span class="demo-chip" data-email="guardian@school.test" data-password="password">
                        <i class="bi bi-people text-secondary"></i>Guardian
                    </span>
                </div>
            @endif
        </section>
    </div>
</div>

<script src="{{ asset('js/app.js') }}"></script>
<script>
    document.querySelectorAll('.demo-chip').forEach(function (c) {
        c.addEventListener('click', function () {
            document.querySelectorAll('.demo-chip').forEach(function (el) { el.classList.remove('active-chip'); });
            c.classList.add('active-chip');

            var emailInput = document.getElementById('email');
            var passwordInput = document.getElementById('password');
            if (emailInput) emailInput.value = c.dataset.email;
            if (passwordInput) passwordInput.value = c.dataset.password;

            var notice = document.getElementById('chipNotice');
            if (notice) {
                notice.style.display = 'inline-block';
                notice.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> ' + c.innerText.trim();
            }
        });
    });

    var pw = document.getElementById('password'), tg = document.getElementById('pwToggle');
    if (tg && pw) {
        tg.addEventListener('click', function () {
            var show = pw.type === 'password';
            pw.type = show ? 'text' : 'password';
            tg.innerHTML = '<i class="bi bi-eye' + (show ? '-slash' : '') + '"></i>';
        });
    }
</script>
</body>
</html>
