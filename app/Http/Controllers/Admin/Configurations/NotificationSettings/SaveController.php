<?php

namespace App\Http\Controllers\Admin\Configurations\NotificationSettings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Configurations\NotificationSettings\SaveRequest;
use App\Models\Configurations\UserNotificationSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SaveController extends Controller
{
    public function __invoke(SaveRequest $request)
    {

      Log::info(' NotificationSettings Save START');

      
        // 1️⃣ Incoming payload
        Log::info('Incoming request', $request->all());


        $validated = $request->validated();

        $type = $request->type;
        $incoming = collect($request->recipients);        


        
        Log::info('Notification type', ['type' => $type]);
        Log::info('Incoming recipients count', ['count' => $incoming->count()]);


        /** -----------------------------
         * HRM USERS
         * ----------------------------- */
        $hrmUserIds = $incoming->where('source', 'hrm')->pluck('user_id')->filter()->map(fn($id) => (int) $id)->values();


              Log::info('HRM user IDs', $hrmUserIds->toArray());

        // Upsert HRM users
        foreach ($incoming->where('source', 'hrm') as $row) {

                Log::info('Upserting HRM user', $row);

            UserNotificationSetting::updateOrCreate(
                [
                    'type' => $type,
                    'user_id' => $row['user_id'],
                ],
                [
                    'name' => $row['name'],
                    'phone' => $row['phone'],
                ],
            );
        }

        // Delete removed HRM users
          $deletedHrm =   UserNotificationSetting::where('type', $type)->whereNotNull('user_id')->whereNotIn('user_id', $hrmUserIds)->delete();

                Log::warning('Deleted HRM users count', ['count' => $deletedHrm]);

        /** -----------------------------
         * MANUAL USERS (MULTIPLE)
         * ----------------------------- */

        $manualRows = $incoming->where('source', 'manual');

           Log::info('Manual rows', $manualRows->toArray());

        // IDs coming from frontend (for update/delete)
        $manualIds = $manualRows->pluck('id')->filter()->map(fn($id) => (int) $id)->values();
  Log::info('Manual IDs from frontend', $manualIds->toArray());
        // Save / update manuals
        foreach ($manualRows as $row) {

          Log::info('Upserting MANUAL user', $row);

            UserNotificationSetting::updateOrCreate(
                [
                    'id' => $row['id'] ?? null, // update if exists
                ],
                [
                    'type' => $type,
                    'user_id' => null,
                    'name' => $row['name'],
                    'phone' => $row['phone'],
                ],
            );
        }


        /** -----------------------------
         * RETURN UPDATED VIEW
         * ----------------------------- */
        $rows = UserNotificationSetting::where('type', $type)->orderBy('name')->get()->toArray();
       Log::info('Final DB rows', $rows);

        Log::info(' NotificationSettings Save END');

        $html = view('admin.configurations.partials._notification_view_table', compact('rows'))->render();

        return response()->json([
            'success' => true,
            'message' => 'Notification settings updated',
            'html' => $html,
            'type' => $type,
            'rows' => $rows,

        ]);
    }
}
