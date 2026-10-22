@extends('layouts.LayoutApp')
@section('title', 'Edit Subject')
@section('content')
<h3 class="mb-3">Edit subject</h3>
<div class="card shadow-sm"><div class="card-body">
    <form method="POST" action="{{ route('admin.subjects.update', $subject) }}">
        @csrf @method('PUT')
        @include('admin.AdminSubjectsForm')
    </form>
</div></div>
@endsection
