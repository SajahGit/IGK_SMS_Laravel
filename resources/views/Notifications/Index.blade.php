@extends('Layouts.LayoutApp')

@section('title', 'Notifications')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-9">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h4 class="mb-0"><i class="bi bi-bell-fill text-primary me-2"></i>All Notifications</h4>
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-check2-all me-1"></i>Mark all as read
                </button>
            </form>
        </div>

        <div class="card p-3">
            @forelse ($notifications as $notification)
                <a href="{{ route('notifications.show', $notification) }}"
                   class="d-block text-decoration-none p-3 rounded-3 mb-2 border {{ $notification->read_at ? 'opacity-75' : 'bg-primary bg-opacity-10 border-primary-subtle' }}">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                        <strong class="text-body">{{ $notification->data['title'] ?? 'Notification' }}</strong>
                        <div class="d-flex align-items-center gap-2">
                            @if (! $notification->read_at)
                                <span class="badge bg-primary rounded-pill">New</span>
                            @endif
                            <span class="small text-secondary">{{ $notification->created_at?->diffForHumans() }}</span>
                        </div>
                    </div>
                    <div class="small text-secondary">{{ \Illuminate\Support\Str::limit($notification->data['body'] ?? ($notification->data['message'] ?? ''), 140) }}</div>
                </a>
            @empty
                <div class="text-center text-secondary py-5">
                    <i class="bi bi-bell-slash fs-2 d-block mb-2"></i>
                    No notifications yet.
                </div>
            @endforelse

            <div class="mt-3">
                {{ $notifications->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>
@endsection
