<?php

namespace App\Http\Controllers\Api\Admin\V1\UserNotification;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

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

        $perPage = $validatedData['per_page'] ?? 10;
        $userId = $validatedData['user_id'] ?? null;
        $orderId = $validatedData['order_id'] ?? null;
        $status = $validatedData['status'] ?? null;

        $notifications = UserNotification::query()
            ->when($userId, fn($query) => $query->where('user_id', $userId))
            ->when($orderId, fn($query) => $query->where('order_id', $orderId))
            ->when($status, fn($query) => $query->where('status', $status))
            ->orderByDesc('id')
            ->paginate($perPage);

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
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }
}
