<?php

namespace App\Http\Controllers\Admin\Configurations\NotificationSettings;

use App\Http\Controllers\Controller;
use App\Models\Configurations\UserNotification;

class DeleteController extends Controller
{
    public function __invoke(UserNotification $notification)
    {
        $type = $notification->type;
        $notification->delete();

        $rows = UserNotification::where('type', $type)->get()->toArray();

        $html = view('admin.configurations.partials._notification_view_table', [
            'rows' => $rows,
            'type' => $type,
        ])->render();

        return response()->json([
            'success' => true,
            'message' => 'Recipient deleted',
            'type'    => $type,
            'html'    => $html,
        ]);
    }
}
