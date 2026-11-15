<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    @include('Layouts.LayoutHead')
    <title>Set Up Two-Factor Authentication - IGK School</title>
</head>
<body>
<div class="bg-orb-3"></div>
<div class="auth-wrap">
    <div class="auth-grid auth-grid-single" style="max-width: 520px; grid-template-columns: 1fr; margin: 0 auto;">
        <section class="glass auth-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="brand p-0 m-0">
                    <span class="brand-mark"><i class="bi bi-mortarboard-fill"></i></span>
                    <span class="text-start">
                        <div class="brand-name">IGK School</div>
                        <div class="brand-sub">MANAGEMENT SYSTEM</div>
                    </span>
                </div>
                <button type="button" class="icon-btn" data-theme-toggle title="Switch theme">
                    <i data-theme-icon class="bi bi-sun"></i>
                </button>
            </div>

            <div class="text-center mb-4">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary mb-3" style="width: 60px; height: 60px; font-size: 1.75rem;">
                    <i class="bi bi-shield-check"></i>
                </div>
                <h1 class="h3 mb-1">Two-Factor Authentication</h1>
                <p class="text-muted small mb-0">Enhance your account security with multi-factor verification.</p>
            </div>

            @if ($recoveryCodes)
                <div class="alert alert-warning small mb-3">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    <strong>Save these recovery codes somewhere safe.</strong> Each one-time code can only be used once if you ever lose access to your authenticator app.
                </div>
                <ul class="list-group mb-4" aria-label="One-time recovery codes">
                    @foreach ($recoveryCodes as $recoveryCode)
                        <li class="list-group-item font-monospace text-center py-2 bg-body-tertiary">{{ $recoveryCode }}</li>
                    @endforeach
                </ul>
                <form method="POST" action="{{ route('two-factor.complete') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary w-100 py-2">
                        <i class="bi bi-check-circle me-1"></i> I Saved My Recovery Codes
                    </button>
                </form>
            @elseif ($enabled)
                <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-check-circle-fill fs-5"></i>
                    <div>Two-factor authentication is currently <strong>active and protecting</strong> this account.</div>
                </div>
                @if (auth()->check())
                    <p class="text-muted small mb-3">Regenerate new recovery codes if your saved codes may have been exposed. Your authenticator app will remain unchanged.</p>
                    <form method="POST" action="{{ route('two-factor.recovery-codes.regenerate') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small" for="current_password">Current Password</label>
                            <input class="form-control @error('current_password') is-invalid @enderror" id="current_password"
                                   name="current_password" type="password" autocomplete="current-password" required>
                            @error('current_password') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label small" for="code">Authentication Code</label>
                            <input class="form-control text-center font-monospace fs-5 @error('code') is-invalid @enderror" id="code" name="code"
                                   inputmode="numeric" autocomplete="one-time-code" maxlength="40" placeholder="Enter code" required style="letter-spacing: 0.15rem;">
                            @error('code') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2">
                            <i class="bi bi-arrow-repeat me-1"></i> Regenerate Recovery Codes
                        </button>
                    </form>
                @endif
            @else
                <p class="text-muted small mb-3">Add this secret key to your authenticator app (such as Google Authenticator, Microsoft Authenticator, or 1Password).</p>
                @if ($demoSetupCode)
                    <div class="alert alert-info small mb-3">
                        <i class="bi bi-info-circle me-1"></i> Local demo first-time code: <strong class="font-monospace">{{ $demoSetupCode }}</strong>.
                    </div>
                @endif
                <div class="p-3 mb-3 bg-body-tertiary rounded border text-center">
                    <div class="text-muted small mb-1">Authenticator Setup Key</div>
                    <code class="fs-6 user-select-all text-break fw-bold text-primary">{{ $secret }}</code>
                </div>
                <form method="POST" action="{{ route('two-factor.setup') }}">
                    @csrf
                    @if (auth()->check())
                        <div class="mb-3">
                            <label class="form-label small" for="current_password">Current Password</label>
                            <input class="form-control @error('current_password') is-invalid @enderror" id="current_password"
                                   name="current_password" type="password" autocomplete="current-password" required>
                            @error('current_password') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label small" for="code">Code from Authenticator</label>
                        <input class="form-control text-center font-monospace fs-5 @error('code') is-invalid @enderror" id="code" name="code"
                               inputmode="numeric" autocomplete="one-time-code" maxlength="40"
                               placeholder="Enter code" required autofocus style="letter-spacing: 0.15rem;">
                        @error('code') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2">
                        <i class="bi bi-shield-lock-fill me-1"></i> Enable Two-Factor Authentication
                    </button>
                </form>
            @endif
        </section>
    </div>
</div>
<script>
    const themeBtn = document.querySelector('[data-theme-toggle]');
    const themeIcon = document.querySelector('[data-theme-icon]');
    if (themeBtn && themeIcon) {
        themeBtn.addEventListener('click', () => {
            const html = document.documentElement;
            const nextTheme = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-bs-theme', nextTheme);
            localStorage.setItem('theme', nextTheme);
            themeIcon.className = nextTheme === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
        });
        const saved = localStorage.getItem('theme');
        if (saved) {
            document.documentElement.setAttribute('data-bs-theme', saved);
            themeIcon.className = saved === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
        }
    }
</script>
</body>
</html>
