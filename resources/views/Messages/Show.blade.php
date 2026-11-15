@extends('Layouts.LayoutApp')
@section('title', $conversation->subject ?: 'Private Conversation')
@section('content')
@php
    $currentUserId = auth()->id();
    $otherParticipant = $conversation->participants->firstWhere('id', '!=', $currentUserId);
    $role = strtolower($otherParticipant?->role ?? 'user');
    $roleBadgeClass = match($role) {
        'teacher' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
        'student' => 'bg-success-subtle text-success-emphasis border border-success-subtle',
        'super-admin', 'super admin', 'admin', 'institution admin' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
        'registrar' => 'bg-primary-subtle text-primary-emphasis border border-primary-subtle',
        'accountant' => 'bg-danger-subtle text-danger-emphasis border border-danger-subtle',
        default => 'bg-secondary-subtle text-body border border-secondary-subtle',
    };
@endphp

<div class="row justify-content-center">
    <div class="col-12 col-xl-10">
        <!-- Top Navigation & Conversation Context -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div class="d-flex align-items-center gap-3">
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('messages.index') }}">
                    <i class="bi bi-arrow-left me-1"></i>Back to Inbox
                </a>
                <div>
                    <h4 class="mb-0 text-body fw-bold">{{ $conversation->subject ?: 'Confidential Conversation' }}</h4>
                    <span class="small text-muted">1-on-1 private messaging channel</span>
                </div>
            </div>

            <!-- Confidentiality Security Badge -->
            <div>
                <span class="badge bg-success-subtle text-success border border-success-subtle py-2 px-3">
                    <i class="bi bi-shield-lock-fill me-1"></i>Private & Confidential
                </span>
            </div>
        </div>

        <!-- Participant Information Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-body py-3 px-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm"
                             style="width: 48px; height: 48px; background: linear-gradient(135deg, #0ea5e9, #6366f1); font-size: 1.15rem;">
                            {{ strtoupper(mb_substr($otherParticipant?->name ?? '?', 0, 1)) }}
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="fs-6 fw-bold text-body">{{ $otherParticipant?->name ?? 'Unavailable participant' }}</span>
                                <span class="badge {{ $roleBadgeClass }} text-uppercase" style="font-size: 0.72rem;">
                                    {{ str_replace('-', ' ', $otherParticipant?->role ?? 'User') }}
                                </span>
                            </div>
                            <div class="small text-muted mt-1">
                                <span class="me-3"><i class="bi bi-envelope me-1"></i>{{ $otherParticipant?->email }}</span>
                                @if ($otherParticipant?->branch)
                                    <span><i class="bi bi-buildings me-1"></i>{{ $otherParticipant->branch->name }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="text-end text-muted small">
                        <div>Participants: <strong class="text-body">You</strong> &amp; <strong class="text-body">{{ $otherParticipant?->name }}</strong></div>
                        <div class="text-success small mt-1"><i class="bi bi-check-circle-fill me-1"></i>Only participants can view</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Messages Chat Stream -->
        <div class="card shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-column gap-4">
                    @forelse ($conversation->messages as $message)
                        @php
                            $isSentByMe = $message->sender_id === $currentUserId;
                        @endphp

                        @if ($isSentByMe)
                            <!-- Outgoing Message (Sent by Current User) -->
                            <div class="d-flex flex-column align-items-end">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="badge bg-primary text-white" style="font-size: 0.72rem;">
                                        <i class="bi bi-person-fill me-1"></i>You ({{ ucfirst(str_replace('-', ' ', auth()->user()->role)) }})
                                    </span>
                                    <span class="small text-muted">
                                        Sent to <strong class="text-body">{{ $otherParticipant?->name }}</strong> &bull;
                                        <time datetime="{{ $message->created_at->toIso8601String() }}">
                                            {{ $message->created_at->format('d M Y, h:i A') }}
                                        </time>
                                    </span>
                                </div>
                                <div class="p-3 rounded-3 text-white shadow-sm"
                                     style="max-width: 80%; background: linear-gradient(135deg, #0284c7, #4f46e5); white-space: pre-wrap;">
                                    {{ $message->body }}
                                </div>
                                <div class="small mt-1 text-muted">
                                    <i class="bi {{ $message->read_at ? 'bi-check2-all text-info' : 'bi-check2' }} me-1"></i>
                                    {{ $message->read_at ? ('Read ' . $message->read_at->diffForHumans()) : 'Delivered' }}
                                </div>
                            </div>
                        @else
                            <!-- Incoming Message (Sent by Other Participant) -->
                            <div class="d-flex flex-column align-items-start">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="badge {{ $roleBadgeClass }}" style="font-size: 0.72rem;">
                                        <i class="bi bi-person me-1"></i>{{ $message->sender?->name ?? 'Sender' }} ({{ ucfirst(str_replace('-', ' ', $message->sender?->role ?? 'User')) }})
                                    </span>
                                    <span class="small text-muted">
                                        Sent to <strong class="text-body">You</strong> &bull;
                                        <time datetime="{{ $message->created_at->toIso8601String() }}">
                                            {{ $message->created_at->format('d M Y, h:i A') }}
                                        </time>
                                    </span>
                                </div>
                                <div class="p-3 rounded-3 text-body bg-body-secondary border border-secondary-subtle shadow-sm"
                                     style="max-width: 80%; white-space: pre-wrap;">
                                    {{ $message->body }}
                                </div>
                            </div>
                        @endif
                    @empty
                        <div class="text-center py-4 text-muted">
                            No messages in this conversation yet.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Reply Form -->
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h5 class="card-title text-body fw-bold mb-3">
                    <i class="bi bi-reply-fill text-primary me-2"></i>Send Reply to {{ $otherParticipant?->name }}
                </h5>
                <form method="POST" action="{{ route('messages.replies.store', $conversation) }}">
                    @csrf
                    <div class="mb-3">
                        <textarea id="body" name="body" rows="4" maxlength="10000" required class="form-control" placeholder="Type your reply here...">{{ old('body') }}</textarea>
                        <div class="d-flex justify-content-between mt-1">
                            <span class="form-text text-muted small">Your reply will only be visible to you and {{ $otherParticipant?->name }}.</span>
                            <span class="small text-muted" id="replyCharCount">0 / 10,000</span>
                        </div>
                        @error('body')<div class="text-danger small mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>@enderror
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4 shadow-sm">
                            <i class="bi bi-send-fill me-2"></i>Send Reply
                        </button>
                        <a class="btn btn-outline-secondary px-3" href="{{ route('messages.index') }}">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const body = document.getElementById('body');
    const counter = document.getElementById('replyCharCount');
    if (body && counter) {
        body.addEventListener('input', () => {
            counter.textContent = body.value.length + ' / 10,000';
        });
        counter.textContent = body.value.length + ' / 10,000';
    }
});
</script>
@endsection
