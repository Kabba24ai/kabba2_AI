<?php

namespace App\Http\Resources\Api\Admin\V1\OrderProducts;

use App\Helpers\CustomHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'sub_total' => $this->sub_total ? CustomHelper::formatCurrency($this->sub_total) : '0.00',
            'tax' => $this->tax ? CustomHelper::formatCurrency($this->tax) : '0.00',
            'total' => $this->total ? CustomHelper::formatCurrency($this->total) : '0.00',
            'product_data' => $this->product_data ?? [],
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

            'pickup_status' => $this->pickup_status ?? '',
            'pickup_transport_mode' => $this->pickup_transport_mode ?? '',
            'pickup_store_id' => $this->pickup_store_id ?? '',
            'pickup_date' => CustomHelper::formatDate($this->pickup_date) ?? '',
            'pickup_time' => CustomHelper::formatTime($this->pickup_time) ?? '',
            'pickup_by' => $this->pickup_by ?? '',
        ];

        return $return;
    }
}
