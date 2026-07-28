<?php

namespace App\Http\Resources\Api\Admin\V1\EquipmentRentalReady;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Mirrors the fields the web rental-ready screen renders per equipment
     * card (see admin.checklist-management.equipment-management.index),
     * kept flat/lightweight for the admin app list.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray(Request $request)
    {
        $orderProduct = $this->orderProduct ?? $this->softAssignments?->first()?->orderProduct;
        $order = $this->order;

        return [
            'id' => $this->id ?? 0,
            'unique_id' => $this->unique_id ?? '',
            'equipment_name' => $this->equipment_name ?? '',
            'model' => $this->model ?? '',
            'serial_number' => $this->serial_number ?? '',
            'equipment_id' => $this->equipment_id ?? 0,
            'product_category_id' => $this->product_category_id ?? 0,
            'category_name' => $this->category_name ?? 'N/A',
            'checklist_master_id' => $this->checklist_master_id ?? 0,
            'equipment_hours' => $this->equipment_hours ?? 0,
            'is_tracked' => $this->is_tracked ?? 'No',
            'last_inspection' => $this->last_inspection ?? '',
            'current_status' => $this->current_status?->value ?? '',
            'status_label' => $this->status_label ?? '',
            'service_status' => $this->service_status ?? 'empty',
            'is_assigned' => (bool) ($this->is_assigned ?? false),

            'store' => $this->whenLoaded('store', fn() => [
                'id' => $this->store->id,
                'store_name' => $this->store->store_name,
            ]),

            'order' => $order ? [
                'id' => $order->id,
                'order_number' => $order->order_number ?? '',
                'customer_name' => $order->customer_name ?? '',
            ] : null,

            'order_product' => $orderProduct ? [
                'id' => $orderProduct->id,
                'product_name' => $orderProduct->product_name ?? '',
            ] : null,

            // Same shape/order as the web screen's get-checklist-questions call:
            // {success, questions:[...]} for a fresh template, or
            // {success, existing_data:{questions:[...], counts, general_notes, existingTemplate}}
            // for one with saved answers. Null when the equipment has no checklist assigned.
            'rental_ready_checklist' => $this->rental_ready_checklist ?? null,
        ];
    }
}
