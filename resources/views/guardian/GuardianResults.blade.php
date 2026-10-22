@extends('layouts.LayoutApp')
@section('title', 'Child Results')
@section('content')
@include('guardian.GuardianChildSwitch', ['routeName' => 'guardian.results'])
@include('layouts.LayoutResultsTable')
@endsection
