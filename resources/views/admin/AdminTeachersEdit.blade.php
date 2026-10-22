@extends('layouts.LayoutApp')
@section('title', 'Edit Teacher')
@section('content')
<h3 class="mb-3">Edit teacher</h3>
<div class="card shadow-sm"><div class="card-body">
    <form method="POST" action="{{ route('admin.teachers.update', $teacher) }}">
        @csrf @method('PUT')
        @include('admin.AdminTeachersForm')
    </form>
</div></div>
@endsection
