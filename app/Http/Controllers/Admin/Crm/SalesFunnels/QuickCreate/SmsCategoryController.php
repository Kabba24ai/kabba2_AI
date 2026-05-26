<?php

namespace App\Http\Controllers\Admin\Crm\SalesFunnels\QuickCreate;

use App\Http\Controllers\Controller;
use App\Models\Customers\SmsCategory;
use Illuminate\Http\Request;

/**
 * Inline quick-create for SMS Category from the Funnel Step modal.
 */
class SmsCategoryController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        try {
            $category = SmsCategory::create([
                'name' => $validated['name'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'SMS category created.',
                'data'    => [
                    'id'   => $category->id,
                    'name' => $category->name,
                    'type' => 'SMS',
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create SMS category.',
            ], 500);
        }
    }
}
