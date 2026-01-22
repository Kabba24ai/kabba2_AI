<?php

namespace App\Http\Controllers\Api\Admin\V1\Orders;

use App\Http\Controllers\Controller;

// Helpers
use Illuminate\Http\JsonResponse;

// Enums
use App\Events\Admin\Orders\OrderAddressUpdatedEvent;

// Requests
use App\Http\Requests\Api\Admin\V1\Orders\UpdateAddressRequest;
use App\Http\Resources\Api\Admin\V1\OrderAddresses\ListResource;

// Models
use App\Models\Orders\Order;
use Illuminate\Support\Facades\DB;

class UpdateAddressController extends Controller
{
    /**
     * Orders Address Update
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(UpdateAddressRequest $request)
    {
        $validated = $request->validated();

        try {
            $order = Order::where('unique_id', $validated['order_unique_id'])->firstOrFail();

            $type = $validated['type'] ?? null;
            $address = null;
            $extra = [];

            if ($type === 'Billing' || $type === 'Shipping') {
                DB::transaction(function () use ($order, $validated, $type) {
                    if ($type === 'Billing') {
                        $order->billingAddress->update($validated);
                    } else {
                        $order->shippingAddress->update($validated);
                    }
                });
                $extra['same_as_billing'] = $order->shippingAddress->isSameAs($order->billingAddress);
                if ($type === 'Billing') {
                    $typeOfAction = 'Billing';
                    $address = $order->billingAddress;

                } else {
                    $typeOfAction = 'Delivery';
                    $address = $order->shippingAddress;
                }
            }

            $user = auth('api_user')->user();

            event(new OrderAddressUpdatedEvent($order, $user, $typeOfAction));
        } catch (\Exception $e) {
            return response()->json(['error' => trans('messages.api.admin.v1.orders.address_update_failed') . ': ' . $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'success' => true,
            'message' => trans('messages.api.admin.v1.orders.address_updated'),
            'address' => new ListResource($address)
        ]);
    }
}
