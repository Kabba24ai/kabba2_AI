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

                 
                    $record->start_time = $startTime;
                    $record->end_time = $endTime;
                    $record->store_id = $schedule['store_id'] ?? null;
                    $record->is_scheduled = $schedule['is_scheduled'];
                    $record->hours = $schedule['hours'];
                    $record->notes = $schedule['notes'] ?? null;

                   
                    $record->save();

                  

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
}