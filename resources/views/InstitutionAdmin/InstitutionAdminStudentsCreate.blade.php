@extends('Layouts.LayoutApp')
@section('title', 'Add Student')
@section('content')
<h3 class="mb-3">Add student</h3>
<div class="card shadow-sm"><div class="card-body">
    <form method="POST" action="{{ route('admin.students.store') }}" enctype="multipart/form-data">
        @csrf
        @include('InstitutionAdmin.InstitutionAdminStudentsForm')
    </form>
</div></div>
@endsection
