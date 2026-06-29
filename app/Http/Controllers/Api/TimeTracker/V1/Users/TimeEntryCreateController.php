<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\Users;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Api\TimeTracker\V1\Users\TimeEntryCreateRequest;
use App\Models\Iam\Personnel\TimeEntry;
use App\Models\Iam\Personnel\TimeEntryBreak;
use App\Models\Iam\Personnel\User;
use App\Services\TimeTrackerAuditService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Helpers\TimeTrackerHelper;
use DB;
use Exception;

class TimeEntryCreateController extends BaseController
{
    public function __invoke(TimeEntryCreateRequest $request)
    {
        try {

            Log::info('================ CREATE FLOW START ================');

            DB::beginTransaction();

            $validated = $request->validated();

            $entryId = $validated['entry_id'];
            $type    = $validated['entry_type'];
            $newTime = $validated['new_time'];

            $entry = TimeEntry::findOrFail($entryId);

            $baseDate = $entry->clock_in->toDateString();

            // Resolve store once — reused in both branches
            $storeId = User::where('id', $entry->employee_id)->value('store_id');

            /*
            |------------------------------------------------------------
            | INPUT LOG
            |------------------------------------------------------------
            */
            Log::info('CREATE DEBUG - INPUT', [
                'entry_id' => $entryId,
                'type' => $type,
                'input_time' => $newTime,
                'base_date' => $baseDate,
                'app_timezone' => config('app.timezone'),
                'server_now' => now()->toDateTimeString(),
            ]);

            /*
            |------------------------------------------------------------
            | PARSE TIME
            |------------------------------------------------------------
            */
            $newDateTime = Carbon::parse($baseDate . ' ' . $newTime);

            Log::info('CREATE DEBUG - PARSED', [
                'combined' => $baseDate . ' ' . $newTime,
                'parsed_datetime' => $newDateTime->toDateTimeString(),
                'timezone' => $newDateTime->timezoneName,
            ]);

            /*
            |------------------------------------------------------------
            | ROUND TIME
            |------------------------------------------------------------
            */
            $payIncrement = (int) TimeTrackerHelper::getTimeTrackerSetting('pay_increments', 5);

            $roundedDateTime = TimeTrackerHelper::roundNearest($newDateTime, $payIncrement);

            Log::info('CREATE DEBUG - ROUNDED', [
                'before_round' => $newDateTime->toDateTimeString(),
                'after_round' => $roundedDateTime->toDateTimeString(),
                'pay_increment' => $payIncrement,
            ]);

            /*
            |------------------------------------------------------------
            | CREATE / UPDATE BREAK
            |------------------------------------------------------------
            */

            if (in_array($type, ['lunch_out','unpaid_out'])) {

                Log::info('CREATE DEBUG - ACTION', [
                    'action' => 'CREATE_BREAK_START',
                    'type' => $type,
                ]);

                $break = new TimeEntryBreak();

                $break->time_entry_id = $entryId;
                $break->type = $type === 'lunch_out' ? 'lunch' : 'other';
                $break->start_time = $roundedDateTime;
                $break->created_at = $newDateTime;

                //  disable auto timestamp override
                $break->timestamps = false;

                $break->save();

                Log::info('CREATE DEBUG - DB RESULT', [
                    'break_id' => $break->id,
                    'start_time' => $break->start_time,
                ]);

                TimeTrackerAuditService::log([
                    'store_id'    => $storeId,
                    'employee_id' => $entry->employee_id,
                    'entity_type' => 'time_entry_break',
                    'entity_id'   => $break->id,
                    'action'      => 'create',
                    'field'       => null,
                    'old_value'   => null,
                    'new_value'   => $roundedDateTime->toDateTimeString(),
                    'metadata'    => [
                        'entry_type'     => $type,
                        'created_entity' => 'time_entry_break',
                    ],
                ]);
            }

            elseif (in_array($type, ['lunch_in','unpaid_in'])) {

                Log::info('CREATE DEBUG - ACTION', [
                    'action' => 'UPDATE_BREAK_END',
                    'type' => $type,
                ]);

                $break = TimeEntryBreak::where('time_entry_id', $entryId)
                    ->whereNull('end_time')
                    ->latest('start_time')
                    ->first();

                if (!$break) {
                    throw new Exception('No active break found');
                }

                Log::info('CREATE DEBUG - FOUND BREAK', [
                    'break_id' => $break->id,
                    'existing_start_time' => $break->start_time,
                ]);

                $break->end_time   = $roundedDateTime;
                $break->updated_at = $newDateTime;

                $break->timestamps = false;

                $break->save();

                Log::info('CREATE DEBUG - DB RESULT', [
                    'break_id' => $break->id,
                    'end_time' => $break->end_time,
                ]);

                TimeTrackerAuditService::log([
                    'store_id'    => $storeId,
                    'employee_id' => $entry->employee_id,
                    'entity_type' => 'time_entry_break',
                    'entity_id'   => $break->id,
                    'action'      => 'create',
                    'field'       => null,
                    'old_value'   => null,
                    'new_value'   => $roundedDateTime->toDateTimeString(),
                    'metadata'    => [
                        'entry_type'     => $type,
                        'created_entity' => 'time_entry_break',
                    ],
                ]);
            }

            DB::commit();

            Log::info('================ CREATE FLOW END ================');

            return response()->json([
                'success' => true,
                'message' => 'New time entry created successfully',
            ]);

        } catch (Exception $e) {

            DB::rollBack();

            Log::error('CREATE FAILED', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Create failed',
            ], 500);
        }
    }
}
