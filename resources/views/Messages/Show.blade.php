@extends('Layouts.LayoutApp')
@section('title', $conversation->subject ?: 'Private conversation')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="h5 mb-1">{{ $conversation->subject ?: 'Private conversation' }}</h3>
        <div class="small text-muted">
            With {{ $conversation->participants->where('id', '!=', auth()->id())->pluck('name')->join(', ') }}
        </div>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="{{ route('messages.index') }}">Back to inbox</a>
</div>
<div class="card shadow-sm mb-3">
    <div class="card-body">
        @foreach ($conversation->messages as $message)
            <article class="border-bottom py-3">
                <div class="d-flex justify-content-between gap-3">
                    <strong>{{ $message->sender->name }}</strong>
                    <time class="small text-muted" datetime="{{ $message->created_at->toIso8601String() }}">
                        {{ $message->created_at->format('d M Y, H:i') }}
                    </time>
                </div>
                <div class="mt-2" style="white-space: pre-wrap">{{ $message->body }}</div>
                @if ($message->sender_id === auth()->id())
                    <div class="small text-muted mt-1">{{ $message->read_at ? 'Read' : 'Delivered' }}</div>
                @endif
            </article>
        @endforeach
    </div>
</div>
<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('messages.replies.store', $conversation) }}">
            @csrf
            <label for="body" class="form-label">Reply</label>
            <textarea id="body" name="body" rows="4" maxlength="10000" required class="form-control">{{ old('body') }}</textarea>
            @error('body')<div class="text-danger small">{{ $message }}</div>@enderror
            <button class="btn btn-primary mt-3">Send reply</button>
        </form>
    </div>
</div>
@endsection
