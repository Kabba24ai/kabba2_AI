<?php

namespace App\Http\Requests\Api\Admin\V1\Equipment;

use App\Enums\Equipments\EquipmentCurrentStatus;
use App\Http\Requests\ApiBaseFormRequest;

class IndexRequest extends ApiBaseFormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'status' => 'nullable|string|in:' . implode(',', EquipmentCurrentStatus::getValues()),
        ];
    }

    /**
     * Get the body parameters for the request documentation.
     *
     * @return array
     */
    public function bodyParameters(): array
    {
        return [
            'status' => [
                'description' => 'The status to filter equipment.',
                'example' => 'available',
                'type' => 'string',
                'enum' => EquipmentCurrentStatus::getValues(),
            ],
        ];
    }
}
