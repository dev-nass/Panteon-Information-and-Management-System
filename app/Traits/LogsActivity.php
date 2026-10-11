<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

trait LogsActivity
{
    protected function logActivity(
        string $action,
        ?Model $subject,
        string $description,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $extraProperties = null,
    ): ActivityLog {
        $properties = [];

        if ($oldValues !== null) {
            $properties['old'] = $oldValues;
        }

        if ($newValues !== null) {
            $properties['new'] = $newValues;
        }

        if ($extraProperties) {
            $properties = array_merge($properties, $extraProperties);
        }

        return ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->id,
            'description' => $description,
            'properties' => ! empty($properties) ? $properties : null,
            'ip_address' => request()->ip(),
        ]);
    }
}
