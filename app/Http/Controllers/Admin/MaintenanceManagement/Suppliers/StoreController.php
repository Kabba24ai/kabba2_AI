<?php
namespace App\Http\Controllers\Admin\MaintenanceManagement\Suppliers;

use App\Helpers\MediaHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

// Request
use App\Http\Requests\Admin\MaintenanceManagement\Suppliers\StoreRequest;

// Models
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductMediaChild;

class StoreController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(StoreRequest $request)
    {
        $validated = $request->validated();

        // dd($validated);

        DB::beginTransaction();

        try {
           

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            // Handle error for AJAX
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the product.'
            ], 500);


        }
    }
}
