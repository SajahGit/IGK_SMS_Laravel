<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    @include('Layouts.LayoutHead')
    <title>Access denied - IGK School</title>
</head>
<body>
<div class="bg-orb-3"></div>
<div class="auth-wrap">
    <div class="glass text-center p-5" style="max-width: 520px; width: 100%;">
        <div class="err-code">403</div>
        @php $locked = str_contains(strtolower($exception->getMessage()), 'locked'); @endphp
        <h2 class="h3 mt-3">{{ $locked ? 'Marks are locked' : 'Access denied' }}</h2>
        <p class="text-muted">{{ $exception->getMessage() ?: 'You are not allowed to open this page.' }}</p>
        @if ($locked)<p class="text-muted small">Ask an admin to unpublish this exam if the marks need to be changed.</p>@endif
        <div class="d-flex justify-content-center gap-2 mt-4">
            <a href="javascript:history.back()" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Go back</a>
            <a href="{{ url('/') }}" class="btn btn-primary"><i class="bi bi-house me-1"></i>Home</a>
        </div>
    </div>
</div>
<script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
