<?php

namespace App\Http\Controllers\Api\Admin\V1\Orders\Notes;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

// Events
use App\Events\Admin\Orders\OrderNoteEvent;

// Requests
use App\Http\Requests\Api\Admin\V1\Orders\Notes\UpdateRequest;

// Resources
use App\Http\Resources\Api\Admin\V1\OrderNotes\ListResource;

// Model
use App\Models\Iam\Personnel\User;
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
            $orderNote = OrderNote::with('order')->where('unique_id', $validatedData['order_note_unique_id'])->firstOrFail();

            $orderNote->update([
                'note' => $validatedData['note'],
                'user_id' => $validatedData['user_id'],
                'updated_by_type' => User::class,
                'updated_by_id' => $validatedData['user_id'],
            ]);

            $order = $orderNote->order;

            $user = auth('api_user')->user();
            $typeOfAction = 'updated';

            // Fire event for the updated note
            event(new OrderNoteEvent($order, $user, $typeOfAction, $orderNote->refresh()));

            return response()->json([
                'success' => true,
                'message' => trans('messages.api.admin.v1.orders.note_update_success'),
                'note' => new ListResource($orderNote->refresh())
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => trans('messages.api.admin.v1.orders.note_not_found')], JsonResponse::HTTP_NOT_FOUND);
        }

    }
}
