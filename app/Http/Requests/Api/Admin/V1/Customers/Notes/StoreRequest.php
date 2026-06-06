<?php

namespace App\Http\Requests\Api\Admin\V1\Customers\Notes;

use App\Http\Requests\ApiBaseFormRequest;

class StoreRequest extends ApiBaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'customer_unique_id' => 'required|string|exists:customers,unique_id',
            'note' => 'required|string|max:1000',
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
            'customer_unique_id' => [
                'description' => 'The unique_id of the customer.',
                'example' => 'CUS-00001',
                'type' => 'string',
            ],
            'note' => [
                'description' => 'The note text to save for the customer.',
                'example' => 'Called customer and confirmed delivery preference.',
                'type' => 'string',
            ],
        ];
    }
}
