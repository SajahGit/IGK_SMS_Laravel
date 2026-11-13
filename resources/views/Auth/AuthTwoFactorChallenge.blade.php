<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    @include('Layouts.LayoutHead')
    <title>Two-factor verification - IGK School</title>
</head>
<body>
<div class="bg-orb-3"></div>
<div class="auth-wrap">
    <div class="auth-grid">
        <section class="glass auth-card">
            <h1 class="h3 mb-2">Two-factor verification</h1>
            <p class="text-muted mb-4">Enter the 6-digit code from your authenticator app or one of your recovery codes.</p>
            <form method="POST" action="{{ route('two-factor.challenge') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="code">Authentication or recovery code</label>
                    <input class="form-control @error('code') is-invalid @enderror" id="code" name="code"
                           value="{{ old('code') }}" inputmode="numeric" autocomplete="one-time-code"
                           maxlength="40" required autofocus>
                    @error('code') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <button class="btn btn-primary w-100 py-2">Verify and sign in</button>
            </form>
            <form method="POST" action="{{ route('two-factor.cancel') }}" class="mt-3">
                @csrf
                <button class="btn btn-outline-secondary w-100">
                    <i class="bi bi-arrow-left me-1"></i>Cancel sign in
                </button>
            </form>
        </section>
    </div>
</div>
</body>
</html>
