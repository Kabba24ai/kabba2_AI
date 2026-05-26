<?php

namespace App\Http\Controllers\Admin\Crm\SalesFunnels\QuickCreate;

use App\Http\Controllers\Controller;
use App\Models\Customers\EmailCategory;
use Illuminate\Http\Request;

/**
 * Inline quick-create for Email Category from the Funnel Step modal.
 */
class EmailCategoryController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        try {
            $category = EmailCategory::create([
                'name' => $validated['name'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Email category created.',
                'data'    => [
                    'id'   => $category->id,
                    'name' => $category->name,
                    'type' => 'Email',
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create email category.',
            ], 500);
        }
    }
}
