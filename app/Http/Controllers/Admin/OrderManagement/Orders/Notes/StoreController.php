<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders\Notes;

use App\Http\Controllers\Controller;

// Requests
use App\Http\Requests\Admin\OrderManagement\Orders\Notes\PostRequest;
use App\Models\Orders\Order;

class StoreController extends Controller
{
    /**
     * Handle bulk deletion of orders.
     */
    public function __invoke($uniqueId, PostRequest $request)
    {

        // Handle the creation of a new note
        $validated = $request->validated();

        try {
            $order = Order::where('unique_id', $uniqueId)->firstOrFail();

            $order->notes()->create([
                'note' => $validated['note'],
                'user_id' => $validated['user_id'],
                'created_by_type' => auth()->user() ? get_class(auth()->user()) : null,
                'created_by_id' => auth()->id(),
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Order not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Note created successfully.',
        ], 201);
    }
}
