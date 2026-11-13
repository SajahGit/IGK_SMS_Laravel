@extends('Layouts.LayoutApp')
@section('title', 'Teachers')
@section('content')
<div class="page-head">
    <h3 class="mb-0">Teachers</h3>
    <a href="{{ route('admin.teachers.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add teacher</a>
</div>
<form method="GET" class="row g-2 mb-3">
    <div class="col-md-4"><input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search by name"></div>
    <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Search</button></div>
</form>
@include('components.teacher-table', ['teachers' => $teachers])
@endsection
