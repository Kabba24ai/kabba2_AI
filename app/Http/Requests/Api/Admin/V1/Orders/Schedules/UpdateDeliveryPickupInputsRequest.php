<?php

namespace App\Http\Requests\Api\Admin\V1\Orders\Schedules;

use App\Enums\Orders\ChecklistInputStatus;
use App\Enums\Orders\DriversLicenseStatus;
use App\Enums\Orders\OrderTermsStatus;
use App\Enums\Orders\VideoInputStatus;
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
            'tnc_status'              => ['nullable'],
            'drivers_license_status'  => ['nullable'],
            'video_status'            => ['nullable'],
            'checklist_status'        => ['nullable'],
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
                'description' => 'Terms and conditions status. Allowed: ',
                'example'     => 'Accepted',
                'type'        => 'string',
            ],
            'drivers_license_status' => [
                'description' => 'Driver\'s license verification status. Allowed: ',
                'example'     => 'Verified',
                'type'        => 'string',
            ],
            'video_status' => [
                'description' => 'Video status. Allowed: ',
                'example'     => 'Completed',
                'type'        => 'string',
            ],
            'checklist_status' => [
                'description' => 'Checklist completion status. Allowed: ',
                'example'     => 'Completed',
                'type'        => 'string',
            ],
        ];
    }
}
