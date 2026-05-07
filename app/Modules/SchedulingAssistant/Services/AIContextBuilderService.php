<?php

namespace App\Modules\SchedulingAssistant\Services;

use App\Models\MaintenanceManagement\Equipment;
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
        $productId = (int) ($orderProduct->product_id ?? 0);

        if ($productId <= 0) {
            return [];
        }

        $primary = Equipment::query()
            ->where('assigned_product_id', $productId)
            ->orderBy('id')
            ->get(['id', 'allow_upgrades', 'allow_downgrades', 'downgrade_requires_approval', 'critical_matching_criteria']);

        $primaryIds = $primary
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $alternatives = collect();

        if (!empty($primaryIds)) {
            $alternatives = Equipment::query()
                ->where('assigned_product_id', '!=', $productId)
                ->where(function ($query) {
                    $query->where('allow_upgrades', true)
                        ->orWhere('allow_downgrades', true);
                })
                ->where(function ($query) use ($primaryIds) {
                    foreach ($primaryIds as $primaryEquipmentId) {
                        $query->orWhereJsonContains('similar_equipment_ids', $primaryEquipmentId);
                    }
                })
                ->orderBy('id')
                ->get(['id', 'allow_upgrades', 'allow_downgrades', 'downgrade_requires_approval', 'critical_matching_criteria']);
        }

        return [
            'source' => 'equipment_model',
            'primary_equipment_pool' => $primary->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            'upgrade_candidates' => $alternatives
                ->filter(fn ($equipment) => (bool) ($equipment->allow_upgrades ?? false))
                ->map(fn ($equipment) => [
                    'equipment_id' => (int) $equipment->id,
                    'allow_upgrades' => (bool) ($equipment->allow_upgrades ?? false),
                    'critical_matching_criteria' => (array) ($equipment->critical_matching_criteria ?? []),
                ])
                ->values()
                ->all(),
            'downgrade_candidates' => $alternatives
                ->filter(fn ($equipment) => (bool) ($equipment->allow_downgrades ?? false))
                ->map(fn ($equipment) => [
                    'equipment_id' => (int) $equipment->id,
                    'allow_downgrades' => (bool) ($equipment->allow_downgrades ?? false),
                    'downgrade_requires_approval' => (bool) ($equipment->downgrade_requires_approval ?? false),
                    'critical_matching_criteria' => (array) ($equipment->critical_matching_criteria ?? []),
                ])
                ->values()
                ->all(),
        ];
    }
}
