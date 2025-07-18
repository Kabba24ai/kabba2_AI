<?php
namespace App\Http\Controllers\Admin\OrderManagement\Orders;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

use App\Http\Requests\Admin\OrderManagement\Orders\UpdateOrderAddressRequest;
use App\Models\Orders\Order;

class UpdateOrderAddressController extends Controller
{
    public function __invoke($orderUniqueId, UpdateOrderAddressRequest $request)
    {
        $validatedData = $request->validated();

        $order = Order::where('unique_id', $orderUniqueId)->first();
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.'
            ], 404);
        }

        $type = $validatedData['type'] ?? null;
        $address = null;
        $message = '';
        $extra = [];

        if ($type === 'Billing' || $type === 'Shipping') {
            DB::transaction(function () use ($order, $validatedData, $type) {
                if ($type === 'Billing') {
                    $order->billingAddress->update($validatedData);
                } else {
                    $order->shippingAddress->update($validatedData);
                }
            });
            $extra['same_as_billing'] = $order->shippingAddress->isSameAs($order->billingAddress);
            if ($type === 'Billing') {
                $address = $order->billingAddress;
                $message = 'Billing address updated.';
            } else {
                $address = $order->shippingAddress;
                $message = 'Shipping address updated.';
            }
            return response()->json([
                'success' => true,
                'message' => $message,
                'type' => $type,
                'data' => array_merge([
                    'address' => $address
                ], $extra)
            ], 200);
        } else {
            return response()->json(['success' => false, 'message' => 'Invalid address type.'], 422);
        }
    }
}
