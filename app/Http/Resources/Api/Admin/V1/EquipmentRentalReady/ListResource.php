<?php

namespace App\Http\Resources\Api\Admin\V1\EquipmentRentalReady;

use App\Helpers\CustomHelper;
use App\Http\Resources\Api\Admin\V1\OrderProducts\ListResource as OrderProductsListResource;
use App\Http\Resources\Api\Admin\V1\ProductCategories\ListResource as ProductCategoriesListResource;
use App\Http\Resources\Api\Admin\V1\Stores\ListResource as StoresListResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Mirrors the fields the web rental-ready screen renders per equipment
     * card (see admin.checklist-management.equipment-management.index).
     * Reuses the same key names/nested resources as
     * App\Http\Resources\Api\Admin\V1\Equipment\ListResource
     * (product_category, equipment_store, order_product, current_status)
     * so both equipment list endpoints share a consistent response shape,
     * plus a handful of rental-ready-only fields (service_status,
     * is_assigned, order, rental_ready_checklist).
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
            'product_category' => new ProductCategoriesListResource($this->whenLoaded('productCategory')),
            'checklist_master_id' => $this->checklist_master_id ?? 0,
            'equipment_hours' => $this->equipment_hours ?? 0,
            'is_tracked' => $this->is_tracked ?? 'No',
            'last_inspection' => $this->last_inspection ?? '',
            // Label string, matching Equipment\ListResource's current_status shape.
            'current_status' => $this->current_status?->label() ?? '',
            'current_status_updated_by' => $this->statusUpdatedByUser?->full_name ?? '',
            'current_status_changed_at' => CustomHelper::formatDateTime($this->current_status_changed_at) ?? '',
            'service_status' => $this->service_status ?? 'empty',
            'is_assigned' => (bool) ($this->is_assigned ?? false),

            'equipment_store' => new StoresListResource($this->whenLoaded('store')),

            'order' => $order ? [
                'id' => $order->id,
                'order_number' => $order->order_number ?? '',
                'customer_name' => $order->customer_name ?? '',
            ] : null,

            'order_product' => new OrderProductsListResource($orderProduct),

            // Same shape/order as the web screen's get-checklist-questions call:
            // {success, questions:[...]} for a fresh template, or
            // {success, existing_data:{questions:[...], counts, general_notes, existingTemplate}}
            // for one with saved answers. Null when the equipment has no checklist assigned.
            'rental_ready_checklist' => $this->rental_ready_checklist ?? null,
        ];
    }
}
