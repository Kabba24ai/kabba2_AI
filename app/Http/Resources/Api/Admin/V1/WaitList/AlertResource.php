<?php

namespace App\Http\Resources\Api\Admin\V1\WaitList;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Wait list match alert payload for the internal mobile app. Internal only —
 * customers never receive these.
 */
class AlertResource extends JsonResource
{
    public function toArray($request): array
    {
        $waitList = $this->waitList;

        return [
            'alert_id'         => $this->id,
            'status'           => $this->status->value,
            'created_at'       => $this->created_at?->toIso8601String(),

            'wait_list_id'     => $this->equipment_wait_list_id,
            'wait_list_status' => $waitList?->status->value,
            'wait_list_age_days' => $waitList?->age_days,
            'priority_override'  => $waitList?->priority_override,

            'customer' => [
                'customer_id' => $waitList?->customer_id,
                'name'        => $waitList?->customer_name,
                'company'     => $waitList?->company_name,
                'phone'       => $waitList?->phone,
                'email'       => $waitList?->email,
            ],

            'match' => [
                'type'                    => $this->match_type->value,
                'type_label'              => $this->match_type->label(),
                'equipment_id'            => $this->equipment_id,
                'equipment_name'          => $this->equipment?->equipment_name,
                'equipment_code'          => $this->equipment?->equipment_id,
                'matched_category_id'     => $this->matched_category_id,
                'matched_category'        => $this->matchedCategory?->title,
                'equipment_status_at_match' => $this->equipment_status_at_match,
                'equipment_current_status'  => $this->equipment?->current_status?->value,
            ],

            'store_preference' => [
                'preference' => $waitList?->store_preference->value,
                'label'      => $waitList?->store_preference->label(),
                'store_id'   => $waitList?->store_id,
                'store_name' => $waitList?->store?->store_name,
            ],

            'reason'         => $waitList?->reason,
            'internal_notes' => $waitList?->internal_notes,

            'requested_equipment' => $waitList?->items->map(fn ($item) => [
                'equipment_id'   => $item->equipment_id,
                'equipment_name' => $item->equipment?->equipment_name,
            ])->values(),

            'acknowledged_by' => $this->acknowledgedBy?->full_name,
            'acknowledged_at' => $this->acknowledged_at?->toIso8601String(),
        ];
    }
}
