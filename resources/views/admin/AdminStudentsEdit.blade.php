@extends('layouts.LayoutApp')
@section('title', 'Edit Student')
@section('content')
<h3 class="mb-3">Edit student</h3>
<div class="card shadow-sm"><div class="card-body">
    <form method="POST" action="{{ route('admin.students.update', $student) }}">
        @csrf @method('PUT')
        @include('admin.AdminStudentsForm')
    </form>
</div></div>
@endsection
