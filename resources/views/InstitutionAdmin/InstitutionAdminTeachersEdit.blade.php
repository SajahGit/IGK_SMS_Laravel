@extends('Layouts.LayoutApp')
@section('title', 'Edit Teacher')
@section('content')
<h3 class="mb-3">Edit teacher</h3>
<div class="card shadow-sm"><div class="card-body">
    <form method="POST" action="{{ route('admin.teachers.update', $teacher) }}">
        @csrf @method('PUT')
        @include('InstitutionAdmin.InstitutionAdminTeachersForm')
    </form>
</div></div>
@endsection
