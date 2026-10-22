{{-- usage: @include('layouts.LayoutStat', ['icon' => 'people-fill', 'value' => 10, 'label' => 'Students', 'tone' => 'indigo']) --}}
<div class="card stat-card">
    <div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon tone-{{ $tone ?? 'indigo' }}"><i class="bi bi-{{ $icon ?? 'bar-chart-fill' }}"></i></div>
        <div>
            <div class="stat-value">{{ $value }}</div>
            <div class="stat-label">{{ $label }}</div>
        </div>
    </div>
</div>
