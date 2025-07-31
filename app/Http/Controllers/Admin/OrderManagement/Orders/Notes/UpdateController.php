<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders\Notes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderManagement\Orders\Notes\PostRequest;
use App\Models\Orders\Order;

class UpdateController extends Controller
{
    /**
     * Update an existing note for an order.
     */
    public function __invoke($uniqueId, $noteUniqueId, PostRequest $request)
    {
        $validated = $request->validated();

        try {
            $order = Order::where('unique_id', $uniqueId)->firstOrFail();
            $note = $order->notes()->where('id', $noteUniqueId)->firstOrFail();

            $note->update([
                'note' => $validated['note'],
                'user_id' => $validated['user_id'],
                'updated_by_type' => auth()->user() ? get_class(auth()->user()) : null,
                'updated_by_id' => auth()->id(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Order or note not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Note updated successfully.',
        ], 200);
    }
}
