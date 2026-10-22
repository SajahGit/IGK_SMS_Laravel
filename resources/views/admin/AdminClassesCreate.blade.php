@extends('layouts.LayoutApp')
@section('title', 'Add Class')
@section('content')
<h3 class="mb-3">Add class</h3>
<div class="card shadow-sm"><div class="card-body">
    <form method="POST" action="{{ route('admin.classes.store') }}">
        @csrf
        @include('admin.AdminClassesForm')
    </form>
</div></div>
@endsection
