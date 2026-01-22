<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipment;

use App\Http\Controllers\Controller;

// Requests
use App\Http\Requests\Admin\MaintenanceManagement\Equipment\AssignStoreRequest;

// Models
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Stores\Store;

class AssignStoreController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\View\View
     */
    public function __invoke(AssignStoreRequest $request)
    {
        $validated = $request->validated();

        $store = Store::where('unique_id', $validated['store_unique_id'])->first();
        $equipment = Equipment::where('unique_id', $validated['equipment_unique_id'])->first();

        if (!$store || !$equipment) {
            return response()->json([
                'success' => false,
                'message' => 'Store or Equipment not found.',
            ], 404);
        }

        if ($equipment->store_id === $store->id) {
            return response()->json([
                'success' => false,
                'message' => 'This Store is already assigned to the Equipment.',
            ], 400);
        }

        // Assign equipment details to order product
        $equipment->store_id = $store->id;
        $equipment->save();

        return response()->json([
            'success' => true,
            'message' => 'Store assigned successfully!',
        ]);
    }
}
