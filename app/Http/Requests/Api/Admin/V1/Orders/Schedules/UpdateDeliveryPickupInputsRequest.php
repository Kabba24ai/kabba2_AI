<?php

namespace App\Http\Requests\Api\Admin\V1\Orders\Schedules;

use App\Http\Requests\ApiBaseFormRequest;

class UpdateDeliveryPickupInputsRequest extends ApiBaseFormRequest
{
    public function rules(): array
    {
        return [
            'order_product_unique_id'          => 'required|string|max:255|exists:order_products,unique_id',

            'delivery_inputs_date'             => 'nullable|date',
            'delivery_tnc_status'              => 'nullable|string|max:255',
            'delivery_drivers_license_status'  => 'nullable|string|max:255',
            'delivery_video_status'            => 'nullable|string|max:255',
            'delivery_checklist_status'        => 'nullable|string|max:255',

            'pickup_inputs_date'               => 'nullable|date',
            'pickup_tnc_status'                => 'nullable|string|max:255',
            'pickup_drivers_license_status'    => 'nullable|string|max:255',
            'pickup_video_status'              => 'nullable|string|max:255',
            'pickup_checklist_status'          => 'nullable|string|max:255',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'order_product_unique_id' => [
                'description' => 'The unique ID of the order product.',
                'example'     => 'ORD-SCH-URXP-LX5R',
                'type'        => 'string',
            ],
            'delivery_inputs_date' => [
                'description' => 'The datetime when delivery inputs were recorded.',
                'example'     => '2026-06-24 10:30:00',
                'type'        => 'string',
            ],
            'delivery_tnc_status' => [
                'description' => 'Delivery terms and conditions status.',
                'example'     => 'accepted',
                'type'        => 'string',
            ],
            'delivery_drivers_license_status' => [
                'description' => 'Delivery driver\'s license verification status.',
                'example'     => 'verified',
                'type'        => 'string',
            ],
            'delivery_video_status' => [
                'description' => 'Delivery video status.',
                'example'     => 'completed',
                'type'        => 'string',
            ],
            'delivery_checklist_status' => [
                'description' => 'Delivery checklist completion status.',
                'example'     => 'completed',
                'type'        => 'string',
            ],
            'pickup_inputs_date' => [
                'description' => 'The datetime when pickup inputs were recorded.',
                'example'     => '2026-06-25 14:00:00',
                'type'        => 'string',
            ],
            'pickup_tnc_status' => [
                'description' => 'Pickup terms and conditions status.',
                'example'     => 'accepted',
                'type'        => 'string',
            ],
            'pickup_drivers_license_status' => [
                'description' => 'Pickup driver\'s license verification status.',
                'example'     => 'verified',
                'type'        => 'string',
            ],
            'pickup_video_status' => [
                'description' => 'Pickup video status.',
                'example'     => 'completed',
                'type'        => 'string',
            ],
            'pickup_checklist_status' => [
                'description' => 'Pickup checklist completion status.',
                'example'     => 'completed',
                'type'        => 'string',
            ],
        ];
    }
}
