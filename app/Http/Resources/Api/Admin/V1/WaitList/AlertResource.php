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
                'matched_product_id'      => $this->matched_product_id,
                'matched_product'         => $this->matchedProduct?->product_name,
                'equipment_status_at_match' => $this->equipment_status_at_match,
                'equipment_current_status'  => $this->equipment?->current_status?->value,
                'power_source_type'         => $this->equipment?->power_source_type,
                'key_starting_mechanism'    => $this->equipment?->key_starting_mechanism,
                'is_fuel'                   => $this->equipment?->hasFuelData() ?? false,
                'is_key'                    => $this->equipment?->hasKeyData() ?? false,
            ],

            'disposition'       => $this->disposition?->value,
            'disposition_label' => $this->disposition?->label(),

            'store_preference' => [
                'preference' => $waitList?->store_preference->value,
                'label'      => $waitList?->store_preference->label(),
                'store_id'   => $waitList?->store_id,
                'store_name' => $waitList?->store?->store_name,
            ],

            'reason'         => $waitList?->reason,
            'internal_notes' => $waitList?->internal_notes,

            'selected_products' => $waitList?->selectedProducts->map(fn ($product) => [
                'product_id'   => $product->id,
                'product_name' => $product->product_name,
            ])->values(),

            'requested_equipment' => $waitList?->items->map(fn ($item) => [
                'equipment_id'           => $item->equipment_id,
                'equipment_name'         => $item->equipment?->equipment_name,
                'power_source_type'      => $item->equipment?->power_source_type,
                'key_starting_mechanism' => $item->equipment?->key_starting_mechanism,
                'is_fuel'                => $item->equipment?->hasFuelData() ?? false,
                'is_key'                 => $item->equipment?->hasKeyData() ?? false,
            ])->values(),

            'acknowledged_by' => $this->acknowledgedBy?->full_name,
            'acknowledged_at' => $this->acknowledged_at?->toIso8601String(),
        ];
    }
}
