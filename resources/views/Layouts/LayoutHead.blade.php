<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<script>
    // apply saved theme before the page paints (no flash)
    (function () {
        var t = 'dark';
        try { t = localStorage.getItem('igk-theme') || 'dark'; } catch (e) {}
        document.documentElement.setAttribute('data-bs-theme', t);
    })();
</script>
<link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
<link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
<link href="{{ asset('vendor/inter/inter.css') }}" rel="stylesheet">
<link href="{{ asset('css/glass.css') }}" rel="stylesheet">
