@extends('layouts.LayoutApp')
@section('title', 'Add Student')
@section('content')
<h3 class="mb-3">Add student</h3>
<div class="card shadow-sm"><div class="card-body">
    <form method="POST" action="{{ route('admin.students.store') }}">
        @csrf
        @include('admin.AdminStudentsForm')
    </form>
</div></div>
@endsection
