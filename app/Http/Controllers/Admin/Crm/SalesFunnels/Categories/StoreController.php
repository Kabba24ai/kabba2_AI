<?php

namespace App\Http\Controllers\Admin\Crm\SalesFunnels\Categories;

use App\Http\Controllers\Controller;

// Models
use App\Models\Customers\SalesFunnelCategory;

// Requests
use App\Http\Requests\Admin\Crm\SalesFunnels\Categories\StoreRequest;

class StoreController extends Controller
{
    public function __invoke(StoreRequest $request)
    {
        $validated = $request->validated();

        try {
            $category = SalesFunnelCategory::create($validated);
        } catch (\Exception $e) {

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to create category. Please try again.',
                    'error' => $e->getMessage(), // optional for debugging
                ],
                500,
            );
        }

         return response()->json([
            'success' => true,
            'message' => 'Sales funnel category created successfully.',
            'data' => $category,
        ]);
    }
}
