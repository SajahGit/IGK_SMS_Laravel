{{-- usage: @include('Layouts.LayoutStat', ['icon' => 'people-fill', 'value' => 10, 'label' => 'Students', 'tone' => 'indigo']) --}}
@php
    $isMulti = is_string($value) && str_contains($value, ' / ');
    $currencies = $isMulti ? explode(' / ', $value) : [];
    $badgeToneClass = match($tone ?? 'primary') {
        'info', 'cyan' => 'bg-info-subtle text-info-emphasis border-info-subtle',
        'success', 'green' => 'bg-success-subtle text-success-emphasis border-success-subtle',
        'warning', 'amber' => 'bg-warning-subtle text-warning-emphasis border-warning-subtle',
        'rose', 'danger' => 'bg-danger-subtle text-danger-emphasis border-danger-subtle',
        'violet' => 'bg-purple-subtle text-purple-emphasis border-purple-subtle',
        default => 'bg-primary-subtle text-primary-emphasis border-primary-subtle',
    };
@endphp
<div class="card stat-card shadow-sm border-0">
    <div class="card-body {{ $isMulti ? 'd-flex flex-column justify-content-between p-3' : 'd-flex align-items-center gap-3 p-3' }} h-100">
        @if($isMulti)
            <div>
                <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom" style="border-color: var(--glass-border, rgba(255,255,255,0.1)) !important;">
                    <div class="d-flex align-items-center gap-2">
                        <div class="stat-icon tone-{{ $tone ?? 'indigo' }}" style="width: 34px; height: 34px; font-size: 1rem; border-radius: 9px; box-shadow: none;">
                            <i class="bi bi-{{ $icon ?? 'bar-chart-fill' }}"></i>
                        </div>
                        <div class="stat-label text-uppercase fw-bold m-0" style="font-size: 0.8rem; letter-spacing: 0.5px;">{{ $label }}</div>
                    </div>
                    <span class="badge {{ $badgeToneClass }} border fw-semibold px-2 py-0.5 rounded-pill" style="font-size: 0.68rem;">
                        {{ count($currencies) }} Currencies
                    </span>
                </div>

                <div class="d-flex flex-column gap-1 w-100">
                    @foreach($currencies as $currencyItem)
                        @php
                            $parts = explode(' ', trim($currencyItem), 2);
                            $cCode = $parts[0] ?? '';
                            $cAmount = $parts[1] ?? $currencyItem;
                        @endphp
                        <div class="d-flex justify-content-between align-items-center px-2 py-1 rounded" 
                             style="background: var(--glass-surface-subtle, rgba(255,255,255,0.03)); border: 1px solid var(--glass-border, rgba(255,255,255,0.08));">
                            <span class="badge {{ $badgeToneClass }} border fw-bold text-uppercase" style="font-size: 0.72rem; min-width: 44px; text-align: center;">
                                {{ $cCode }}
                            </span>
                            <span class="font-monospace fw-bold text-end" style="color: var(--text-strong); font-size: 0.88rem;">
                                {{ $cAmount }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="stat-icon tone-{{ $tone ?? 'indigo' }}"><i class="bi bi-{{ $icon ?? 'bar-chart-fill' }}"></i></div>
            <div>
                <div class="stat-value">{{ $value }}</div>
                <div class="stat-label">{{ $label }}</div>
            </div>
        @endif
    </div>
</div>
