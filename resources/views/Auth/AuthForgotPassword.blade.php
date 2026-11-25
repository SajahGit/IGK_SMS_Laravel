<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    @include('Layouts.LayoutHead')
    <title>Forgot Password - IGK School</title>
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
                <p>Enter your registered administrator email address and we will send you a 6-digit verification code.</p>
            </div>
        </section>

        <section class="glass auth-card">
            <div class="d-flex justify-content-between align-items-start mb-1">
                <div>
                    <h2 class="h3 mb-1">Forgot password?</h2>
                    <p class="text-muted mb-4">Available for Super Admin and Institution Admin accounts. Students, teachers, and staff should contact an administrator.</p>
                </div>
            </div>

            @include('Layouts.LayoutErrors')

            @if (session('status'))
                <div class="alert alert-success mb-4">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" id="forgotPasswordForm">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="email">Registered Email</label>
                    <div class="input-icon">
                        <i class="bi bi-envelope"></i>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="admin@school.test"
                               class="form-control @error('email') is-invalid @enderror" required autofocus autocomplete="username">
                    </div>
                    @error('email') <div class="text-danger small mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div> @enderror
                </div>
                <button class="btn btn-primary w-100 py-2"><i class="bi bi-send me-1"></i> Send 6-Digit OTP</button>
            </form>

            <hr class="my-4">
            <div class="text-center small">
                <a href="{{ route('login') }}" class="text-decoration-none">Remember your password? <strong>Sign in</strong></a>
            </div>
        </section>
    </div>
</div>

<script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
