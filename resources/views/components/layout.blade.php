@props(['title' => ''])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' - ' : '' }}{{ config('app.name', 'School Management System') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root { --brand: #0f766e; --brand-dark: #115e59; --ink: #0f172a; --line: #e2e8f0; }
        body { font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
        .btn { display: inline-flex; align-items: center; gap: .4rem; padding: .5rem .9rem; border-radius: .5rem; font-size: .875rem; font-weight: 600; border: 1px solid transparent; cursor: pointer; line-height: 1.25rem; text-decoration: none; }
        .btn-primary { background: var(--brand); color: #fff; }
        .btn-primary:hover { background: var(--brand-dark); }
        .btn-secondary { background: #fff; color: var(--ink); border-color: #cbd5e1; }
        .btn-secondary:hover { background: #f1f5f9; }
        .btn-danger { background: #fff; color: #b91c1c; border-color: #fecaca; }
        .btn-danger:hover { background: #fef2f2; }
        .btn-sm { padding: .25rem .6rem; font-size: .8rem; }
        .input { width: 100%; border: 1px solid #cbd5e1; border-radius: .5rem; padding: .5rem .7rem; font-size: .9rem; background: #fff; }
        .input:focus { outline: 2px solid var(--brand); outline-offset: -1px; border-color: var(--brand); }
        .label { display: block; font-size: .8rem; font-weight: 600; color: #334155; margin-bottom: .25rem; }
        .tbl { width: 100%; font-size: .875rem; border-collapse: collapse; }
        .tbl th { text-align: left; font-size: .75rem; color: #475569; background: #f8fafc; padding: .6rem .8rem; border-bottom: 1px solid var(--line); white-space: nowrap; }
        .tbl td { padding: .6rem .8rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .tbl tr:hover td { background: #f8fafc; }
        .nav-link { display: block; padding: .5rem .9rem; border-radius: .5rem; font-size: .875rem; color: #cbd5e1; text-decoration: none; }
        .nav-link:hover { background: #1e293b; color: #fff; }
        .nav-link.active { background: var(--brand); color: #fff; font-weight: 600; }
        @media print { .no-print { display: none !important; } body { background: #fff; } }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 antialiased">
<div class="min-h-screen md:flex">

    {{-- Sidebar --}}
    <aside id="sidebar" class="no-print hidden md:block bg-slate-900 text-white md:w-60 md:shrink-0 md:min-h-screen">
        <div class="px-4 py-5 border-b border-slate-800">
            <div class="text-lg font-bold tracking-tight">School Management</div>
            <div class="text-xs text-slate-400">{{ ucfirst(auth()->user()->role) }} portal</div>
        </div>
        <x-layout.sidebar />
    </aside>

    <div class="flex-1 min-w-0">
        {{-- Header --}}
        <header class="no-print bg-white border-b border-slate-200 px-4 py-3 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <button type="button" class="md:hidden btn btn-secondary btn-sm"
                        onclick="document.getElementById('sidebar').classList.toggle('hidden')">Menu</button>
                @php $term = \App\Models\Term::current(); @endphp
                <span class="text-sm text-slate-600">
                    {{ $term ? $term->full_name : 'No term set' }}
                </span>
            </div>
            <div class="flex items-center gap-3 text-sm">
                <span class="hidden sm:inline text-slate-600">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-sm">Log out</button>
                </form>
            </div>
        </header>

        <main class="p-4 md:p-6 max-w-7xl">
            @if (session('success'))
                <x-ui.alert type="success">{{ session('success') }}</x-ui.alert>
            @endif
            @if (session('error'))
                <x-ui.alert type="error">{{ session('error') }}</x-ui.alert>
            @endif
            @if ($errors->any())
                <x-ui.alert type="error">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-ui.alert>
            @endif

            {{ $slot }}
        </main>
    </div>
</div>
</body>
</html>
