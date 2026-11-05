<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    public static function log(
        string $action,
        Model|string $subject,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        $subjectType = is_string($subject) ? $subject : get_class($subject);
        $subjectId = is_object($subject) && isset($subject->id) ? $subject->id : null;

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
