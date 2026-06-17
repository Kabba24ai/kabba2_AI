<?php

namespace App\Http\Controllers\Admin\OrderManagement\ScheduleAssignment;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\MaintenanceManagement\EquipmentAiProfile;
use App\Models\MaintenanceManagement\EquipmentCategoryComparisonKey;
use App\Models\Orders\OrderProduct;
use App\Services\AiEquipmentSuggestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiEquipmentSuggestionController extends Controller
{
    public function __construct(private AiEquipmentSuggestionService $aiService) {}

    public function __invoke(Request $request, int $orderProductId): JsonResponse
    {
        // ── 1. Load order product ─────────────────────────────────────────────
        $orderProduct = OrderProduct::with([
            'product.categories',
            'softAssignment.equipment',
            'order',
        ])
            ->whereHas('order')
            ->find($orderProductId);

        if (!$orderProduct) {
            return response()->json(['success' => false, 'message' => 'Order product not found.'], 404);
        }

        $categoryIds = $orderProduct->product?->categories?->pluck('id')->toArray() ?? [];

        if (empty($categoryIds)) {
            return response()->json(['success' => false, 'message' => 'No product category linked to this order product.'], 422);
        }

        // ── 2. Comparison keys ────────────────────────────────────────────────
        $comparisonKeys = EquipmentCategoryComparisonKey::whereIn('category_id', $categoryIds)
            ->orderByRaw("FIELD(importance_level, 'critical', 'high', 'medium', 'low')")
            ->get()
            ->keyBy(fn($ck) => mb_strtolower(trim($ck->spec_key)));

        // ── 3. Candidate equipment ────────────────────────────────────────────
        $deliveryDate    = $orderProduct->delivery_date;
        $pickupDate      = $orderProduct->pickup_date;
        $currentAssignId = $orderProduct->softAssignment?->equipment_id;

        $candidates = Equipment::with(['productCategory', 'store'])
            ->where('not_for_rent', 0)
            ->whereIn('product_category_id', $categoryIds)
            ->whereIn('current_status', ['Available', 'Rented', 'Maintenance'])
            ->get();

        $candidateMap = [];   // keyed by equipment->id for fast merge later

        foreach ($candidates as $equipment) {
            $status = $equipment->current_status instanceof \BackedEnum
                ? $equipment->current_status->value
                : (string) $equipment->current_status;

            // Skip Rented equipment that conflicts with this order window
            if ($status === 'Rented' && $deliveryDate && $pickupDate) {
                $conflict = $equipment->softAssignments()
                    ->whereHas('orderProduct', function ($q) use ($deliveryDate, $pickupDate, $orderProductId) {
                        $q->whereHas('order')
                            ->where('id', '!=', $orderProductId)
                            ->where('delivery_date', '<=', $pickupDate)
                            ->where('pickup_date', '>=', $deliveryDate);
                    })
                    ->exists();

                if ($conflict) {
                    continue;
                }
            }

            // Load AI profile key specs
            $profileIds  = array_filter((array) ($equipment->comparable_ai_profile_ids ?? []));
            $matchedSpecs = [];

            if (!empty($profileIds)) {
                $profiles = EquipmentAiProfile::with('keySpecifications')
                    ->whereIn('id', $profileIds)
                    ->where('ai_status', 'completed')
                    ->get();

                foreach ($profiles as $profile) {
                    foreach ($profile->keySpecifications as $spec) {
                        $key = mb_strtolower(trim($spec->spec_key));
                        if ($comparisonKeys->has($key)) {
                            $ck = $comparisonKeys->get($key);
                            $matchedSpecs[$key] = [
                                'spec_key'   => $key,
                                'label'      => $ck->display_label,
                                'value'      => $spec->spec_value,
                                'unit'       => $spec->spec_unit,
                                'importance' => $ck->importance_level,
                                'confidence_pct' => (int) round((float) $spec->confidence_score * 100),
                            ];
                        }
                    }
                }
            }

            $candidateMap[$equipment->id] = [
                'equipment_id'          => $equipment->id,
                'equipment_unique_id'   => $equipment->unique_id,
                'equipment_name'        => $equipment->equipment_name,
                'equipment_number'      => $equipment->equipment_id,
                'status'                => $status,
                'status_label'          => $equipment->status_label,
                'store'                 => $equipment->store?->store_name,
                'category'              => $equipment->productCategory?->title,
                'has_ai_profile'        => !empty($profileIds),
                'matched_specs'         => array_values($matchedSpecs),
                'matched_spec_count'    => count($matchedSpecs),
                'total_comparison_keys' => $comparisonKeys->count(),
                'is_currently_assigned' => $currentAssignId && $currentAssignId === $equipment->id,
            ];
        }

        if (empty($candidateMap)) {
            return response()->json([
                'success'  => true,
                'suggestions' => [],
                'overall_summary' => 'No available equipment found in this category for the order window.',
                'order_product' => $this->orderProductSummary($orderProduct),
            ]);
        }

        // ── 4. Build OpenAI context ───────────────────────────────────────────
        $context = [
            'order' => array_merge($this->orderProductSummary($orderProduct), [
                'category' => $orderProduct->product?->categories?->first()?->title,
                'duration_days' => ($deliveryDate && $pickupDate)
                    ? \Carbon\Carbon::parse($deliveryDate)->diffInDays(\Carbon\Carbon::parse($pickupDate))
                    : null,
            ]),
            'comparison_criteria' => $comparisonKeys->values()->map(fn($ck) => [
                'spec_key'   => $ck->spec_key,
                'label'      => $ck->display_label,
                'importance' => $ck->importance_level,
            ])->values()->toArray(),
            'candidates' => array_values(array_map(fn($c) => [
                'equipment_id'   => $c['equipment_id'],
                'equipment_name' => $c['equipment_name'],
                'equipment_number' => $c['equipment_number'],
                'status'         => $c['status'],
                'store'          => $c['store'],
                'has_ai_profile' => $c['has_ai_profile'],
                'matched_specs'  => $c['matched_specs'],
            ], $candidateMap)),
        ];

        // ── 5. Ask OpenAI ─────────────────────────────────────────────────────
        $aiResult = $this->aiService->suggest($context);

        if (!$aiResult['success']) {
            return response()->json([
                'success' => false,
                'message' => 'AI service error: ' . ($aiResult['error'] ?? 'Unknown error.'),
            ], 500);
        }

        // ── 6. Merge AI ranking with local candidate data ─────────────────────
        $aiSuggestions = $aiResult['suggestions'] ?? [];

        // Index AI suggestions by equipment_id
        $aiIndex = collect($aiSuggestions)->keyBy('equipment_id');

        // Build the merged, AI-ranked list
        $merged = [];

        foreach ($aiSuggestions as $aiItem) {
            $local = $candidateMap[$aiItem['equipment_id']] ?? null;
            if (!$local) {
                continue;
            }

            $merged[] = array_merge($local, [
                'rank'                => $aiItem['rank'],
                'suitability_score'   => $aiItem['suitability_score'],
                'recommendation_type' => $aiItem['recommendation_type'],
                'reasoning'           => $aiItem['reasoning'],
                'actions_required'    => $aiItem['actions_required'] ?? [],
                'warnings'            => $aiItem['warnings'] ?? [],
            ]);
        }

        // Any local candidates not ranked by AI go at the bottom
        foreach ($candidateMap as $id => $local) {
            if (!$aiIndex->has($id)) {
                $merged[] = array_merge($local, [
                    'rank'                => 999,
                    'suitability_score'   => 0,
                    'recommendation_type' => 'not_recommended',
                    'reasoning'           => 'Not evaluated by AI.',
                    'actions_required'    => [],
                    'warnings'            => ['Not included in AI response.'],
                ]);
            }
        }

        usort($merged, fn($a, $b) => $a['rank'] <=> $b['rank']);

        return response()->json([
            'success'         => true,
            'order_product'   => $this->orderProductSummary($orderProduct),
            'overall_summary' => $aiResult['overall_summary'] ?? '',
            'comparison_keys' => $comparisonKeys->values()->map(fn($ck) => [
                'spec_key'   => $ck->spec_key,
                'label'      => $ck->display_label,
                'importance' => $ck->importance_level,
            ])->values(),
            'suggestions'       => $merged,
            'total_candidates'  => count($candidateMap),
        ]);
    }

    private function orderProductSummary(OrderProduct $op): array
    {
        return [
            'id'            => $op->id,
            'unique_id'     => $op->unique_id,
            'product_name'  => $op->product_name,
            'delivery_date' => $op->delivery_date,
            'pickup_date'   => $op->pickup_date,
        ];
    }
}
