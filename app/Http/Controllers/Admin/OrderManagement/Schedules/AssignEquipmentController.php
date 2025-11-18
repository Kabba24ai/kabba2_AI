<?php

namespace App\Http\Controllers\Admin\OrderManagement\Schedules;

use App\Enums\Equipments\EquipmentCurrentStatus;
use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Equipment;

// Models
use App\Models\Orders\OrderProduct;
use App\Http\Requests\Admin\OrderManagement\Schedules\AssignEquipmentRequest;
use App\Models\Iam\Personnel\User;

class AssignEquipmentController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\View\View
     */
    public function __invoke(AssignEquipmentRequest $request)
    {
        $validated = $request->validated();

        $orderProduct = OrderProduct::where('unique_id', $validated['order_product_unique_id'])->first();
        $equipment = Equipment::available()->where('unique_id', $validated['equipment_unique_id'])->first();
        $user = User::where('unique_id', $validated['user_unique_id'])->first();

        if (!$orderProduct || !$equipment) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Order Product or Equipment selected.'
            ], 404);
        }

        if(!empty($orderProduct->equipment_id) && $orderProduct->equipment_id !== $equipment->id) {
           Equipment::where('id', $orderProduct->equipment_id)
                ->update([
                    'current_status' => EquipmentCurrentStatus::Available->value,
                    'current_status_changed_at' => now(),
                    'current_order_id' => null,
                    'current_order_product_id' => null,
                ]);
        }

        if(!empty($orderProduct->equipment_id) && $orderProduct->equipment_id == $equipment->id){
            return response()->json([
                'success' => false,
                'message' => 'Equipment is already assigned to this order product.'
            ], 409);
        }

        // Assign equipment details to order product
        $orderProduct->equipment_id = $equipment->id;
        $orderProduct->equipment_details = $equipment->toArray();
        $orderProduct->assigned_by = $user->id;
        $orderProduct->assigned_at = now();
        $orderProduct->save();

        $equipment->current_status = EquipmentCurrentStatus::Rented->value;
        $equipment->current_status_changed_at = now();
        $equipment->current_order_id = $orderProduct->order_id;
        $equipment->current_order_product_id = $orderProduct->id;
        $equipment->save();

        return response()->json([
            'success' => true,
            'message' => 'Equipment assigned successfully!',
        ]);
    }
}
