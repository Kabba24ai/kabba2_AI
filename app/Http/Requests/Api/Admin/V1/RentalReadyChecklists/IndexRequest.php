<?php

namespace App\Http\Requests\Api\Admin\V1\RentalReadyChecklists;

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
            'equipment_unique_id' => 'required|exists:equipment,unique_id',
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
            'equipment_unique_id' => [
                'description' => 'The unique ID of the equipment.',
                'example' => 'EQP-VL04-IVRC',
                'type' => 'string',
            ],
        ];
    }
}
