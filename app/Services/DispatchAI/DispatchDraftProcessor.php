<?php

namespace App\Services\DispatchAI;

use App\Models\Dispatch\DispatchAiDraft;
use App\Models\Dispatch\DispatchAiDraftAssignment;
use App\Models\Orders\OrderProduct;
use Carbon\Carbon;

class DispatchDraftProcessor
{
    public function save(array $aiResult, int $lookAheadDays, int $triggeredByUserId, string $triggeredBy): DispatchAiDraft
    {
        $draft = DispatchAiDraft::create([
            'draft_date'           => Carbon::today()->toDateString(),
            'look_ahead_days'      => $lookAheadDays,
            'ai_model'             => $aiResult['model'] ?? null,
            'prompt_tokens'        => $aiResult['prompt_tokens'] ?? null,
            'completion_tokens'    => $aiResult['completion_tokens'] ?? null,
            'response_time_ms'     => $aiResult['response_time_ms'] ?? null,
            'ai_reasoning'         => $aiResult['overall_reasoning'] ?? null,
            'confidence_score'     => $aiResult['confidence_score'] ?? null,
            'ai_metadata'          => array_filter([
                'delivery_priority_summary'      => $aiResult['delivery_priority_summary'] ?? null,
                'pickup_decisions'               => $aiResult['pickup_decisions'] ?? [],
                'driver_assignments'             => $aiResult['driver_assignments'] ?? [],
                'early_delivery_recommendations' => $aiResult['early_delivery_recommendations'] ?? [],
                'manager_review_items'           => $aiResult['manager_review_items'] ?? [],
                'warnings'                       => $aiResult['warnings'] ?? [],
            ]),
            'triggered_by'         => $triggeredBy,
            'triggered_by_user_id' => $triggeredByUserId,
        ]);

        foreach ($aiResult['assignments'] as $item) {
            // Validate order_product_id exists and is not locked for the relevant field
            $op = OrderProduct::find($item['order_product_id']);
            if (!$op) {
                continue;
            }

            $isDelivery    = $item['slot'] === 'delivery';
            $driverLocked  = $isDelivery ? $op->delivery_driver_locked : $op->pickup_driver_locked;

            // If driver is locked, respect existing assignment
            $driverId = $driverLocked
                ? ($isDelivery ? $op->delivery_by : $op->pickup_by)
                : ($item['recommended_driver_id'] ?? null);

            $priorityLocked = $isDelivery ? $op->delivery_priority_locked : $op->pickup_priority_locked;
            $priority = $priorityLocked
                ? ($isDelivery ? $op->delivery_priority : $op->pickup_priority)
                : ($item['recommended_priority'] ?? null);

            DispatchAiDraftAssignment::updateOrCreate(
                [
                    'draft_id'         => $draft->id,
                    'order_product_id' => $op->id,
                    'slot'             => $item['slot'],
                ],
                [
                    'recommended_driver_id'   => $driverId,
                    'recommended_truck_id'    => $item['recommended_truck_id'] ?? null,
                    'recommended_trailer_id'  => $item['recommended_trailer_id'] ?? null,
                    'recommended_priority'    => $priority,
                    'is_early_delivery'       => (bool) ($item['is_early_delivery'] ?? false),
                    'suggested_delivery_date' => $item['suggested_delivery_date'] ?? null,
                    'ai_reasoning'            => $item['ai_reasoning'] ?? null,
                    'was_applied'             => false,
                ],
            );
        }

        return $draft->load('assignments');
    }
}
