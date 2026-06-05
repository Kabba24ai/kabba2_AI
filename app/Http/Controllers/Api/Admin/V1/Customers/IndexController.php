<?php

namespace App\Http\Controllers\Api\Admin\V1\Customers;

use App\Helpers\CustomHelper;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

// Requests
use App\Http\Requests\Api\Admin\V1\Customers\IndexRequest;

// Resources
use App\Http\Resources\Api\Admin\V1\Customers\ListResource;

// Model
use App\Models\Customers\Customer;

class IndexController extends BaseController
{
    /**
     * Customers List
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(IndexRequest $request)
    {
        $validatedData = $request->validated();

        $perPage = $validatedData['per_page'] ?? 10;

        $customersQuery = Customer::query()->withCount('orders')
            ->with(['notes' => fn($q) => $q
                ->with('user')
            , 'billingAddress.state', 'shippingAddress.state'])
            ->orderByRaw("
                CASE
                    -- BAD DEBT: credit rule
                    WHEN (
                        is_credit_account != 1
                        AND (credit_limit IS NULL OR credit_limit = '')
                        AND available_credit_balance > 0
                    ) THEN 1

                    -- BAD DEBT: payment rule (60+ days since last payment)
                    WHEN (
                        SELECT DATEDIFF(CURDATE(), MAX(date))
                        FROM customer_accounts
                        WHERE customer_accounts.customer_id = customers.id
                        AND customer_accounts.type = 'payment'
                    ) >= 60 THEN 1

                    -- GOOD STANDING
                    ELSE 0
                END ASC,
                CONCAT_WS(' ', TRIM(first_name), TRIM(last_name)) COLLATE utf8mb4_unicode_ci ASC
            ");

        if (!empty($validatedData['search_name'])) {
            $customersQuery->where(function ($query) use ($validatedData) {
                $query->whereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", ['%' . $validatedData['search_name'] . '%']);
            });
        }

        if (!empty($validatedData['search_company_name'])) {
            $customersQuery->where('company_name', 'like', '%' . $validatedData['search_company_name'] . '%');
        }

        if (!empty($validatedData['search_phone'])) {
            $searchPhone = CustomHelper::unformatPhone($validatedData['search_phone']);
            $customersQuery->where('phone', 'like', '%' . $searchPhone . '%');
        }

        if (!empty($validatedData['tax_status']) && $validatedData['tax_status'] !== 'All') {
            $customersQuery->where('tax_status', $validatedData['tax_status']);
        }

        if (!empty($validatedData['tag'])) {
            $customersQuery->where(function ($query) use ($validatedData) {
                $query->where('tags', 'LIKE', '%"' . $validatedData['tag'] . '"%')
                    ->orWhere('tags', 'LIKE', '%' . $validatedData['tag'] . '%');
            });
        }

        $customers = $customersQuery->paginate($perPage);

        if ($customers->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => trans('messages.api.admin.v1.customers.no_customers_found'),
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'message' => trans('messages.api.admin.v1.customers.customers_found'),
            'customers' => ListResource::collection($customers),
            'pagination' => [
                'current_page' => $customers->currentPage(),
                'last_page'    => $customers->lastPage(),
                'per_page'     => $customers->perPage(),
                'total'        => $customers->total(),
            ],
        ]);
    }
}
