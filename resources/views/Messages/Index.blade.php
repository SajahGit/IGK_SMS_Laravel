@extends('Layouts.LayoutApp')
@section('title', 'Private Messages')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h3 class="mb-1 text-body fw-bold"><i class="bi bi-shield-lock-fill text-primary me-2"></i>Private Messages</h3>
        <p class="text-muted small mb-0">End-to-end confidential 1-on-1 conversations. Only you and the respective recipient/sender have access to these messages.</p>
    </div>
    <a class="btn btn-primary px-3 py-2 shadow-sm" href="{{ route('messages.create') }}">
        <i class="bi bi-pencil-square me-2"></i>Compose Message
    </a>
</div>

<!-- Filter Tabs -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2 px-3">
        <ul class="nav nav-pills w-100 gap-2 mb-0">
            <li class="nav-item">
                <a class="nav-link py-1 px-3 {{ ($tab ?? 'all') === 'all' ? 'active' : '' }}" href="{{ route('messages.index') }}">
                    <i class="bi bi-chat-dots me-1"></i>All Conversations
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link py-1 px-3 {{ ($tab ?? '') === 'unread' ? 'active' : '' }}" href="{{ route('messages.index', ['tab' => 'unread']) }}">
                    <i class="bi bi-envelope me-1"></i>Unread Only
                </a>
            </li>
        </ul>
    </div>
</div>

<!-- Conversations List -->
<div class="card shadow-sm overflow-hidden">
    <div class="list-group list-group-flush">
        @forelse ($conversations as $conversation)
            @php
                $currentUserId = auth()->id();
                $otherParticipant = $conversation->participants->firstWhere('id', '!=', $currentUserId);
                $latestMessage = $conversation->messages->first();
                $isSentByMe = $latestMessage && $latestMessage->sender_id === $currentUserId;
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
            <a class="list-group-item list-group-item-action py-3 px-4 text-decoration-none transition-all" href="{{ route('messages.show', $conversation) }}">
                <div class="d-flex align-items-start gap-3">
                    <!-- Participant Avatar -->
                    <div class="avatar rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm flex-shrink-0"
                         style="width: 46px; height: 46px; background: linear-gradient(135deg, #0ea5e9, #6366f1); font-size: 1.1rem;">
                        {{ strtoupper(mb_substr($otherParticipant?->name ?? '?', 0, 1)) }}
                    </div>

                    <!-- Conversation Details -->
                    <div class="flex-grow-1 min-w-0">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <span class="fw-bold fs-6 text-body">{{ $otherParticipant?->name ?? 'Unavailable participant' }}</span>
                                <span class="badge {{ $roleBadgeClass }} text-uppercase" style="font-size: 0.72rem;">
                                    {{ str_replace('-', ' ', $otherParticipant?->role ?? 'User') }}
                                </span>
                                @if ($otherParticipant?->branch)
                                    <span class="badge bg-body-secondary text-body border border-secondary-subtle" style="font-size: 0.72rem;">
                                        <i class="bi bi-buildings me-1"></i>{{ $otherParticipant->branch->name }}
                                    </span>
                                @endif
                            </div>
                            <span class="small text-muted">
                                <i class="bi bi-clock me-1"></i>{{ $conversation->last_message_at?->diffForHumans() ?? 'Recently' }}
                            </span>
                        </div>

                        <!-- Direction Badge & Subject -->
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            @if ($isSentByMe)
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.72rem;">
                                    <i class="bi bi-arrow-up-right me-1"></i>You sent to {{ $otherParticipant?->name }}
                                </span>
                            @else
                                <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.72rem;">
                                    <i class="bi bi-arrow-down-left me-1"></i>Received from {{ $latestMessage?->sender?->name ?? $otherParticipant?->name }}
                                </span>
                            @endif

                            <span class="text-body fw-semibold small">
                                {{ $conversation->subject ?: 'Confidential conversation' }}
                            </span>
                        </div>

                        <!-- Last Message Body Snippet -->
                        @if ($latestMessage)
                            <div class="text-truncate text-secondary small mt-1" style="max-width: 90%;">
                                <strong class="text-body">{{ $isSentByMe ? 'You: ' : (($latestMessage->sender?->name ?? 'Sender') . ': ') }}</strong>
                                <span>{{ $latestMessage->body }}</span>
                            </div>
                        @endif

                        <!-- Read / Unread Status -->
                        <div class="d-flex align-items-center gap-2 mt-2">
                            @if ($conversation->unread_messages_count)
                                <span class="badge bg-primary text-white rounded-pill px-2 py-1 shadow-sm">
                                    <i class="bi bi-envelope-fill me-1"></i>{{ $conversation->unread_messages_count }} unread
                                </span>
                            @endif

                            @if ($isSentByMe && $latestMessage)
                                <span class="small text-muted">
                                    <i class="bi {{ $latestMessage->read_at ? 'bi-check2-all text-info' : 'bi-check2' }} me-1"></i>
                                    {{ $latestMessage->read_at ? 'Read by recipient' : 'Delivered' }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Chevron indicator -->
                    <div class="align-self-center text-muted ps-2">
                        <i class="bi bi-chevron-right fs-5"></i>
                    </div>
                </div>
            </a>
        @empty
            <div class="text-center py-5">
                <div class="mb-3 text-muted">
                    <i class="bi bi-chat-square-dots" style="font-size: 3rem;"></i>
                </div>
                <h5 class="text-body fw-semibold">No private conversations found</h5>
                <p class="text-muted small mb-3">You don't have any private messages in this folder yet.</p>
                <a class="btn btn-primary btn-sm px-3 shadow-sm" href="{{ route('messages.create') }}">
                    <i class="bi bi-pencil-square me-1"></i>Start New Conversation
                </a>
            </div>
        @endforelse
    </div>
</div>

@if ($conversations->hasPages())
    <div class="mt-4 d-flex justify-content-center">
        {{ $conversations->links() }}
    </div>
@endif
@endsection
