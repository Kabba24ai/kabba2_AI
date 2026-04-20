<?php

namespace App\Modules\SchedulingAssistant\Services;

use App\Models\Orders\OrderProduct;
use App\Models\MaintenanceManagement\EquipmentSoftAssign;
use App\Modules\SchedulingAssistant\DTOs\AssistantResultData;
use App\Modules\SchedulingAssistant\Support\DateRangeHelper;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ScheduleAssistantService
{
    public function __construct(
        protected CandidateEquipmentService $candidateEquipmentService,
        protected EquipmentEligibilityService $equipmentEligibilityService
    ) {
    }

    public function analyzeOrderProduct(int $orderProductId): AssistantResultData
    {
        $orderProduct = OrderProduct::query()->find($orderProductId);

        if (!$orderProduct) {
            throw new ModelNotFoundException("OrderProduct {$orderProductId} not found.");
        }

        $currentAssignment = EquipmentSoftAssign::query()
            ->with('equipment')
            ->where('order_product_id', $orderProduct->id)
            ->first();

        // equipment list according the product category and the product name
        $candidates = $this->candidateEquipmentService->getCandidatesForOrderProduct($orderProduct);


        $evaluated = [];

        foreach ($candidates as $equipment) {
            $evaluated[] = $this->equipmentEligibilityService->evaluate(
                orderProduct: $orderProduct,
                equipment: $equipment,
                currentAssignedEquipmentId: $currentAssignment?->equipment_id
            );
        }

        usort($evaluated, fn ($a, $b) => $b->score <=> $a->score);

        return new AssistantResultData(
            orderProductId: (int) $orderProduct->id,
            orderWindow: [
                'delivery' => optional(DateRangeHelper::combine(
                    $orderProduct->delivery_date,
                    $orderProduct->delivery_time
                ))?->toDateTimeString(),
                'pickup' => optional(DateRangeHelper::combine(
                    $orderProduct->pickup_date,
                    $orderProduct->pickup_time
                ))?->toDateTimeString(),
            ],
            currentAssignment: $currentAssignment ? [
                'equipment_id' => (int) $currentAssignment->equipment_id,
                'equipment_name' => optional($currentAssignment->equipment)?->name
                    ?? optional($currentAssignment->equipment)?->title
                    ?? optional($currentAssignment->equipment)?->equipment_name,
            ] : null,
            issues: $this->buildIssues($orderProduct, $currentAssignment, $evaluated),
            recommendedCandidates: $evaluated
        );
    }

    protected function buildIssues(OrderProduct $orderProduct, ?EquipmentSoftAssign $currentAssignment, array $evaluated): array
    {
        $issues = [];

        if (!$currentAssignment) {
            $issues[] = [
                'code' => 'UNASSIGNED',
                'severity' => 'warning',
                'message' => 'This order product does not currently have equipment assigned.',
            ];
        }

        if (empty($orderProduct->delivery_date) || empty($orderProduct->pickup_date)) {
            $issues[] = [
                'code' => 'MISSING_DATE_WINDOW',
                'severity' => 'error',
                'message' => 'Delivery date and pickup date are required for schedule analysis.',
            ];
        }

        $blockedCount = collect($evaluated)
            ->filter(fn ($candidate) => $candidate->eligibilityStatus->value === 'blocked')
            ->count();

        if ($blockedCount === count($evaluated) && count($evaluated) > 0) {
            $issues[] = [
                'code' => 'NO_SAFE_CANDIDATES',
                'severity' => 'error',
                'message' => 'No safe equipment candidates were found for the requested rental window.',
            ];
        }

        return $issues;
    }
}
