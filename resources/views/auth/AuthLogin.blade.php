<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    @include('layouts.LayoutHead')
    <title>Login - IGK School</title>
</head>
<body>
<div class="bg-orb-3"></div>
<div class="auth-wrap">
    <div class="auth-grid">

        <section class="glass auth-hero">
            <div>
                <div class="brand p-0">
                    <span class="brand-mark"><i class="bi bi-mortarboard-fill"></i></span>
                    <span><div class="brand-name">IGK School</div><div class="brand-sub">MANAGEMENT SYSTEM</div></span>
                </div>
                <h1>Everything your school needs, in one place.</h1>
                <p>Manage students, mark attendance, record exam marks and share results with parents, all from one simple dashboard.</p>
            </div>
            <div>
                <div class="feature"><i class="bi bi-shield-check"></i><span>Separate access for admin, teacher, student and parent</span></div>
                <div class="feature"><i class="bi bi-clipboard2-check"></i><span>Attendance and marks entered once, visible everywhere</span></div>
                <div class="feature"><i class="bi bi-file-earmark-bar-graph"></i><span>Printable reports with CSV download</span></div>
            </div>
        </section>

        <section class="glass auth-card">
            <div class="d-lg-none brand p-0 mb-3">
                <span class="brand-mark"><i class="bi bi-mortarboard-fill"></i></span>
                <span><div class="brand-name">IGK School</div><div class="brand-sub">MANAGEMENT SYSTEM</div></span>
            </div>
            <div class="d-flex justify-content-between align-items-start mb-1">
                <div>
                    <h2 class="h3 mb-1">Welcome back</h2>
                    <p class="text-muted mb-4">Sign in to continue</p>
                </div>
                <button type="button" class="icon-btn" data-theme-toggle title="Switch theme"><i data-theme-icon class="bi bi-sun"></i></button>
            </div>

            <form method="POST" action="{{ route('login.attempt') }}" id="loginForm">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <div class="input-icon">
                        <i class="bi bi-envelope"></i>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="you@school.test"
                               class="form-control @error('email') is-invalid @enderror" required autofocus autocomplete="username">
                    </div>
                    @error('email') <div class="text-danger small mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-icon">
                        <i class="bi bi-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Your password"
                               class="form-control @error('password') is-invalid @enderror" required autocomplete="current-password">
                        <button type="button" class="pw-toggle" id="pwToggle" aria-label="Show password"><i class="bi bi-eye"></i></button>
                    </div>
                    @error('password') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>
                <button class="btn btn-primary w-100 py-2"><i class="bi bi-box-arrow-in-right me-1"></i> Sign in</button>
            </form>

            <hr class="my-4">
            <div class="small text-muted mb-2">Demo accounts (click to fill, password is <code>password</code>)</div>
            <div class="d-flex flex-wrap gap-2">
                <span class="demo-chip" data-email="admin@school.test"><i class="bi bi-shield-lock"></i>Admin</span>
                <span class="demo-chip" data-email="teacher@school.test"><i class="bi bi-person-workspace"></i>Teacher</span>
                <span class="demo-chip" data-email="student@school.test"><i class="bi bi-person-badge"></i>Student</span>
                <span class="demo-chip" data-email="parent@school.test"><i class="bi bi-person-hearts"></i>Parent</span>
            </div>
        </section>
    </div>
</div>

<script src="{{ asset('js/app.js') }}"></script>
<script>
    document.querySelectorAll('.demo-chip').forEach(function (c) {
        c.addEventListener('click', function () {
            document.getElementById('email').value = c.dataset.email;
            document.getElementById('password').value = 'password';
        });
    });
    var pw = document.getElementById('password'), tg = document.getElementById('pwToggle');
    tg.addEventListener('click', function () {
        var show = pw.type === 'password';
        pw.type = show ? 'text' : 'password';
        tg.innerHTML = '<i class="bi bi-eye' + (show ? '-slash' : '') + '"></i>';
    });
</script>
</body>
</html>
