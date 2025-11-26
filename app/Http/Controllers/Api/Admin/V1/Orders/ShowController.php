<?php

namespace App\Http\Controllers\Api\Admin\V1\Orders;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

// Requests
use App\Http\Requests\Api\Admin\V1\Orders\ShowRequest;

// Resources
use App\Http\Resources\Api\Admin\V1\Orders\ListResource;

// Model
use App\Models\Orders\Order;

class ShowController extends BaseController
{
    /**
     * Order Details
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(ShowRequest $request)
    {
        $validatedData = $request->validated();

        $uniqueId = $validatedData['unique_id'];

        $order = Order::query()->with('shippingAddress', 'billingAddress', 'licenseMedia', 'products.product', 'lastPayment', 'notes', 'products.deliveryMedia', 'products.pickupMedia', 'products.deliverySignatureMedia', 'products.returnSignatureMedia', 'products.deliveryStore','products.pickupStore', 'products.softEquipment')
            ->where('unique_id', $uniqueId)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => trans('messages.api.admin.v1.orders.order_not_found'),
            ], JsonResponse::HTTP_NOT_FOUND);
        }



        return response()->json([
            'success' => true,
            'message' => trans('messages.api.admin.v1.orders.order_found'),
            'order' => new ListResource($order),
        ]);
    }
}
