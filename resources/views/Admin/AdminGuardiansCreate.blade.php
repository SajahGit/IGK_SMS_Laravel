@extends('Layouts.LayoutApp')
@section('title', 'Add Guardian')
@section('content')
<h3 class="mb-3">Add guardian</h3>
<div class="card shadow-sm"><div class="card-body">
    <form method="POST" action="{{ route('admin.guardians.store') }}">
        @csrf
        @include('Admin.AdminGuardiansForm')
    </form>
</div></div>
@endsection
