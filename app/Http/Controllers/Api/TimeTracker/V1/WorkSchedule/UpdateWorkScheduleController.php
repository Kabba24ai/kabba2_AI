<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\WorkSchedule;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;
use Carbon\Carbon;

use App\Models\Iam\Personnel\WorkSchedule;
use App\Http\Requests\Api\TimeTracker\V1\WorkSchedule\UpdateWorkScheduleRequest;
use App\Services\TimeTrackerAuditService;

class UpdateWorkScheduleController extends BaseController
{
    public function __invoke(UpdateWorkScheduleRequest $request): JsonResponse
    {
        try {

            $data = $request->validated();

            // Normalize time
            $startTime = !empty($data['start_time'])
                ? Carbon::parse($data['start_time'])->format('H:i:s')
                : null;

            $endTime = !empty($data['end_time'])
                ? Carbon::parse($data['end_time'])->format('H:i:s')
                : null;

            // Look up existing record BEFORE updateOrCreate to capture old values
            $existing = WorkSchedule::where('user_id', $data['employee_id'])
                ->where('date', $data['date'])
                ->first();

            $oldValues = $existing ? [
                'start_time'   => $existing->start_time,
                'end_time'     => $existing->end_time,
                'store_id'     => $existing->store_id,
                'is_scheduled' => $existing->is_scheduled,
                'hours'        => $existing->hours,
                'notes'        => $existing->notes,
            ] : null;

            $record = WorkSchedule::updateOrCreate(
                [
                    'user_id' => $data['employee_id'],
                    'date' => $data['date'],
                ],
                [
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'store_id' => $data['store_id'] ?? null,
                    'is_scheduled' => $data['is_scheduled'],
                    'hours' => $data['hours'],
                    'notes' => $data['notes'] ?? null,
                ]
            );

            // Audit: one row per changed field
            $isNew   = $record->wasRecentlyCreated;
            $action  = $isNew ? 'schedule_create' : 'schedule_update';
            $weekday = Carbon::parse($data['date'])->format('l');
            $shift   = ($startTime && $endTime) ? "{$startTime} - {$endTime}" : null;

            $newFields = [
                'start_time'   => $startTime,
                'end_time'     => $endTime,
                'store_id'     => $data['store_id'] ?? null,
                'is_scheduled' => $data['is_scheduled'],
                'hours'        => $data['hours'],
                'notes'        => $data['notes'] ?? null,
            ];

            foreach ($newFields as $field => $newVal) {
                $oldVal  = $oldValues[$field] ?? null;
                $oldNorm = self::normalizeField($field, $oldVal);
                $newNorm = self::normalizeField($field, $newVal);

                if ($oldNorm === $newNorm) {
                    continue;
                }

                TimeTrackerAuditService::log([
                    'store_id'    => $record->store_id,
                    'employee_id' => $data['employee_id'],
                    'entity_type' => 'work_schedule',
                    'entity_id'   => $record->id,
                    'action'      => $action,
                    'field'       => $field,
                    'old_value'   => $isNew ? null : $oldNorm,
                    'new_value'   => $newNorm,
                    'metadata'    => [
                        'weekday'      => $weekday,
                        'shift'        => $shift,
                        'request_type' => 'update',
                    ],
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Schedule updated successfully',
                'data' => $record
            ]);

        } catch (Throwable $e) {

            //  Log error properly
            Log::error('WorkSchedule Update Failed', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update schedule'
            ], 500);
        }
    }

    private static function normalizeField(string $field, mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if ($field === 'is_scheduled') {
            return is_bool($value) ? ($value ? '1' : '0') : (string) $value;
        }
        if ($field === 'hours') {
            return (string) (float) $value;
        }
        return (string) $value;
    }
}
