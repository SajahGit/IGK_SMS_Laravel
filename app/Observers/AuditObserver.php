<?php

namespace App\Observers;

use App\Support\AuditLogger;
use Illuminate\Database\Eloquent\Model;

class AuditObserver
{
    public function created(Model $model): void
    {
        if (! $this->hasActor()) {
            return;
        }

        $newValues = $this->safeValues($model->getAttributes());
        if ($newValues !== []) {
            AuditLogger::log('created', $model, null, $newValues);
        }
    }

    public function updated(Model $model): void
    {
        if (! $this->hasActor()) {
            return;
        }

        $changes = $this->safeValues($model->getChanges());
        if ($changes === []) {
            return;
        }

        $oldValues = [];
        foreach ($changes as $attribute => $value) {
            $oldValues[$attribute] = $model->getRawOriginal($attribute);
        }

        AuditLogger::log('updated', $model, $oldValues, $changes);
    }

    public function deleted(Model $model): void
    {
        if (! $this->hasActor()) {
            return;
        }

        $oldValues = $this->safeValues($model->getAttributes());
        if ($oldValues !== []) {
            AuditLogger::log('deleted', $model, $oldValues, null);
        }
    }

    private function hasActor(): bool
    {
        return auth()->id() !== null;
    }

    private function safeValues(array $attributes): array
    {
        return collect($attributes)
            ->reject(function ($value, $key) {
                $key = strtolower((string) $key);

                return in_array($key, ['created_at', 'updated_at', 'last_login_at'], true)
                    || str_contains($key, 'password')
                    || str_contains($key, 'token')
                    || str_contains($key, 'secret');
            })
            ->all();
    }
}
