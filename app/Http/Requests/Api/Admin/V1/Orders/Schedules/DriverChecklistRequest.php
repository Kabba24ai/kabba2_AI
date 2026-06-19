<?php

namespace App\Http\Requests\Api\Admin\V1\Orders\Schedules;

use App\Enums\Orders\EquipmentDriverStatus;
use App\Http\Requests\ApiBaseFormRequest;
use Illuminate\Validation\Rule;

class DriverChecklistRequest extends ApiBaseFormRequest
{
    public function rules(): array
    {
        return [
            'order_product_unique_id'  => 'required|string|max:255|exists:order_products,unique_id',
            'checklist_type'           => ['required', Rule::in(['delivery', 'pickup'])],
            'equipment_fuel'           => 'nullable|string|max:255',
            'equipment_key_location'   => 'nullable|string|max:255',
            'equipment_driver_status'  => ['nullable', Rule::enum(EquipmentDriverStatus::class)],
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
            'checklist_type' => [
                'description' => 'Checklist target type. Use delivery to store delivery fields, pickup to store pickup fields.',
                'example'     => 'delivery',
                'type'        => 'string',
            ],
            'equipment_fuel' => [
                'description' => 'Fuel level of the equipment (e.g. Empty, 1/8 Full, 1/4 Full, Full).',
                'example'     => '1/4 Full',
                'type'        => 'string',
            ],
            'equipment_key_location' => [
                'description' => 'Location of the equipment key (e.g. In Truck Cup Holder, In Truck Storage Bin, In my pocket, Left in machine).',
                'example'     => 'In Truck Cup Holder',
                'type'        => 'string',
            ],
            'equipment_driver_status' => [
                'description' => 'Driver status. Allowed: ' . implode(', ', EquipmentDriverStatus::getValues()),
                'example'     => 'Ready to Go',
                'type'        => 'string',
            ],
        ];
    }
}
