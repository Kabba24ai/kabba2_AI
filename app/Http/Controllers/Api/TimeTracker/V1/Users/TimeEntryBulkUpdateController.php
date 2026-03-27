<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\Users;

use App\Http\Controllers\Api\BaseController;
use App\Models\Iam\Personnel\TimeEntry;
use App\Models\Iam\Personnel\TimeEntryBreak;
use App\Helpers\TimeTrackerHelper;
use App\Http\Requests\Api\TimeTracker\V1\Users\TimeEntryBulkUpdateRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class TimeEntryBulkUpdateController extends BaseController
{
   public function __invoke(TimeEntryBulkUpdateRequest $request)
    {
   
        try {

        
            $updates = $request->validated()['updates'];

            DB::beginTransaction();

            foreach ($updates as $item) {

                $entryId = $item['entry_id'];
                $breakId = $item['break_id'] ?? null;
                $type    = $item['entry_type'];
                $newTime = $item['new_time'];

                $entry = TimeEntry::findOrFail($entryId);

                $baseDate = $entry->clock_in->toDateString();

                $newDateTime = Carbon::parse($baseDate . ' ' . $newTime);

                $payIncrement = (int) TimeTrackerHelper::getTimeTrackerSetting('pay_increments', 5);

                $roundedDateTime = TimeTrackerHelper::roundDown($newDateTime, $payIncrement);

                /*
                |--------------------------------------------------------------------------
                | CLOCK IN
                |--------------------------------------------------------------------------
                */

                if ($type === 'clock_in') {

                    $entry->clock_in = $roundedDateTime;
                    $entry->created_at = $newDateTime;

                    $entry->save();
                }

                /*
                |--------------------------------------------------------------------------
                | CLOCK OUT
                |--------------------------------------------------------------------------
                */

                elseif ($type === 'clock_out') {

                    $entry->clock_out = $roundedDateTime;
                    $entry->status = 'completed';
                    $entry->updated_at = $newDateTime;

                    $entry->save();
                }

                /*
                |--------------------------------------------------------------------------
                | BREAKS
                |--------------------------------------------------------------------------
                */

                elseif ($breakId) {

                    $break = TimeEntryBreak::findOrFail($breakId);

                    // $lunchMinutes = TimeTrackerHelper::getTimeTrackerSetting(
                    //     'default_lunch_duration_minutes',
                    //     30
                    // );

                    // $roundedBreakEnd = $newDateTime->copy()->addMinutes((int) $lunchMinutes);

                    if (in_array($type, ['lunch_out','unpaid_out'])) {

                        $break->start_time = $newDateTime;
                        $break->created_at = $newDateTime;

                    }

                    if (in_array($type, ['lunch_in','unpaid_in'])) {

                        $break->end_time = $newDateTime;
                        $break->updated_at = $newDateTime;

                    }

                    $break->save();

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