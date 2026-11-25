<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    @include('Layouts.LayoutHead')
    <title>Verify Reset OTP - IGK School</title>
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
                <h1>Verify your email</h1>
                <p>Enter the 6-digit verification code sent to your email address. The code expires in 5 minutes.</p>
            </div>
        </section>

        <section class="glass auth-card">
            <div class="d-flex justify-content-between align-items-start mb-1">
                <div>
                    <h2 class="h3 mb-1">Enter 6-Digit OTP</h2>
                    <p class="text-muted small mb-3">Verification email target: <strong>{{ $email }}</strong></p>
                </div>
            </div>

            @include('Layouts.LayoutErrors')

            @if (session('status'))
                <div class="alert alert-info small mb-3">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('password.otp.verify') }}" id="verifyOtpForm">
                @csrf
                <div class="mb-3">
                    <label class="form-label small fw-semibold" for="otp">6-Digit Email OTP</label>
                    <div class="input-icon">
                        <i class="bi bi-shield-check"></i>
                        <input type="text" id="otp" name="otp" value="{{ old('otp') }}"
                               inputmode="numeric" pattern="\d{6}" maxlength="6" placeholder="123456"
                               class="form-control text-center font-monospace fs-4 @error('otp') is-invalid @enderror"
                               required autofocus autocomplete="one-time-code" style="letter-spacing: 0.35rem; font-weight: 600;">
                    </div>
                    @error('otp') <div class="text-danger small mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div> @enderror
                    <div class="form-text small text-muted">Valid for 5 minutes. Maximum 5 attempts.</div>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                    <i class="bi bi-check2-circle me-1"></i> Verify OTP
                </button>
            </form>

            <form method="POST" action="{{ route('password.otp.resend') }}" class="mt-3" id="resendOtpForm">
                @csrf
                <button type="submit" class="btn btn-outline-secondary w-100 btn-sm" id="resendOtpBtn"
                        @if ($cooldownRemaining > 0) disabled @endif>
                    <i class="bi bi-arrow-repeat me-1"></i>
                    <span id="resendOtpLabel">
                        @if ($cooldownRemaining > 0)
                            Resend OTP ({{ $cooldownRemaining }}s)
                        @else
                            Resend OTP
                        @endif
                    </span>
                </button>
            </form>

            <hr class="my-4">
            <div class="text-center small d-flex justify-content-between">
                <a href="{{ route('password.forgot') }}" class="text-decoration-none"><i class="bi bi-arrow-left me-1"></i> Change Email</a>
                <a href="{{ route('login') }}" class="text-decoration-none">Back to <strong>Sign in</strong></a>
            </div>
        </section>
    </div>
</div>

<script src="{{ asset('js/app.js') }}"></script>
@if ($cooldownRemaining > 0)
<script>
    (function () {
        var remaining = {{ (int) $cooldownRemaining }};
        var btn = document.getElementById('resendOtpBtn');
        var label = document.getElementById('resendOtpLabel');
        if (!btn || !label) return;
        var timer = setInterval(function () {
            remaining -= 1;
            if (remaining <= 0) {
                clearInterval(timer);
                btn.disabled = false;
                label.textContent = 'Resend OTP';
            } else {
                label.textContent = 'Resend OTP (' + remaining + 's)';
            }
        }, 1000);
    })();
</script>
@endif
</body>
</html>
