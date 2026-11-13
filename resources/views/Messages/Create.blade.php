@extends('Layouts.LayoutApp')
@section('title', 'New message')
@section('content')
<div class="card shadow-sm">
    <div class="card-body">
        <h3 class="h5 mb-3">New private message</h3>
        <form method="POST" action="{{ route('messages.store') }}">
            @csrf
            <div class="mb-3">
                <label for="recipient_id" class="form-label">Recipient</label>
                <select id="recipient_id" name="recipient_id" class="form-select recipient-select" size="8" required>
                    <option value="">Select a recipient</option>
                    @foreach ($recipients as $recipient)
                        <option value="{{ $recipient->id }}" @selected(old('recipient_id') == $recipient->id)>
                            {{ $recipient->name }} ({{ $recipient->role }})
                        </option>
                    @endforeach
                </select>
                @error('recipient_id')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="subject" class="form-label">Subject</label>
                <input id="subject" name="subject" value="{{ old('subject') }}" maxlength="150" class="form-control">
                @error('subject')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="body" class="form-label">Message</label>
                <textarea id="body" name="body" rows="6" maxlength="10000" required class="form-control">{{ old('body') }}</textarea>
                @error('body')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <button class="btn btn-primary">Send message</button>
            <a class="btn btn-outline-secondary" href="{{ route('messages.index') }}">Cancel</a>
        </form>
    </div>
</div>
@endsection
