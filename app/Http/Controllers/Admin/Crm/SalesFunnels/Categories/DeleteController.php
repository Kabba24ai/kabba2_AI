<?php

namespace App\Http\Controllers\Admin\Crm\SalesFunnels\Categories;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Models
use App\Models\Customers\SalesFunnelCategory;

class DeleteController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function __invoke($unique_id, Request $request)
    {
        try {
            $objRecord = SalesFunnelCategory::where('unique_id', $unique_id)->firstOrFail();
            $objRecord->delete();
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Category not found.'], 404);
        }

        return response()->json(
            [
                'success' => true,
                'message' => 'Category deleted successfully.',
            ],
            200,
        );
    }
}
