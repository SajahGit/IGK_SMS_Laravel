<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    @include('Layouts.LayoutHead')
    <title>Reset Password - IGK School</title>
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
                <h1>Reset your password</h1>
                <p>Choose a new password for your account.</p>
            </div>
        </section>

        <section class="glass auth-card">
            <div class="d-flex justify-content-between align-items-start mb-1">
                <div>
                    <h2 class="h3 mb-1">Reset Password</h2>
                    <p class="text-muted mb-4">Enter your new password below</p>
                </div>
            </div>

            @include('Layouts.LayoutErrors')

            <form method="POST" action="{{ route('password.update') }}" id="resetPasswordForm">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $email }}">

                <div class="mb-3">
                    <label class="form-label" for="password">New Password</label>
                    <div class="input-icon">
                        <i class="bi bi-lock"></i>
                        <input type="password" id="password" name="password" placeholder="New password"
                               class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password">
                        <button type="button" class="pw-toggle" id="pwToggle" aria-label="Show password"><i class="bi bi-eye"></i></button>
                    </div>
                    @error('password') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password_confirmation">Confirm Password</label>
                    <div class="input-icon">
                        <i class="bi bi-lock-fill"></i>
                        <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Confirm new password"
                               class="form-control @error('password_confirmation') is-invalid @enderror" required autocomplete="new-password">
                    </div>
                    @error('password_confirmation') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <button class="btn btn-primary w-100 py-2"><i class="bi bi-check2-circle me-1"></i> Reset Password</button>
            </form>

            <hr class="my-4">
            <div class="text-center small">
                <a href="{{ route('login') }}" class="text-decoration-none">Remember your password? <strong>Sign in</strong></a>
            </div>
        </section>
    </div>
</div>

<script src="{{ asset('js/app.js') }}"></script>
<script>
    var pw = document.getElementById('password'), tg = document.getElementById('pwToggle');
    tg.addEventListener('click', function () {
        var show = pw.type === 'password';
        pw.type = show ? 'text' : 'password';
        tg.innerHTML = '<i class="bi bi-eye' + (show ? '-slash' : '') + '"></i>';
    });
</script>
</body>
</html>
