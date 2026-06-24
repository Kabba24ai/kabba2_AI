<?php

namespace App\Http\Requests\Api\Admin\V1\Orders\Schedules;

use App\Http\Requests\ApiBaseFormRequest;
use Illuminate\Validation\Rule;

class UpdateDeliveryPickupInputsRequest extends ApiBaseFormRequest
{
    public function rules(): array
    {
        return [
            'order_product_unique_id' => 'required|string|max:255|exists:order_products,unique_id',
            'type'                    => ['required', Rule::in(['delivery', 'pickup'])],

            'inputs_date'             => 'nullable|date',
            'tnc_status'              => 'nullable|string|max:255',
            'drivers_license_status'  => 'nullable|string|max:255',
            'video_status'            => 'nullable|string|max:255',
            'checklist_status'        => 'nullable|string|max:255',
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
            'type' => [
                'description' => 'Whether to update delivery or pickup fields. Allowed: delivery, pickup.',
                'example'     => 'delivery',
                'type'        => 'string',
            ],
            'inputs_date' => [
                'description' => 'The datetime when inputs were recorded.',
                'example'     => '2026-06-24 10:30:00',
                'type'        => 'string',
            ],
            'tnc_status' => [
                'description' => 'Terms and conditions status.',
                'example'     => 'accepted',
                'type'        => 'string',
            ],
            'drivers_license_status' => [
                'description' => 'Driver\'s license verification status.',
                'example'     => 'verified',
                'type'        => 'string',
            ],
            'video_status' => [
                'description' => 'Video status.',
                'example'     => 'completed',
                'type'        => 'string',
            ],
            'checklist_status' => [
                'description' => 'Checklist completion status.',
                'example'     => 'completed',
                'type'        => 'string',
            ],
        ];
    }
}
