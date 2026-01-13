<?php

namespace App\Http\Controllers\Api\Admin\V1\UserNotification;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

// Request
use App\Http\Requests\Api\Admin\V1\UserNotification\UpdateStatusRequest;
// Resource
use App\Http\Resources\Api\Admin\V1\UserNotification\ListResource;
// Model
use App\Models\Iam\Personnel\UserNotification;

class UpdateStatusController extends BaseController
{
    /**
     * Update notification status (read/unread)
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(UpdateStatusRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $notification = UserNotification::where('user_id', $validated['user_id'])
            ->where('order_id', $validated['order_id'])
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => trans('messages.api.admin.v1.user_notifications.no_notifications_found'),
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        $notification->update([
            'status' => 'read',
        ]);

        return response()->json([
            'success' => true,
            'message' => trans('messages.api.admin.v1.user_notifications.notification_status_updated'),
              'notification' => new ListResource($notification),
        ]);
    }
}
