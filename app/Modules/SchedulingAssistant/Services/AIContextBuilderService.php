<?php

namespace App\Modules\SchedulingAssistant\Services;

use App\Models\Global\AIAssignmentRule;
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

        $rules = $this->loadRulesForOrderProduct($orderProduct);

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
            'rules' => $rules,
            'policy' => [
                'primary_behavior' => 'allowed_no_action',
                'upgrade_behavior' => 'allowed_notify_only',
                'downgrade_behavior' => 'requires_review_and_customer_approval',
                'assistant_mode' => 'advisory_only',
                'system_may_auto_assign' => false,
            ],
        ];
    }

    protected function loadRulesForOrderProduct(OrderProduct $orderProduct): array
    {
        $query = AIAssignmentRule::query()
            ->where('active', true)
            ->where(function ($q) use ($orderProduct) {
                if (!empty($orderProduct->product_id)) {
                    $q->orWhere('product_id', $orderProduct->product_id);
                }

                if (!empty($orderProduct->product_name)) {
                    $q->orWhere('product_name', $orderProduct->product_name);
                }
            })
            ->orderBy('relationship_type')
            ->orderBy('equipment_name');

        return $query->get()->map(function (AIAssignmentRule $rule) {
            return [
                'product_id' => $rule->product_id,
                'product_name' => $rule->product_name,
                'equipment_id' => $rule->equipment_id,
                'equipment_name' => $rule->equipment_name,
                'relationship_type' => $rule->relationship_type,
                'actions_required' => $rule->actions_required ?? [],
                'notes' => $rule->notes,
            ];
        })->values()->all();
    }
}
