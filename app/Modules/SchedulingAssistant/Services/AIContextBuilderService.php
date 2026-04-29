<?php

namespace App\Modules\SchedulingAssistant\Services;

use App\Models\ProductManagement\ProductEquipmentAssignment;
use App\Models\Orders\OrderProduct;
use App\Modules\SchedulingAssistant\DTOs\AssistantResultData;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AIContextBuilderService
{
    public function build(int $orderProductId, AssistantResultData $assistantResult): array
    {
        $orderProduct = OrderProduct::query()->find($orderProductId);

        if (!$orderProduct) {
            throw new ModelNotFoundException("OrderProduct {$orderProductId} not found.");
        }

        $equipmentAssignments = $this->loadEquipmentAssignmentsForOrderProduct($orderProduct);

        return [
            'order' => [
                'order_product_id' => (int) $orderProduct->id,
                'product_id' => $orderProduct->product_id ?? null,
                'product_name' => $orderProduct->product_name ?? null,
                'delivery_datetime' => $assistantResult->orderWindow['delivery'] ?? null,
                'pickup_datetime' => $assistantResult->orderWindow['pickup'] ?? null,
                'delivery_transport_mode' => $orderProduct->delivery_transport_mode ?? null,
                'pickup_transport_mode' => $orderProduct->pickup_transport_mode ?? null,
                'service_method' => $orderProduct->service_method ?? null,
                'delivery_store_id' => $orderProduct->delivery_store_id ?? null,
                'pickup_store_id' => $orderProduct->pickup_store_id ?? null,
            ],
            'current_assignment' => $assistantResult->currentAssignment,
            'issues' => $assistantResult->issues,
            'candidates' => $assistantResult->toArray()['recommended_candidates'],
            'equipment_assignments' => $equipmentAssignments,
            'policy' => [
                'primary_behavior' => 'allowed_no_action',
                'upgrade_behavior' => 'allowed_notify_only',
                'downgrade_behavior' => 'requires_review_and_customer_approval',
                'assistant_mode' => 'advisory_only',
                'system_may_auto_assign' => false,
            ],
        ];
    }

    protected function loadEquipmentAssignmentsForOrderProduct(OrderProduct $orderProduct): array
    {
        $assignment = ProductEquipmentAssignment::query()
            ->where('product_id', $orderProduct->product_id)
            ->where('is_active', true)
            ->with(['paths.items'])
            ->first();

        if (!$assignment) {
            return [];
        }

        return [
            'primary_equipment_pool' => $assignment->primary_equipment_pool,
            'upgrade_path_primary' => $assignment->upgrade_path_primary,
            'upgrade_path_alternate_1' => $assignment->upgrade_path_alternate_1,
            'upgrade_path_alternate_2' => $assignment->upgrade_path_alternate_2,
            'downgrade_path_option_1' => $assignment->downgrade_path_option_1,
            'assignment_notes' => $assignment->assignment_notes,
        ];
    }
}
