@extends('layouts.LayoutApp')
@section('title', 'Add Parent')
@section('content')
<h3 class="mb-3">Add parent</h3>
<div class="card shadow-sm"><div class="card-body">
    <form method="POST" action="{{ route('admin.guardians.store') }}">
        @csrf
        @include('admin.AdminGuardiansForm')
    </form>
</div></div>
@endsection
