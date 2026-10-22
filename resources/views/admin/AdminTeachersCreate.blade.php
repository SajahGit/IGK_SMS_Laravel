@extends('layouts.LayoutApp')
@section('title', 'Add Teacher')
@section('content')
<h3 class="mb-3">Add teacher</h3>
<div class="card shadow-sm"><div class="card-body">
    <form method="POST" action="{{ route('admin.teachers.store') }}">
        @csrf
        @include('admin.AdminTeachersForm')
    </form>
</div></div>
@endsection
