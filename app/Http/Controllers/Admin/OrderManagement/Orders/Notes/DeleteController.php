<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders\Notes;

use App\Http\Controllers\Controller;
use App\Models\Orders\Order;

class DeleteController extends Controller
{
    /**
     * Delete a note from an order.
     */
    public function __invoke($uniqueId, $noteUniqueId)
    {
        try {
            $order = Order::where('unique_id', $uniqueId)->firstOrFail();
            $note = $order->notes()->where('id', $noteUniqueId)->firstOrFail();

            $note->delete();
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Order or note not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Note deleted successfully.',
        ], 200);
    }
}
