<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\Users;

use App\Http\Controllers\Api\BaseController;
use App\Models\Iam\Personnel\TimeEntry;
use App\Models\Iam\Personnel\TimeEntryBreak;
use App\Models\Iam\Personnel\User;
use App\Helpers\TimeTrackerHelper;
use App\Http\Requests\Api\TimeTracker\V1\Users\TimeEntryBulkUpdateRequest;
use App\Services\TimeTrackerAuditService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class TimeEntryBulkUpdateController extends BaseController
{
   public function __invoke(TimeEntryBulkUpdateRequest $request)
    {

        try {

            $updates   = $request->validated()['updates'];
            $bulkCount = count($updates);

            // Lazy store_id cache — one DB read per unique employee across the whole batch
            $storeCache = [];

            DB::beginTransaction();

            foreach ($updates as $index => $item) {

                $entryId = $item['entry_id'];
                $breakId = $item['break_id'] ?? null;
                $type    = $item['entry_type'];
                $newTime = $item['new_time'];

                $entry = TimeEntry::findOrFail($entryId);

                $baseDate = $entry->clock_in->toDateString();

                $newDateTime = Carbon::parse($baseDate . ' ' . $newTime);

                $payIncrement = (int) TimeTrackerHelper::getTimeTrackerSetting('pay_increments', 5);

                $roundedDateTime = TimeTrackerHelper::roundNearest($newDateTime, $payIncrement);

                /*
                |--------------------------------------------------------------------------
                | CLOCK IN
                |--------------------------------------------------------------------------
                */

                if ($type === 'clock_in') {

                    $oldClockIn = $entry->clock_in; // capture BEFORE modification

                    $entry->clock_in = $roundedDateTime;
                    $entry->created_at = $newDateTime;

                    $entry->save();

                    if ($oldClockIn === null || $oldClockIn->timestamp !== $roundedDateTime->timestamp) {
                        TimeTrackerAuditService::log([
                            'store_id'    => $storeCache[$entry->employee_id]
                                ??= User::where('id', $entry->employee_id)->value('store_id'),
                            'employee_id' => $entry->employee_id,
                            'entity_type' => 'time_entry',
                            'entity_id'   => $entry->id,
                            'action'      => 'bulk_update',
                            'field'       => 'clock_in',
                            'old_value'   => $oldClockIn?->toDateTimeString(),
                            'new_value'   => $roundedDateTime->toDateTimeString(),
                            'metadata'    => [
                                'index'      => $index,
                                'entry_type' => $type,
                                'bulk_count' => $bulkCount,
                            ],
                        ]);
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | CLOCK OUT
                |--------------------------------------------------------------------------
                */

                elseif ($type === 'clock_out') {

                    $oldClockOut = $entry->clock_out; // capture BEFORE modification

                    $entry->clock_out = $roundedDateTime;
                    $entry->status = 'completed';
                    $entry->updated_at = $newDateTime;

                    $entry->save();

                    if ($oldClockOut === null || $oldClockOut->timestamp !== $roundedDateTime->timestamp) {
                        TimeTrackerAuditService::log([
                            'store_id'    => $storeCache[$entry->employee_id]
                                ??= User::where('id', $entry->employee_id)->value('store_id'),
                            'employee_id' => $entry->employee_id,
                            'entity_type' => 'time_entry',
                            'entity_id'   => $entry->id,
                            'action'      => 'bulk_update',
                            'field'       => 'clock_out',
                            'old_value'   => $oldClockOut?->toDateTimeString(),
                            'new_value'   => $roundedDateTime->toDateTimeString(),
                            'metadata'    => [
                                'index'      => $index,
                                'entry_type' => $type,
                                'bulk_count' => $bulkCount,
                            ],
                        ]);
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | BREAKS
                |--------------------------------------------------------------------------
                */

                elseif ($breakId) {

                    $break = TimeEntryBreak::findOrFail($breakId);

                    // Capture originals BEFORE either if-block modifies the break
                    $oldStartTime = $break->start_time;
                    $oldEndTime   = $break->end_time;

                    // $lunchMinutes = TimeTrackerHelper::getTimeTrackerSetting(
                    //     'default_lunch_duration_minutes',
                    //     30
                    // );

                    // $roundedBreakEnd = $newDateTime->copy()->addMinutes((int) $lunchMinutes);

                    if (in_array($type, ['lunch_out','unpaid_out'])) {

                        $break->start_time = $roundedDateTime;
                        $break->created_at = $newDateTime;

                    }

                    if (in_array($type, ['lunch_in','unpaid_in'])) {

                        $break->end_time = $roundedDateTime;
                        $break->updated_at = $newDateTime;

                    }

                    $break->save();

                    // Audit AFTER save() so entity_id is stable, still inside transaction
                    if (in_array($type, ['lunch_out', 'unpaid_out'])) {
                        if ($oldStartTime === null || $oldStartTime->timestamp !== $roundedDateTime->timestamp) {
                            TimeTrackerAuditService::log([
                                'store_id'    => $storeCache[$entry->employee_id]
                                    ??= User::where('id', $entry->employee_id)->value('store_id'),
                                'employee_id' => $entry->employee_id,
                                'entity_type' => 'time_entry_break',
                                'entity_id'   => $break->id,
                                'action'      => 'bulk_update',
                                'field'       => 'start_time',
                                'old_value'   => $oldStartTime?->toDateTimeString(),
                                'new_value'   => $roundedDateTime->toDateTimeString(),
                                'metadata'    => [
                                    'index'      => $index,
                                    'entry_type' => $type,
                                    'bulk_count' => $bulkCount,
                                ],
                            ]);
                        }
                    }

                    if (in_array($type, ['lunch_in', 'unpaid_in'])) {
                        if ($oldEndTime === null || $oldEndTime->timestamp !== $roundedDateTime->timestamp) {
                            TimeTrackerAuditService::log([
                                'store_id'    => $storeCache[$entry->employee_id]
                                    ??= User::where('id', $entry->employee_id)->value('store_id'),
                                'employee_id' => $entry->employee_id,
                                'entity_type' => 'time_entry_break',
                                'entity_id'   => $break->id,
                                'action'      => 'bulk_update',
                                'field'       => 'end_time',
                                'old_value'   => $oldEndTime?->toDateTimeString(),
                                'new_value'   => $roundedDateTime->toDateTimeString(),
                                'metadata'    => [
                                    'index'      => $index,
                                    'entry_type' => $type,
                                    'bulk_count' => $bulkCount,
                                ],
                            ]);
                        }
                    }

                    $entry->refresh();
                    $entry->save();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bulk update completed',
                'count' => count($updates)
            ]);

        } catch (Exception $e) {

            DB::rollBack();

            Log::error('Bulk TimeEntry Update Failed', [
                'error'=>$e->getMessage(),
                'trace'=>$e->getTraceAsString()
            ]);

            return response()->json([
                'success'=>false,
                'message'=>'Bulk update failed'
            ],500);
        }
    }
}
