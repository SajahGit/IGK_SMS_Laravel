@extends('Layouts.LayoutApp')
@section('title', 'Add Subject')
@section('content')
<h3 class="mb-3">Add subject</h3>
<div class="card shadow-sm"><div class="card-body">
    <form method="POST" action="{{ route('admin.subjects.store') }}">
        @csrf
        @include('Admin.AdminSubjectsForm')
    </form>
</div></div>
@endsection
