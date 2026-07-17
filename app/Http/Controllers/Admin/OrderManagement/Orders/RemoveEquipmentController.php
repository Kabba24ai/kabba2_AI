<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders;

use App\Enums\Equipments\EquipmentCurrentStatus;
use App\Http\Controllers\Controller;
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentStatusLog;
use App\Models\Orders\OrderProduct;
use App\Models\Orders\OrderProductChecklistQuestionAnswers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RemoveEquipmentController extends Controller
{
    /**
     * Remove equipment assignment from an order product.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_product_unique_id' => ['required', 'string'],
        ]);

        $orderProduct = OrderProduct::with(['equipment', 'softAssignment', 'checklistQuestions'])
            ->whereHas('order')
            ->where('unique_id', $validated['order_product_unique_id'])
            ->first();

        if (!$orderProduct) {
            return response()->json(['success' => false, 'message' => 'Order product not found.'], 404);
        }

        $user = auth()->user();

        $orderProduct->softAssignment()->delete();

        $equipment = $orderProduct->equipment;
        if ($equipment) {
            if ($equipment->current_order_product_id === $orderProduct->id) {
                $equipment->current_order_id = null;
                $equipment->current_order_product_id = null;
            }

            $beforeStatus = $equipment->current_status?->value;
            $equipment->current_status = EquipmentCurrentStatus::Available->value;
            $equipment->current_status_updated_by = $user?->id;
            $equipment->current_status_changed_at = now();
            $equipment->saveQuietly();
            EquipmentStatusLog::recordTransition($equipment->id, $beforeStatus, EquipmentCurrentStatus::Available->value, $user?->id);
        }

        // BUG-3 (P3-12A) fix: soft-delete the child answer rows before their parent
        // questions, mirroring RemoveController's fix — this was previously a permanent
        // orphan (no rebuild follows in this controller, unlike SaveDeliveryController/
        // AssignEquipmentController). See
        // docs/checklist-system-audit/P3_12A_BUG3_COMPLETE_SOFT_DELETE_CASCADE.md.
        $staleQuestionIds = $orderProduct->checklistQuestions()->pluck('id');
        OrderProductChecklistQuestionAnswers::whereIn('order_product_checklist_question_id', $staleQuestionIds)->delete();

        $orderProduct->checklistQuestions()->delete();
        $orderProduct->equipment_id = null;
        $orderProduct->equipment_details = null;
        $orderProduct->assigned_by = null;
        $orderProduct->assigned_at = null;
        $orderProduct->delivery_by = null;
        $orderProduct->pickup_by = null;
        $orderProduct->is_delivered = false;
        $orderProduct->is_returned = false;
        $orderProduct->delivery_status = 'Pending';
        $orderProduct->pickup_status = 'Pending';
        $orderProduct->save();

        return response()->json([
            'success' => true,
            'message' => 'Equipment removed successfully.'
        ]);
    }
}
