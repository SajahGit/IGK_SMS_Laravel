@extends('Layouts.LayoutApp')
@section('title', 'Child Results')
@section('content')
@include('Guardian.GuardianChildSwitch', ['routeName' => 'guardian.results'])
@include('Layouts.LayoutResultsTable')
@endsection
