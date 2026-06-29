<?php

namespace App\Services;

use App\Models\Iam\Personnel\TimeTrackerAuditLog;
use Illuminate\Support\Facades\Log;

class TimeTrackerAuditService
{
    /**
     * Write a Time Tracker audit record.
     *
     * Required keys in $data: action, entity_type.
     * All other keys are optional.
     *
     * Returns the created model on success, null on failure.
     * Failures are logged but never rethrown — audit writes must never break payroll mutations.
     */
    public static function log(array $data): ?TimeTrackerAuditLog
    {
        try {
            $oldValue = $data['old_value'] ?? null;
            $newValue = $data['new_value'] ?? null;
            $metadata = $data['metadata'] ?? null;

            return TimeTrackerAuditLog::create([
                'store_id'    => $data['store_id']    ?? null,
                'employee_id' => $data['employee_id'] ?? null,
                'actor_type'  => $data['actor_type']  ?? 'user',
                'actor_id'    => array_key_exists('actor_id', $data)
                    ? $data['actor_id']
                    : auth()->id(),
                'entity_type' => $data['entity_type'],
                'entity_id'   => $data['entity_id']   ?? null,
                'action'      => $data['action'],
                'field'       => $data['field']        ?? null,
                'old_value'   => self::castValue($oldValue),
                'new_value'   => self::castValue($newValue),
                'reason'      => $data['reason']       ?? null,
                'metadata'    => is_array($metadata) ? $metadata : null,
            ]);
        } catch (\Throwable $e) {
            Log::error('[TimeTrackerAuditService] Audit write failed', [
                'action'      => $data['action']      ?? 'unknown',
                'entity_type' => $data['entity_type'] ?? 'unknown',
                'entity_id'   => $data['entity_id']   ?? null,
                'error'       => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Cast any scalar, array, or object value to a storable string.
     * Returns null if the input is null.
     */
    private static function castValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value);
    }
}
