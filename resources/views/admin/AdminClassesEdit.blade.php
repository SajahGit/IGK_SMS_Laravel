@extends('layouts.LayoutApp')
@section('title', 'Edit Class')
@section('content')
<h3 class="mb-3">Edit class</h3>
<div class="card shadow-sm"><div class="card-body">
    <form method="POST" action="{{ route('admin.classes.update', $schoolClass) }}">
        @csrf @method('PUT')
        @include('admin.AdminClassesForm')
    </form>
</div></div>
@endsection
