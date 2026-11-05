@extends('Layouts.LayoutApp')
@section('title', 'Change Password')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">
        @if (session('warning'))
            <div class="alert alert-warning d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-shield-exclamation fs-5"></i>
                <div class="flex-grow-1">
                    <strong>Action Required:</strong> {{ session('warning') }}
                </div>
            </div>
        @endif

        @include('Layouts.LayoutErrors')

        <div class="card shadow-sm border-0">
            <div class="card-header bg-transparent py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="stat-icon tone-primary mb-0" style="width:40px;height:40px;font-size:1.2rem;">
                        <i class="bi bi-key-fill"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">Change Password</h4>
                        <small class="text-muted">Ensure your account uses a strong, secure password.</small>
                    </div>
                </div>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('password.change.update') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                        <div class="input-icon">
                            <i class="bi bi-lock"></i>
                            <input type="password" id="current_password" name="current_password"
                                   class="form-control @error('current_password') is-invalid @enderror"
                                   placeholder="Enter current password" required autocomplete="current-password">
                            <button type="button" class="pw-toggle" data-target="current_password" aria-label="Toggle password visibility">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        @error('current_password')
                            <div class="text-danger small mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">New Password <span class="text-danger">*</span></label>
                        <div class="input-icon">
                            <i class="bi bi-shield-lock"></i>
                            <input type="password" id="password" name="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   placeholder="Use 10+ characters with uppercase, lowercase, and a number" required minlength="10" autocomplete="new-password">
                            <button type="button" class="pw-toggle" data-target="password" aria-label="Toggle password visibility">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="form-text small text-muted">Use at least 10 characters with uppercase, lowercase, and a number. It must differ from your current password.</div>
                        @error('password')
                            <div class="text-danger small mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                        <div class="input-icon">
                            <i class="bi bi-shield-check"></i>
                            <input type="password" id="password_confirmation" name="password_confirmation"
                                   class="form-control" placeholder="Re-enter new password" required autocomplete="new-password">
                            <button type="button" class="pw-toggle" data-target="password_confirmation" aria-label="Toggle password visibility">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        @if (!auth()->user()->must_change_password)
                            <a href="{{ route(auth()->user()->dashboardRoute()) }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
                            </a>
                        @else
                            <div></div>
                        @endif
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check2-circle me-1"></i> Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.pw-toggle').forEach(function (button) {
            button.addEventListener('click', function () {
                var targetId = this.dataset.target;
                var input = document.getElementById(targetId);
                if (!input) return;
                var isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                this.innerHTML = '<i class="bi bi-eye' + (isPassword ? '-slash' : '') + '"></i>';
            });
        });
    });
</script>
@endsection
