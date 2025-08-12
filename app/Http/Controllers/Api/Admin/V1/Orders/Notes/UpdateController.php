<?php

namespace App\Http\Controllers\Api\Admin\V1\Orders\Notes;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

// Requests
use App\Http\Requests\Api\Admin\V1\Orders\Notes\UpdateRequest;
use App\Http\Resources\Api\Admin\V1\OrderNotes\ListResource;
use App\Models\Iam\Personnel\User;

// Model
use App\Models\Orders\OrderNote;

class UpdateController extends BaseController
{
    /**
     * Orders Note Update
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(UpdateRequest $request)
    {
        $validatedData = $request->validated();

        try {
            $order = OrderNote::where('unique_id', $validatedData['order_note_unique_id'])->firstOrFail();

            $order->update([
                'note' => $validatedData['note'],
                'user_id' => $validatedData['user_id'],
                'updated_by_type' => User::class,
                'updated_by_id' => $validatedData['user_id'],
            ]);

            return response()->json([
                'success' => true,
                'message' => trans('messages.api.admin.v1.orders.note_update_success'),
                'note' => new ListResource($order->notes()->latest()->first())
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => trans('messages.api.admin.v1.orders.note_not_found')], JsonResponse::HTTP_NOT_FOUND);
        }

    }
}
