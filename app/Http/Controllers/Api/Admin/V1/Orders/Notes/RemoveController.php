<?php

namespace App\Http\Controllers\Api\Admin\V1\Orders\Notes;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

// Requests
use App\Http\Requests\Api\Admin\V1\Orders\Notes\RemoveRequest;

// Model
use App\Models\Orders\OrderNote;

class RemoveController extends BaseController
{
    /**
     * Orders Note Remove
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(RemoveRequest $request)
    {
        $validatedData = $request->validated();

        try {
            $order = OrderNote::where('unique_id', $validatedData['order_note_unique_id'])->firstOrFail();
            $order->delete();

            return response()->json([
                'success' => true,
                'message' => trans('messages.api.admin.v1.orders.note_deleted'),
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => trans('messages.api.admin.v1.orders.note_not_found')], JsonResponse::HTTP_NOT_FOUND);
        }

    }
}
