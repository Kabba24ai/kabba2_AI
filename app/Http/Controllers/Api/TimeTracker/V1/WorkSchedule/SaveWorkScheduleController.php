<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\WorkSchedule;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;
use Carbon\Carbon;

use App\Models\Iam\Personnel\WorkSchedule;
use App\Http\Requests\Api\TimeTracker\V1\WorkSchedule\SaveWorkScheduleRequest;
use App\Services\TimeTrackerAuditService;

class SaveWorkScheduleController extends BaseController
{
    public function __invoke(SaveWorkScheduleRequest $request): JsonResponse
    {
        $schedules = $request->validated()['schedules'];

        Log::info('WorkSchedule Save Request', [
            'count' => count($schedules)
        ]);

        DB::beginTransaction();

        try {

            foreach ($schedules as $index => $schedule) {

                try {

                    //  Normalize time format
                    $startTime = !empty($schedule['start_time'])
                        ? Carbon::parse($schedule['start_time'])->format('H:i:s')
                        : null;

                    $endTime = !empty($schedule['end_time'])
                        ? Carbon::parse($schedule['end_time'])->format('H:i:s')
                        : null;

                  

                 
                    $record = WorkSchedule::firstOrNew([
                        'user_id' => $schedule['employee_id'],
                        'date' => $schedule['date'],
                    ]);

                    $isNew = !$record->exists;

                    // Capture old values BEFORE field assignments
                    $oldValues = $isNew ? null : [
                        'start_time'   => $record->start_time,
                        'end_time'     => $record->end_time,
                        'store_id'     => $record->store_id,
                        'is_scheduled' => $record->is_scheduled,
                        'hours'        => $record->hours,
                        'notes'        => $record->notes,
                    ];

                    $record->start_time = $startTime;
                    $record->end_time = $endTime;
                    $record->store_id = $schedule['store_id'] ?? null;
                    $record->is_scheduled = $schedule['is_scheduled'];
                    $record->hours = $schedule['hours'];
                    $record->notes = $schedule['notes'] ?? null;

                    $record->save();

                    // Audit: one row per changed field
                    $action  = $isNew ? 'schedule_create' : 'schedule_update';
                    $weekday = Carbon::parse($schedule['date'])->format('l');
                    $shift   = ($startTime && $endTime) ? "{$startTime} - {$endTime}" : null;

                    $newFields = [
                        'start_time'   => $startTime,
                        'end_time'     => $endTime,
                        'store_id'     => $schedule['store_id'] ?? null,
                        'is_scheduled' => $schedule['is_scheduled'],
                        'hours'        => $schedule['hours'],
                        'notes'        => $schedule['notes'] ?? null,
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
                            'employee_id' => $schedule['employee_id'],
                            'entity_type' => 'work_schedule',
                            'entity_id'   => $record->id,
                            'action'      => $action,
                            'field'       => $field,
                            'old_value'   => $isNew ? null : $oldNorm,
                            'new_value'   => $newNorm,
                            'metadata'    => [
                                'weekday'      => $weekday,
                                'shift'        => $shift,
                                'request_type' => 'save',
                            ],
                        ]);
                    }

                  

                } catch (Throwable $rowError) {

                    Log::error('Failed to save schedule row', [
                        'index' => $index,
                        'data' => $schedule,
                        'error' => $rowError->getMessage(),
                        'line' => $rowError->getLine()
                    ]);
                }
            }

            DB::commit();

            Log::info('WorkSchedule Save Completed Successfully');

            return response()->json([
                'success' => true,
                'message' => 'Schedule saved successfully'
            ]);

        } catch (Throwable $e) {

            DB::rollBack();

            Log::error('WorkSchedule Save Failed (Global)', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save schedule'
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