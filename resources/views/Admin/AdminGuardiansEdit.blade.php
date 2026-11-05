@extends('Layouts.LayoutApp')
@section('title', 'Edit Guardian')
@section('content')
<h3 class="mb-3">Edit guardian</h3>
<div class="card shadow-sm"><div class="card-body">
    <form method="POST" action="{{ route('admin.guardians.update', $guardian) }}">
        @csrf @method('PUT')
        @include('Admin.AdminGuardiansForm')
    </form>
</div></div>
@endsection
