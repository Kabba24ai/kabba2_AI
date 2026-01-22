<?php

namespace App\Http\Controllers\Api\Admin\V1\UserNotification;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

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

        // Authenticated user only
        $userId = Auth::id();

        $notifications = UserNotification::query()
            ->where('user_id', $userId)
            ->when(isset($validated['order_id']), function ($query) use ($validated) {
                $query->where('order_id', $validated['order_id']);
            })
            ->update(['status' => 'read']);


        return response()->json([
            'success' => true,
            'message' => trans('messages.api.admin.v1.user_notifications.notification_status_updated'),
        ]);
    }
}
