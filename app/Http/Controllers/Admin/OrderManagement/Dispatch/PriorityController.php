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

        $field = $request->type === 'delivery' ? 'delivery_priority' : 'pickup_priority';
        $orderProduct->update([$field => $request->priority ?: null]);

        return response()->json(['success' => true, 'priority' => $orderProduct->$field]);
    }
}
