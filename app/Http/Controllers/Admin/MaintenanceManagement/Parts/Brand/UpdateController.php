<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Parts\Brand;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Models\MaintenanceManagement\PartBrand;
use App\Http\Requests\Admin\MaintenanceManagement\Parts\Brand\StoreRequest;

class UpdateController extends Controller
{
    public function __invoke(StoreRequest $request, PartBrand $brand)
    {
        // Log::info("==== BRAND UPDATE REQUEST RECEIVED ====", [
        //     'incoming_data' => $request->all(),
        //     'brand_before' => $brand->toArray()
        // ]);

        try {
            $data = $request->validated();

            // Log::info("Brand validated data", $data);

            $updated = $brand->update([
                'name' => $data['name'],
            ]);

            // Log::info("Brand update result", [
            //     "updated" => $updated,
            //     "brand_after" => $brand->fresh()->toArray()
            // ]);

            return response()->json([
                'success' => true,
                'message' => 'Part Brand updated successfully!',
                'brand' => $brand->fresh(),
            ]);

        } catch (\Throwable $e) {
            Log::error("BRAND UPDATE FAILED", [
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Brand update failed!',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
