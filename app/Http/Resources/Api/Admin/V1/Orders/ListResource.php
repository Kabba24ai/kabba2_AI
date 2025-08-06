<?php

namespace App\Http\Resources\Api\Admin\V1\Orders;

use App\Helpers\CustomHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use App\Http\Resources\Api\Admin\V1\OrderMedias\ListResource as OrderMediasListResource;
use App\Http\Resources\Api\Admin\V1\OrderNotes\ListResource as OrderNotesListResource;
use App\Http\Resources\Api\Admin\V1\OrderProducts\ListResource as OrderProductsListResource;

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
            'order_number' => $this->order_number ?? '',
            'customer_name' => $this->customer_name ?? '',
            'customer_email' => $this->customer_email ?? '',
            'customer_phone' => $this->customer_phone ?? '',
            'status' => $this->last_payment_status ?? '',
            'order_date' => CustomHelper::formatDate($this->order_date) ?? '',
            'amount' => CustomHelper::formatCurrency($this->grand_total) ?? '',
            'platform' => $this->platform ?? '',
            'payment_type' => $this->last_payment_type ?? '',
            'payment_status' => $this->last_payment_status ?? '',
            'delivery_address' => $this->shippingAddress->address ?? '',
            'billing_address' => $this->billingAddress->address ?? '',

            'license' => OrderMediasListResource::collection($this->licenseMedia ?? []),
            'order_products' => OrderProductsListResource::collection($this->products) ?? [],
            'order_notes' => OrderNotesListResource::collection($this->notes) ?? [],

        ];

        return $return;
    }
}
