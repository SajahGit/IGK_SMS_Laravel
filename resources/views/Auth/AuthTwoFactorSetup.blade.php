<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    @include('Layouts.LayoutHead')
    <title>Set up two-factor authentication - IGK School</title>
</head>
<body>
<div class="bg-orb-3"></div>
<div class="auth-wrap">
    <div class="auth-grid">
        <section class="glass auth-card">
            <h1 class="h3 mb-2">Two-factor authentication</h1>

            @if ($recoveryCodes)
                <p class="text-muted">Save these recovery codes somewhere private. Each code works once; they cannot be shown again.</p>
                <ul class="list-group mb-4" aria-label="One-time recovery codes">
                    @foreach ($recoveryCodes as $recoveryCode)
                        <li class="list-group-item font-monospace">{{ $recoveryCode }}</li>
                    @endforeach
                </ul>
                <form method="POST" action="{{ route('two-factor.complete') }}">
                    @csrf
                    <button class="btn btn-primary w-100 py-2">I saved my recovery codes</button>
                </form>
            @elseif ($enabled)
                <p class="text-success mb-0">Two-factor authentication is enabled for this account.</p>
                @if (auth()->check())
                    <p class="text-muted mt-3">Regenerate recovery codes if your saved codes may have been exposed. Your authenticator app will remain unchanged.</p>
                    <form method="POST" action="{{ route('two-factor.recovery-codes.regenerate') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="current_password">Current password</label>
                            <input class="form-control @error('current_password') is-invalid @enderror" id="current_password"
                                   name="current_password" type="password" autocomplete="current-password" required>
                            @error('current_password') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="code">6-digit authentication code</label>
                            <input class="form-control @error('code') is-invalid @enderror" id="code" name="code"
                                   inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required>
                            @error('code') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <button class="btn btn-primary w-100 py-2">Regenerate recovery codes</button>
                    </form>
                @endif
            @else
                <p class="text-muted">Add this key to an authenticator app before continuing. Future sign-ins require that app's rotating 6-digit code.</p>
                @if ($demoSetupCode)
                    <p class="alert alert-warning">Local demo first-time setup code: <strong class="font-monospace">{{ $demoSetupCode }}</strong>. Use it only to finish this initial setup after adding the key to your authenticator. It cannot be used for future sign-ins.</p>
                @endif
                <div class="alert alert-secondary font-monospace text-break" aria-label="Authenticator setup key">{{ $secret }}</div>
                <form method="POST" action="{{ route('two-factor.setup') }}">
                    @csrf
                    @if (auth()->check())
                        <div class="mb-3">
                            <label class="form-label" for="current_password">Current password</label>
                            <input class="form-control @error('current_password') is-invalid @enderror" id="current_password"
                                   name="current_password" type="password" autocomplete="current-password" required>
                            @error('current_password') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label" for="code">6-digit authentication code</label>
                        <input class="form-control @error('code') is-invalid @enderror" id="code" name="code"
                               inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6"
                               required autofocus>
                        @error('code') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <button class="btn btn-primary w-100 py-2">Enable two-factor authentication</button>
                </form>
            @endif
        </section>
    </div>
</div>
</body>
</html>
