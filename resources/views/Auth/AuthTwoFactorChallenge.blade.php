<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    @include('Layouts.LayoutHead')
    <title>Two-Factor Verification - IGK School</title>
</head>
<body>
<div class="bg-orb-3"></div>
<div class="auth-wrap">
    <div class="auth-grid auth-grid-single" style="max-width: 460px; grid-template-columns: 1fr; margin: 0 auto;">
        <section class="glass auth-card text-center">
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

            <div class="my-2">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary mb-3" style="width: 60px; height: 60px; font-size: 1.75rem;">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <h1 class="h3 mb-2">Two-Factor Verification</h1>
                <p class="text-muted small mb-4">
                    Enter the code from your authenticator app or one of your emergency recovery codes.
                </p>
            </div>

            <form method="POST" action="{{ route('two-factor.challenge') }}" class="text-start">
                @csrf
                @if (!empty($demoCode))
                    <div class="alert alert-info py-2 px-3 small mb-3 text-start d-flex align-items-center justify-content-between">
                        <div>
                            <i class="bi bi-info-circle-fill me-1 text-primary"></i>
                            <span>Local demo code: <strong class="font-monospace text-primary">{{ $demoCode }}</strong></span>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 font-monospace" onclick="document.getElementById('code').value='{{ $demoCode }}';">Auto-fill</button>
                    </div>
                @endif
                <div class="mb-3">
                    <label class="form-label text-start d-block" for="code">Authentication Code</label>
                    <div class="input-icon">
                        <i class="bi bi-key-fill"></i>
                        <input class="form-control text-center font-monospace fs-5 @error('code') is-invalid @enderror"
                               id="code" name="code" value="{{ old('code') }}" inputmode="numeric" autocomplete="one-time-code"
                               placeholder="Enter code" maxlength="40" required autofocus style="letter-spacing: 0.15rem;">
                    </div>
                    @error('code')
                        <div class="text-danger small mt-2 d-flex align-items-center gap-1">
                            <i class="bi bi-exclamation-circle-fill"></i>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Verify & Sign In
                </button>
            </form>

            <form method="POST" action="{{ route('two-factor.cancel') }}" class="mt-3">
                @csrf
                <button type="submit" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-arrow-left me-1"></i> Cancel Sign In
                </button>
            </form>
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
