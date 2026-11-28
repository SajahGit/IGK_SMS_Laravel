@extends('Layouts.LayoutApp')

@section('title', $notification->data['title'] ?? 'Notification Details')

@section('content')
@php
    $data = $notification->data ?? [];
    $title = $data['title'] ?? 'Notification';
    $body = $data['body'] ?? ($data['message'] ?? 'No additional message content.');
    $actionUrl = $actionUrl ?? ($data['url'] ?? null);
    $extraMeta = collect($data)->except(['title', 'body', 'message', 'url'])->filter(fn ($v) => is_scalar($v) && (string) $v !== '');
@endphp

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h4 class="mb-0"><i class="bi bi-bell-fill text-primary me-2"></i>Notification Details</h4>
            <div class="d-flex gap-2">
                <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-list-ul me-1"></i>All Notifications
                </a>
                <a href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
            </div>
        </div>

        <div class="card p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 pb-3 mb-3 border-bottom border-secondary border-opacity-25">
                <div>
                    <h5 class="mb-1 fw-bold">{{ $title }}</h5>
                    <div class="small text-secondary">
                        <i class="bi bi-clock me-1"></i>{{ $notification->created_at?->format('d M Y, h:i A') }}
                        @if ($notification->created_at)
                            ({{ $notification->created_at->diffForHumans() }})
                        @endif
                    </div>
                </div>
                <span class="badge bg-success-subtle text-success border border-success-subtle">Read</span>
            </div>

            <div class="mb-4" style="white-space: pre-line; line-height: 1.65;">{{ $body }}</div>

            @if ($extraMeta->isNotEmpty())
                <div class="p-3 rounded-3 bg-body-tertiary border mb-4">
                    <div class="small fw-semibold text-secondary text-uppercase mb-2">Details</div>
                    <div class="row g-2">
                        @foreach ($extraMeta as $key => $value)
                            <div class="col-sm-6">
                                <span class="small text-secondary">{{ \Illuminate\Support\Str::headline((string) $key) }}:</span>
                                <span class="small fw-semibold ms-1">{{ $value }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($actionUrl)
                <div>
                    <a href="{{ $actionUrl }}" class="btn btn-primary">
                        <i class="bi bi-box-arrow-up-right me-1"></i>Open Related Page
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
