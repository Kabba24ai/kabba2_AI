<?php

namespace App\Http\Controllers\Api\Admin\V1\Orders\Notes;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

// Requests
use App\Http\Requests\Api\Admin\V1\Orders\Notes\StoreRequest;
use App\Http\Resources\Api\Admin\V1\OrderNotes\ListResource;
use App\Models\Iam\Personnel\User;

// Model
use App\Models\Orders\Order;

class StoreController extends BaseController
{
    /**
     * Orders Note Store
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(StoreRequest $request)
    {
        $validatedData = $request->validated();

        try {
            $order = Order::where('unique_id', $validatedData['order_unique_id'])->firstOrFail();

            $order->notes()->create([
                'note' => $validatedData['note'],
                'user_id' => $validatedData['user_id'],
                'created_by_type' => User::class,
                'created_by_id' => $validatedData['user_id'],
            ]);

            return response()->json([
                'success' => true,
                'message' => trans('messages.api.admin.v1.orders.note_created'),
                'note' => new ListResource($order->notes()->latest()->first())
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => trans('messages.api.admin.v1.orders.order_not_found')], JsonResponse::HTTP_NOT_FOUND);
        }

    }
}
