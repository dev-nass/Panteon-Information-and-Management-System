<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

trait LogsActivity
{
    protected function logActivity(
        string $action,
        Model $subject,
        string $description,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): ActivityLog {
        $properties = null;
        if ($oldValues || $newValues) {
            $properties = array_filter([
                'old' => $oldValues,
                'new' => $newValues,
            ]);
        }

        return ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => get_class($subject),
            'subject_id' => $subject->id,
            'description' => $description,
            'properties' => $properties,
            'ip_address' => request()->ip(),
        ]);
    }
}
