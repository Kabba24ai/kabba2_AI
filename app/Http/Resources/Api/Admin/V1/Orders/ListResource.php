<?php

namespace App\Http\Resources\Api\Admin\V1\Orders;

use App\Helpers\CustomHelper;
use App\Http\Resources\Api\Admin\V1\OrderAddresses\ListResource as OrderAddressesListResource;
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
            'company_name' => $this->company_name ?? '',
            'company_website' => $this->company_website ?? '',
            'status' => $this->last_payment_status ?? '',
            'order_date' => CustomHelper::formatDate($this->order_date) ?? '',

            'subtotal' => CustomHelper::formatCurrency($this->subtotal) ?? '',
            'is_tax_exempt' => $this->is_tax_exempt ?? false,
            'tax_amount' => CustomHelper::formatCurrency($this->tax_amount) ?? '',
            'coupon_code' => $this->coupon_code ?? '',
            'discount_amount' => CustomHelper::formatCurrency($this->discount_amount) ?? '',

            'amount' => CustomHelper::formatCurrency($this->grand_total) ?? '',
            'platform' => $this->platform ?? '',
            'payment_type' => $this->last_payment_type ?? '',
            'payment_status' => $this->last_payment_status ?? '',
            'delivery_address' => new OrderAddressesListResource($this->shippingAddress ?? []) ?? [],
            'billing_address' => new OrderAddressesListResource($this->billingAddress ?? []) ?? [],

            'license' => OrderMediasListResource::collection($this->licenseMedia ?? []),
            'order_products' => OrderProductsListResource::collection($this->products) ?? [],
            'order_notes' => OrderNotesListResource::collection($this->notes) ?? [],

            'terms_status' => $this->terms_status ?? '',
            'terms_page' => route('front.terms-and-conditions.index', $this->unique_id) ?? '',

            'terms_accepted_at' => CustomHelper::formatDateTime($this->terms_accepted_at) ?? '',

        ];

        return $return;
    }
}
