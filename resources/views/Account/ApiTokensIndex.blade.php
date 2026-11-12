@extends('Layouts.LayoutApp')
@section('title', 'API Tokens')

@section('content')
<div class="mb-4">
    <h3 class="mb-1">API Tokens</h3>
    <p class="text-muted mb-0">Tokens expire after 30 days. Create a separate token for each client and revoke it when no longer needed.</p>
</div>

@if ($plainTextToken)
    <div class="alert alert-warning" role="alert">
        <strong>Copy this token now. It will not be shown again.</strong>
        <code class="d-block text-break mt-2">{{ $plainTextToken }}</code>
    </div>
@endif

<div class="row g-4">
    <div class="col-lg-5">
        <form method="POST" action="{{ route('account.api-tokens.store') }}" class="card shadow-sm border-0">
            @csrf
            <div class="card-header bg-transparent"><strong>Create token</strong></div>
            <div class="card-body">
                <div class="mb-3"><label class="form-label" for="token-name">Token name</label><input id="token-name" name="name" class="form-control" maxlength="80" required></div>
                <fieldset class="mb-3"><legend class="form-label">Permissions</legend>
                    @foreach ($allowedAbilities as $ability)
                        <label class="form-check"><input class="form-check-input" type="checkbox" name="abilities[]" value="{{ $ability }}" @checked($ability === 'profile:read')><span class="form-check-label">{{ $ability }}</span></label>
                    @endforeach
                </fieldset>
                <div class="mb-3"><label class="form-label" for="current-password">Confirm password</label><input id="current-password" type="password" name="current_password" class="form-control" autocomplete="current-password" required></div>
                <button class="btn btn-primary" type="submit"><i class="bi bi-key me-1"></i>Create token</button>
            </div>
        </form>
    </div>
    <div class="col-lg-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-transparent"><strong>Active tokens</strong></div>
            <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Name</th><th>Permissions</th><th>Last used</th><th>Expires</th><th></th></tr></thead><tbody>
                @forelse ($tokens as $token)
                    <tr><td>{{ $token->name }}</td><td>{{ implode(', ', $token->abilities ?? []) }}</td><td>{{ $token->last_used_at?->diffForHumans() ?? 'Never' }}</td><td>{{ $token->expires_at?->toDateString() ?? 'No expiry' }}</td><td class="text-end"><form method="POST" action="{{ route('account.api-tokens.destroy', $token->id) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Revoke</button></form></td></tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No active tokens.</td></tr>
                @endforelse
            </tbody></table></div>
        </div>
    </div>
</div>
@endsection