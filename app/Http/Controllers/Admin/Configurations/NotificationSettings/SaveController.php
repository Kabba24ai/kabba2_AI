<?php

namespace App\Http\Controllers\Admin\Configurations\NotificationSettings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Configurations\NotificationSettings\SaveRequest;
use App\Models\Configurations\UserNotificationSetting;
use Illuminate\Support\Collection;

class SaveController extends Controller
{
    public function __invoke(SaveRequest $request)
    {
        $validated = $request->validated();

        $type = $request->type;
        $incoming = collect($request->recipients);

        /** -----------------------------
         * HRM USERS
         * ----------------------------- */
        $hrmUserIds = $incoming->where('source', 'hrm')->pluck('user_id')->filter()->map(fn($id) => (int) $id)->values();

        // Upsert HRM users
        foreach ($incoming->where('source', 'hrm') as $row) {
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
        UserNotificationSetting::where('type', $type)->where('user_id', '!=', 0)->whereNotIn('user_id', $hrmUserIds)->delete();

        /** -----------------------------
         * MANUAL USERS (MULTIPLE)
         * ----------------------------- */

        $manualRows = $incoming->where('source', 'manual');

        // IDs coming from frontend (for update/delete)
        $manualIds = $manualRows->pluck('id')->filter()->map(fn($id) => (int) $id)->values();

        // Save / update manuals
        foreach ($manualRows as $row) {
            UserNotificationSetting::updateOrCreate(
                [
                    'id' => $row['id'] ?? null, // update if exists
                ],
                [
                    'type' => $type,
                    'user_id' => 0,
                    'name' => $row['name'],
                    'phone' => $row['phone'],
                ],
            );
        }

        // Delete removed manuals
        UserNotificationSetting::where('type', $type)->whereNull('user_id')->whereNotIn('id', $manualIds)->delete();

        /** -----------------------------
         * RETURN UPDATED VIEW
         * ----------------------------- */
        $rows = UserNotificationSetting::where('type', $type)->get()->toArray();

        $html = view('admin.configurations.partials._notification_view_table', compact('rows'))->render();

        return response()->json([
            'success' => true,
            'message' => 'Notification settings updated',
            'html' => $html,
            'type' => $type,
        ]);
    }
}
