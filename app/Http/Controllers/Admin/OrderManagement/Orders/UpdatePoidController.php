<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderManagement\Orders\UpdatePoidRequest;
use App\Models\Orders\Order;

class UpdatePoidController extends Controller
{
    public function __invoke(UpdatePoidRequest $request)
    {
        $validatedData = $request->validated();

        $order = Order::findOrFail($validatedData['order_id']);
    
        $order->po_id = $validatedData['po_id'];
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'PO ID updated successfully.',
        ]);
    }

}
