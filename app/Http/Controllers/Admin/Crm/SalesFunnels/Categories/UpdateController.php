<?php

namespace App\Http\Controllers\Admin\Crm\SalesFunnels\Categories;

use App\Http\Controllers\Controller;

// Models
use App\Models\Customers\SalesFunnelCategory;

// Requests
use App\Http\Requests\Admin\Crm\SalesFunnels\Categories\UpdateRequest;

class UpdateController extends Controller
{
    public function __invoke($uniqueId,UpdateRequest $request)
    {
        $validated = $request->validated();

        try {
            $category = SalesFunnelCategory::where('unique_id', $uniqueId)->firstOrFail();
            $category->update($validated);
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
        ]);
    }
}
