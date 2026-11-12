@extends('Layouts.LayoutApp')
@section('title', 'Messages')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Private messages</h3>
    <a class="btn btn-primary" href="{{ route('messages.create') }}">New message</a>
</div>
<div class="card shadow-sm">
    <div class="list-group list-group-flush">
        @forelse ($conversations as $conversation)
            @php
                $otherParticipant = $conversation->participants->firstWhere('id', '!=', auth()->id());
                $latestMessage = $conversation->messages->first();
            @endphp
            <a class="list-group-item list-group-item-action py-3" href="{{ route('messages.show', $conversation) }}">
                <div class="d-flex justify-content-between gap-3">
                    <strong>{{ $otherParticipant?->name ?? 'Unavailable participant' }}</strong>
                    <span class="small text-muted">{{ $conversation->last_message_at?->diffForHumans() }}</span>
                </div>
                <div class="small text-muted">{{ $conversation->subject ?: 'Private conversation' }}</div>
                @if ($latestMessage)
                    <div class="text-truncate mt-1">{{ $latestMessage->body }}</div>
                @endif
                @if ($conversation->unread_messages_count)
                    <span class="badge bg-primary mt-2">{{ $conversation->unread_messages_count }} unread</span>
                @endif
            </a>
        @empty
            <div class="list-group-item text-muted py-4">No conversations yet.</div>
        @endforelse
    </div>
</div>
<div class="mt-3">{{ $conversations->links() }}</div>
@endsection
