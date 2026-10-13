<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root { --brand: #0f766e; --brand-dark: #115e59; }
        body { font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
        .btn { display: inline-flex; justify-content: center; width: 100%; padding: .6rem 1rem; border-radius: .5rem; font-weight: 600; background: var(--brand); color: #fff; border: 0; cursor: pointer; }
        .btn:hover { background: var(--brand-dark); }
        .input { width: 100%; border: 1px solid #cbd5e1; border-radius: .5rem; padding: .55rem .75rem; font-size: .95rem; }
        .input:focus { outline: 2px solid var(--brand); outline-offset: -1px; }
        .label { display: block; font-size: .8rem; font-weight: 600; color: #334155; margin-bottom: .25rem; }
    </style>
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center p-4">
<div class="w-full max-w-md">
    <div class="text-center mb-6">
        <div class="text-2xl font-bold text-slate-900">School Management System</div>
        <div class="text-sm text-slate-500">Sign in to continue</div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.attempt') }}" class="space-y-4">
            @csrf
            <div>
                <label class="label" for="email">Email</label>
                <input id="email" class="input" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            </div>
            <div>
                <label class="label" for="password">Password</label>
                <input id="password" class="input" type="password" name="password" required autocomplete="current-password">
            </div>
            <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" value="1" class="rounded border-slate-300"> Remember me
            </label>
            <button type="submit" class="btn">Log in</button>
        </form>
    </div>

    @if (app()->environment('local'))
        <div class="mt-4 rounded-xl border border-slate-200 bg-white p-4 text-xs text-slate-600">
            <div class="mb-2 font-semibold text-slate-700">Demo accounts (password: <code>password</code>)</div>
            <table class="w-full">
                <tr><td class="py-0.5">Admin</td><td>admin@school.test</td></tr>
                <tr><td class="py-0.5">Teacher</td><td>teacher@school.test</td></tr>
                <tr><td class="py-0.5">Student</td><td>student@school.test</td></tr>
                <tr><td class="py-0.5">Parent</td><td>parent@school.test</td></tr>
            </table>
        </div>
    @endif
</div>
</body>
</html>
