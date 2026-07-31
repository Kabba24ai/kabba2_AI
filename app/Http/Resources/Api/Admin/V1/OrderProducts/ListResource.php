<?php

namespace App\Http\Resources\Api\Admin\V1\OrderProducts;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use App\Helpers\CustomHelper;
use App\Http\Resources\Api\Admin\V1\Equipment\ListResource as EquipmentListResource;
use App\Http\Resources\Api\Admin\V1\OrderMedias\ListResource as OrderMediasListResource;
use App\Http\Resources\Api\Admin\V1\Orders\ListResource as OrdersListResource;
use App\Http\Resources\Api\Admin\V1\Stores\ListResource as StoresListResource;
use App\Http\Resources\Api\Admin\V1\CustomerChecklistQuestions\ListResource as CustomerChecklistQuestionsListResource;
use App\Http\Resources\Api\Admin\V1\RentalReadyChecklistQuestions\ListResource as RentalReadyChecklistQuestionsListResource;
use App\Http\Resources\Api\Admin\V1\Users\ListResource as UsersListResource;

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

            'hour_tracking' => $this->hour_tracking == 'Yes' ? true : false,
            'hour_rate' => (float) $this->hour_rate ?? 0,
            'allocated_hours' => (float) $this->allocated_hours ?? 0,

            'sub_total' => $this->sub_total ? CustomHelper::formatCurrency($this->sub_total) : '0.00',
            'tax' => $this->tax ? CustomHelper::formatCurrency($this->tax) : '0.00',
            'total' => $this->total ? CustomHelper::formatCurrency($this->total) : '0.00',
            'product_data' => $this->transformProductData($this->product_data ?? []),
            'is_product_clean' => (bool) $this->is_product_clean,
            'rental_prepaid_cleaning' => (float) ($this->rental_prepaid_cleaning ?? 0),
            'service_method' => $this->service_method ?? '',
            'service_option' => $this->service_option ?? '',
            'distance_type' => $this->distance_type ?? '',
            'distance_range' => $this->distance_range ?? '',

            'delivery_status' => $this->delivery_status ?? '',
            'delivery_transport_mode' => $this->delivery_transport_mode ?? '',
            'delivery_store_id' => $this->delivery_store_id ?? '',
            'delivery_by' => $this->delivery_by ?? '',
            'delivery_priority' => $this->delivery_priority,
            'delivery_date' => CustomHelper::formatDate($this->delivery_date) ?? '',
            'delivery_time' => CustomHelper::formatTime($this->delivery_time) ?? '',
            'dispatch_delivery_date' => CustomHelper::formatDate($this->dispatch_delivery_date) ?? '',
            'is_delivery_overdue' => (bool) (
                ($this->dispatch_delivery_date ?? $this->delivery_date)
                && Carbon::parse($this->dispatch_delivery_date ?? $this->delivery_date)->lt(Carbon::today())
            ),
            'is_early' => (bool) (
                $this->dispatch_delivery_date && $this->delivery_date
                && Carbon::parse($this->dispatch_delivery_date)->lt(Carbon::parse($this->delivery_date))
            ),

            'delivery_media' => OrderMediasListResource::collection($this->whenLoaded('deliveryMedia') ?? []),

            'delivery_notes' => $this->delivery_notes ?? '',
            'delivery_checklist' => [
                'equipment_fuel'          => $this->delivery_equipment_fuel,
                'equipment_key_location'  => $this->delivery_equipment_key_location,
                'equipment_driver_status' => $this->delivery_equipment_driver_status?->value ?? $this->delivery_equipment_driver_status,
                'ready_to_go_at'          => $this->delivery_ready_to_go_at?->toDateTimeString(),
                'arrived_at'              => $this->delivery_arrived_at?->toDateTimeString(),
                'is_delivered'            => (bool) $this->delivery_is_delivered,
                'is_arrived'              => (bool) $this->delivery_is_arrived,
            ],

            'pickup_status' => $this->pickup_status ?? '',
            'pickup_transport_mode' => $this->pickup_transport_mode ?? '',
            'pickup_store_id' => $this->pickup_store_id ?? '',
            'pickup_date' => CustomHelper::formatDate($this->pickup_date) ?? '',
            'pickup_time' => CustomHelper::formatTime($this->pickup_time) ?? '',
            'pickup_by' => $this->pickup_by ?? '',
            'pickup_priority' => $this->pickup_priority,
            'dispatch_return_date' => CustomHelper::formatDate($this->dispatch_return_date) ?? '',
            'is_pickup_overdue' => (bool) (
                ($this->dispatch_return_date ?? $this->pickup_date)
                && Carbon::parse($this->dispatch_return_date ?? $this->pickup_date)->lt(Carbon::today())
            ),
            'is_late_pickup' => (bool) (
                $this->dispatch_return_date && $this->pickup_date
                && Carbon::parse($this->dispatch_return_date)->gt(Carbon::parse($this->pickup_date))
            ),

            'pickup_media' => OrderMediasListResource::collection($this->whenLoaded('pickupMedia') ?? []),

            'pickup_notes' => $this->pickup_notes ?? '',
            'pickup_checklist' => [
                'equipment_fuel'          => $this->pickup_equipment_fuel,
                'equipment_key_location'  => $this->pickup_equipment_key_location,
                'equipment_driver_status' => $this->pickup_equipment_driver_status?->value ?? $this->pickup_equipment_driver_status,
                'ready_to_go_at'          => $this->pickup_ready_to_go_at?->toDateTimeString(),
                'arrived_at'              => $this->pickup_arrived_at?->toDateTimeString(),
                'is_delivered'            => (bool) $this->pickup_is_delivered,
                'is_arrived'              => (bool) $this->pickup_is_arrived,
            ],

            'delivery_signature_media_id' => $this->delivery_signature_media_id ?? 0,
            'return_signature_media_id' => $this->pickup_signature_media_id ?? 0,

            'delivery_signature_media_url' => $this->whenLoaded('deliverySignatureMedia')->url ?? '',
            'return_signature_media_url' => $this->whenLoaded('returnSignatureMedia')->url ?? '',

            'is_delivered' => (bool) $this->is_delivered ?? false,
            'is_returned' => (bool) $this->is_returned ?? false,

            'order' => new OrdersListResource($this->whenLoaded('order')),

            'start_hours' => (float) ($this->start_hours ?? 0.0),
            'end_hours' => (float) ($this->end_hours ?? 0.0),
            'fuel_initial_reading' => $this->fuel_initial_reading ?? '',
            'fuel_final_reading' => $this->fuel_final_reading ?? '',

            'fuel_total_charge' => (float) ($this->fuel_total_charge ?? 0.0),

            'total_charge' => (float) ($this->total_charge ?? 0.0),

            'equipment' => new EquipmentListResource($this->whenLoaded('equipment')),

            'equipment_id' => $this->equipment_id ?? 0,

            'equipment_details' => $this->equipment_details ?? (new EquipmentListResource($this->whenLoaded('softEquipment')) ?? ''),

            'assigned_by' => $this->equipment_assigned_by ?? '',

            'assigned_at' => CustomHelper::formatDateTime($this->equipment_assigned_at) ?? '',

            'delivery_store' => new StoresListResource($this->whenLoaded('deliveryStore')),

            'pickup_store' => new StoresListResource($this->whenLoaded('pickupStore')),

            'delivery_employee' => new UsersListResource($this->whenLoaded('deliveryEmployee')),

            'pickup_employee' => new UsersListResource($this->whenLoaded('pickupEmployee')),

            'equipment_location' => $this->equipmentLocation() ?? '', // due to many relationships add last so above keys whenLoaded not load other data

            'customer_checklist_questions' => CustomerChecklistQuestionsListResource::collection($this->whenLoaded('checklistQA')),

            'rental_ready_checklist_questions' => RentalReadyChecklistQuestionsListResource::collection($this->whenLoaded('rentalReadyQA')),
        ];

        return $return;
    }

    private function transformProductData($productData)
    {
        if (!isset($productData['product_rental_items_prices'])) {
            return $productData;
        }

        $newData = [];

        foreach ($productData['product_rental_items_prices'] as $key => $value) {
            $case = collect(\App\Enums\Products\ProductCustomStaticLabel::cases(),)->firstWhere('name', $key)?->value;

            $label = $case ?? ucwords(str_replace('_', ' ', preg_replace('/^rental_/', '', $key)));

            $newData[] = [
                'label' => $label,
                'value' => $value
            ];
        }

        // ✅ Add new key (now as array of objects)
        $productData['product_rental_items_prices_with_labels'] = $newData;

        return $productData;
    }
}
