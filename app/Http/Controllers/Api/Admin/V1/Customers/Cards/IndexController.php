<?php

namespace App\Http\Controllers\Api\Admin\V1\Customers\Cards;

use App\Http\Controllers\Api\BaseController;

// Requests
use App\Http\Requests\Api\Admin\V1\Customers\Cards\IndexRequest;

// Resources
use App\Http\Resources\Api\Admin\V1\CustomerCards\ListResource;

// Model
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerCard;

class IndexController extends BaseController
{
    /**
     * Customer Cards List
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(IndexRequest $request)
    {
        $validatedData = $request->validated();

        $customer = Customer::where('unique_id', $validatedData['customer_unique_id'])->first();
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => trans('messages.api.admin.v1.customers.no_customers_found'),
            ], 404);
        }

        $customerCards = CustomerCard::query()
            ->where('customer_id', $customer->id)
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => trans('messages.api.admin.v1.customers.cards.cards_found'),
            'customer_cards' => ListResource::collection($customerCards),
        ]);
    }
}
