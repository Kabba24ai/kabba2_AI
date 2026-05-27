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

        $customersQuery = Customer::query()
            ->with(['notes' => fn($q) => $q
                ->with('user')
            , 'billingAddress.state', 'shippingAddress.state'])
            ->orderByRaw("CONCAT(first_name, ' ', last_name)");

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
