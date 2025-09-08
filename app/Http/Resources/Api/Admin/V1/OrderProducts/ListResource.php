<?php

namespace App\Http\Resources\Api\Admin\V1\OrderProducts;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use App\Helpers\CustomHelper;
use App\Http\Resources\Api\Admin\V1\Equipment\ListResource as EquipmentListResource;
use App\Http\Resources\Api\Admin\V1\OrderMedias\ListResource as OrderMediasListResource;
use App\Http\Resources\Api\Admin\V1\Orders\ListResource as OrdersListResource;

class ListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray(Request $request)
    {
        $return = [
            'id' => $this->id ?? 0,
            'unique_id' => $this->unique_id ?? 0,
            'product_name' => $this->product_name ?? '',
            'price' => $this->price ? CustomHelper::formatCurrency($this->price) : '0.00',
            'quantity' => $this->quantity ?? 0,

            'hour_tracking' =>  ($this->hour_tracking == "Yes") ? true : false,
            'hour_rate' => (float) $this->hour_rate ?? 0,
            'allocated_hours' => (float) $this->allocated_hours ?? 0,

            'sub_total' => $this->sub_total ? CustomHelper::formatCurrency($this->sub_total) : '0.00',
            'tax' => $this->tax ? CustomHelper::formatCurrency($this->tax) : '0.00',
            'total' => $this->total ? CustomHelper::formatCurrency($this->total) : '0.00',
            //'product_data' => $this->product_data ?? [],
            'service_method' => $this->service_method ?? '',
            'service_option' => $this->service_option ?? '',
            'distance_type' => $this->distance_type ?? '',
            'distance_range' => $this->distance_range ?? '',

            'delivery_status' => $this->delivery_status ?? '',
            'delivery_transport_mode' => $this->delivery_transport_mode ?? '',
            'delivery_store_id' => $this->delivery_store_id ?? '',
            'delivery_by' => $this->delivery_by ?? '',
            'delivery_date' => CustomHelper::formatDate($this->delivery_date) ?? '',
            'delivery_time' => CustomHelper::formatTime($this->delivery_time) ?? '',

            'delivery_media' => OrderMediasListResource::collection($this->whenLoaded('deliveryMedia') ?? []),

            'delivery_notes' => $this->delivery_notes ?? '',

            'pickup_status' => $this->pickup_status ?? '',
            'pickup_transport_mode' => $this->pickup_transport_mode ?? '',
            'pickup_store_id' => $this->pickup_store_id ?? '',
            'pickup_date' => CustomHelper::formatDate($this->pickup_date) ?? '',
            'pickup_time' => CustomHelper::formatTime($this->pickup_time) ?? '',
            'pickup_by' => $this->pickup_by ?? '',

            'pickup_media' => OrderMediasListResource::collection($this->whenLoaded('pickupMedia') ?? []),

            'pickup_notes' => $this->pickup_notes ?? '',

            'delivery_signature_media_id' => $this->delivery_signature_media_id ?? 0,
            'return_signature_media_id' => $this->pickup_signature_media_id ?? 0,

            'delivery_signature_media_url' => $this->whenLoaded('deliverySignatureMedia')->url ?? '',
            'return_signature_media_url' => $this->whenLoaded('returnSignatureMedia')->url ?? '',

            'is_delivered' => (bool) $this->is_delivered ?? false,
            'is_returned' => (bool) $this->is_returned ?? false,

            'order' => new OrdersListResource($this->whenLoaded('order')),

            'start_hours' => (float) ($this->start_hours ?? 0.0),
            'end_hours' => (float) ($this->end_hours ?? 0.0),
            'total_charge' => (float) ($this->total_charge ?? 0.0),

            'equipment' => new EquipmentListResource($this->whenLoaded('equipment')),

            'equipment_id' => $this->equipment_id ?? 0,
            'equipment_details' => $this->equipment_details ?? '',
            'assigned_by' => $this->equipment_assigned_by ?? '',
            'assigned_at' => CustomHelper::formatDateTime($this->equipment_assigned_at) ?? '',

        ];

        return $return;
    }
}
