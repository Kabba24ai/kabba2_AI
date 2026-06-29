<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\Users;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Api\TimeTracker\V1\Users\TimeEntryDeleteRequest;
use App\Models\Iam\Personnel\TimeEntry;
use App\Models\Iam\Personnel\TimeEntryBreak;
use App\Models\Iam\Personnel\User;
use App\Services\TimeTrackerAuditService;
use Illuminate\Support\Facades\Log;
use Exception;

class TimeEntryDeleteController extends BaseController
{
    public function __invoke(TimeEntryDeleteRequest $request)
    {
        try {

            $validated = $request->validated();

            $entryId = $validated['entry_id'];
            $breakId = $validated['break_id'] ?? null;
            $type    = $validated['entry_type'];

            $entry = TimeEntry::findOrFail($entryId);

            $storeId = User::where('id', $entry->employee_id)->value('store_id');

            /*
            |--------------------------------------------------------------------------
            | CLOCK IN DELETE
            |--------------------------------------------------------------------------
            |
            | Delete FULL entry + ALL breaks
            |
            */

            if ($type === 'clock_in') {

                // Capture everything BEFORE deletion — rows will be gone after delete()
                $oldClockIn  = $entry->clock_in;
                $breakIds    = $entry->breaks()->pluck('id')->toArray();
                $breakCount  = count($breakIds);

                // delete all breaks
                $entry->breaks()->delete();

                // delete entry
                $entry->delete();

                // Audit after successful deletion — entity_id / employee_id still in model memory
                TimeTrackerAuditService::log([
                    'store_id'    => $storeId,
                    'employee_id' => $entry->employee_id,
                    'entity_type' => 'time_entry',
                    'entity_id'   => $entry->id,
                    'action'      => 'delete',
                    'field'       => null,
                    'old_value'   => $oldClockIn?->toDateTimeString(),
                    'new_value'   => null,
                    'metadata'    => [
                        'entry_type'     => 'clock_in',
                        'deleted_entity' => 'time_entry',
                        'breaks_deleted' => $breakCount,
                        'break_ids'      => $breakIds,
                    ],
                ]);

            }

            /*
            |--------------------------------------------------------------------------
            | CLOCK OUT DELETE
            |--------------------------------------------------------------------------
            |
            | Re-open active entry
            |
            */

            elseif ($type === 'clock_out') {

                $oldClockOut = $entry->clock_out; // capture BEFORE nulling

                $entry->clock_out = null;

                $entry->status = 'active';

                $entry->save();

                // Only log when there was an actual clock_out value to remove
                if ($oldClockOut !== null) {
                    TimeTrackerAuditService::log([
                        'store_id'    => $storeId,
                        'employee_id' => $entry->employee_id,
                        'entity_type' => 'time_entry',
                        'entity_id'   => $entry->id,
                        'action'      => 'delete',
                        'field'       => 'clock_out',
                        'old_value'   => $oldClockOut->toDateTimeString(),
                        'new_value'   => null,
                        'metadata'    => [
                            'entry_type'     => 'clock_out',
                            'deleted_entity' => 'clock_out_value',
                        ],
                    ]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | BREAK DELETE
            |--------------------------------------------------------------------------
            */

            elseif ($breakId) {

                $break = TimeEntryBreak::findOrFail($breakId);

                /*
                |--------------------------------------------------------------------------
                | BREAK START DELETE
                |--------------------------------------------------------------------------
                |
                | Delete FULL break
                |
                */

                if (in_array($type, ['lunch_out', 'unpaid_out'])) {

                    $oldStartTime = $break->start_time; // capture BEFORE row is deleted

                    $break->delete();

                    // entity_id still accessible from model memory after delete()
                    TimeTrackerAuditService::log([
                        'store_id'    => $storeId,
                        'employee_id' => $entry->employee_id,
                        'entity_type' => 'time_entry_break',
                        'entity_id'   => $break->id,
                        'action'      => 'delete',
                        'field'       => null,
                        'old_value'   => $oldStartTime?->toDateTimeString(),
                        'new_value'   => null,
                        'metadata'    => [
                            'entry_type'     => $type,
                            'deleted_entity' => 'time_entry_break',
                        ],
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | BREAK END DELETE
                |--------------------------------------------------------------------------
                |
                | Re-open break
                |
                */

                elseif (in_array($type, ['lunch_in', 'unpaid_in'])) {

                    $oldEndTime = $break->end_time; // capture BEFORE nulling

                    $break->end_time = null;

                    $break->save();

                    // Only log when there was an actual end_time to remove
                    if ($oldEndTime !== null) {
                        TimeTrackerAuditService::log([
                            'store_id'    => $storeId,
                            'employee_id' => $entry->employee_id,
                            'entity_type' => 'time_entry_break',
                            'entity_id'   => $break->id,
                            'action'      => 'delete',
                            'field'       => 'end_time',
                            'old_value'   => $oldEndTime->toDateTimeString(),
                            'new_value'   => null,
                            'metadata'    => [
                                'entry_type'     => $type,
                                'deleted_entity' => 'end_time_value',
                            ],
                        ]);
                    }
                }

                // refresh totals
                $entry->refresh();
                $entry->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Time entry deleted successfully',
            ]);

        } catch (Exception $e) {

            Log::error('TimeEntry Delete Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Delete failed',
            ], 500);
        }
    }
}
