@extends('errors.layout')
@section('code', '403')
@section('title', 'Access denied')
@section('message', $exception->getMessage() ?: 'You are not allowed to open this page.')
