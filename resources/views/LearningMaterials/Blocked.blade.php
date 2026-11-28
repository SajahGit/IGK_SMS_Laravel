@extends('Layouts.LayoutApp')

@section('title', 'Access Unavailable')

@section('content')
@php
    $messages = [
        'invalid_token' => 'This secure material link is invalid or no longer exists.',
        'revoked_link' => 'This secure sharing link has been revoked by an administrator or uploader.',
        'expired_link' => 'This secure sharing link has expired.',
        'expired_material' => 'This learning material has expired and is no longer available.',
        'inactive_material' => 'This learning material is currently inactive.',
        'inactive_user' => 'Your account is inactive. Please contact the school administrator.',
        'no_permission' => 'Your account does not currently have access to the Learning Materials Library.',
        'library_disabled' => 'The Learning Materials Library is currently disabled.',
    ];
    $message = $messages[$reason ?? 'no_permission'] ?? 'You do not have permission to access this learning material.';
@endphp

<div class="row justify-content-center py-5">
    <div class="col-md-7 col-lg-5">
        <div class="glass-card p-4 text-center">
            <div class="mb-3">
                <i class="bi bi-shield-exclamation text-warning" style="font-size: 3rem;"></i>
            </div>
            <h4 class="mb-2">Access Restricted</h4>
            @if (! empty($material))
                <div class="text-secondary small mb-2">{{ $material->title }}</div>
            @endif
            <p class="text-secondary mb-4">{{ $message }}</p>
            <a href="{{ route('learning-materials.library') }}" class="btn btn-primary">
                <i class="bi bi-arrow-left me-1"></i>Back to Learning Materials
            </a>
        </div>
    </div>
</div>
@endsection
