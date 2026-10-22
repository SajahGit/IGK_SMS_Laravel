@extends('layouts.LayoutApp')
@section('title', 'Edit Parent')
@section('content')
<h3 class="mb-3">Edit parent</h3>
<div class="card shadow-sm"><div class="card-body">
    <form method="POST" action="{{ route('admin.guardians.update', $guardian) }}">
        @csrf @method('PUT')
        @include('admin.AdminGuardiansForm')
    </form>
</div></div>
@endsection
