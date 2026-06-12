<?php

namespace App\Http\Resources\Api\Admin\V1\Dispatch;

use App\Helpers\CustomHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $order    = $this->order;
        $customer = $order?->customer;
        $addr     = $order?->shippingAddress;

        $isHardAssigned = (bool) $this->equipment?->current_status?->isRented();
        $equipmentName  = $isHardAssigned
            ? ($this->equipment_details['equipment_name'] ?? null)
            : ($this->softAssignment?->equipment?->equipment_name ?? null);
        $equipmentId    = $isHardAssigned
            ? ($this->equipment_details['equipment_id'] ?? null)
            : ($this->softAssignment?->equipment?->equipment_id ?? null);

        $categories = $this->product?->categories ?? collect();

        return [
            'id'        => $this->id ?? 0,
            'unique_id' => $this->unique_id ?? '',

            'product' => [
                'name'       => $this->product_name ?? '',
                'categories' => $categories->map(fn($c) => [
                    'id'    => $c->id,
                    'title' => $c->title,
                ])->values(),
            ],

            'order' => [
                'id'                     => $order?->id ?? 0,
                'unique_id'              => $order?->unique_id ?? '',
                'order_number'           => $order?->order_number ?? '',
                'reference_order_number' => $order?->reference_order_number ?? '',
                'company_name'           => $order?->company_name ?? '',
                'has_notes'              => $order?->notes?->isNotEmpty() ?? false,
            ],

            'customer' => [
                'name'            => $order?->customer_name ?? '',
                'company_name'    => $customer?->company_name ?? '',
                'company_website' => $customer?->company_website ?? '',
            ],

            'address' => [
                'full_address' => $addr?->full_address ?? '',
                'phone'        => $addr?->phone ?? '',
            ],

            'equipment' => [
                'name'             => $equipmentName ?? '',
                'equipment_id'     => $equipmentId ?? '',
                'is_hard_assigned' => $isHardAssigned,
            ],

            'delivery' => [
                'date'           => $this->delivery_date ? CustomHelper::formatDate($this->delivery_date, 'M d, y') : '',
                'date_raw'       => $this->delivery_date ?? '',
                'time'           => $this->delivery_time ? CustomHelper::formatTime($this->delivery_time) : '',
                'status'         => $this->delivery_status ?? '',
                'transport_mode' => $this->delivery_transport_mode ?? '',
                'priority'       => $this->delivery_priority,
                'store_name'     => $this->deliveryStore?->store_name ?? '',
                'driver'         => $this->deliveryEmployee ? [
                    'id'   => $this->deliveryEmployee->id,
                    'name' => $this->deliveryEmployee->full_name,
                ] : null,
            ],

            'return' => [
                'date'           => $this->pickup_date ? CustomHelper::formatDate($this->pickup_date, 'M d, y') : '',
                'date_raw'       => $this->pickup_date ?? '',
                'time'           => $this->pickup_time ? CustomHelper::formatTime($this->pickup_time) : '',
                'status'         => $this->pickup_status ?? '',
                'transport_mode' => $this->pickup_transport_mode ?? '',
                'priority'       => $this->pickup_priority,
                'store_name'     => $this->pickupStore?->store_name ?? '',
                'driver'         => $this->pickupEmployee ? [
                    'id'   => $this->pickupEmployee->id,
                    'name' => $this->pickupEmployee->full_name,
                ] : null,
            ],

            'fully_completed' => $this->delivery_status === 'Completed' && $this->pickup_status === 'Completed',
        ];
    }
}
