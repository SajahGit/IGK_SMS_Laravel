<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') - {{ config('app.name') }}</title>
    <style>
        body { margin: 0; font-family: ui-sans-serif, system-ui, sans-serif; background: #f1f5f9; color: #0f172a; display: flex; min-height: 100vh; align-items: center; justify-content: center; }
        .box { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 2.5rem; max-width: 28rem; text-align: center; }
        .code { font-size: 3rem; font-weight: 800; color: #0f766e; }
        a { display: inline-block; margin-top: 1.25rem; background: #0f766e; color: #fff; padding: .55rem 1rem; border-radius: 8px; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
<div class="box">
    <div class="code">@yield('code')</div>
    <h1 style="font-size:1.1rem;margin:.5rem 0">@yield('title')</h1>
    <p style="color:#475569;font-size:.9rem">@yield('message')</p>
    <a href="{{ url('/dashboard') }}">Back to dashboard</a>
</div>
</body>
</html>
