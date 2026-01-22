<?php

namespace App\Http\Controllers\Api\Admin\V1\UserNotification;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

// Requests
use App\Http\Requests\Api\Admin\V1\UserNotification\IndexRequest;

// Resources
use App\Http\Resources\Api\Admin\V1\UserNotification\ListResource;

// Model
use App\Models\Iam\Personnel\UserNotification;

class IndexController extends BaseController
{
    /**
     * User Notifications List
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(IndexRequest $request): JsonResponse
    {
        $validatedData = $request->validated();

        //  Always take authenticated user
        $userId = Auth::id();

        //  Default status = unread
        $status = $validatedData['status'] ?? 'unread';

        $notifications = UserNotification::query()
            ->where('user_id', $userId)
            ->where('status', $status)
            ->orderByDesc('id')
            ->get();

        if ($notifications->isEmpty()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => trans('messages.api.admin.v1.user_notifications.no_notifications_found'),
                ],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        return response()->json([
            'success' => true,
            'message' => trans('messages.api.admin.v1.user_notifications.notifications_found'),
            'notifications' => ListResource::collection($notifications),
        ]);
    }
}
