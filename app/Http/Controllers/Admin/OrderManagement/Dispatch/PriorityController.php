<?php

namespace App\Http\Controllers\Admin\OrderManagement\Dispatch;

use App\Http\Controllers\Controller;
use App\Models\Orders\OrderProduct;
use Illuminate\Http\Request;

class PriorityController extends Controller
{
    public function __invoke(Request $request, string $unique_id)
    {
        $request->validate([
            'type'     => 'required|in:delivery,return',
            'priority' => 'nullable|integer|min:1|max:9999',
        ]);

        $orderProduct = OrderProduct::where('unique_id', $unique_id)->firstOrFail();

        $isDelivery    = $request->type === 'delivery';
        $priorityField = $isDelivery ? 'delivery_priority' : 'pickup_priority';
        $lockField     = $isDelivery ? 'delivery_priority_locked' : 'pickup_priority_locked';

        $newPriority = $request->priority ?: null;

        $orderProduct->update([
            $priorityField => $newPriority,
            $lockField     => $newPriority !== null,  // lock when set, release when cleared
        ]);

        return response()->json(['success' => true, 'priority' => $orderProduct->$priorityField]);
    }
}
