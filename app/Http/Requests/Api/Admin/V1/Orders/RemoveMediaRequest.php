<?php

namespace App\Http\Requests\Api\Admin\V1\Orders;

use App\Http\Requests\ApiBaseFormRequest;

class RemoveMediaRequest extends ApiBaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'customer_note_unique_id' => 'required|string|exists:customer_notes,unique_id', // Required unique ID parameter
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
            'customer_note_unique_id' => [
                'description' => 'The unique ID of the customer note.',
                'example' => 'CUST-NOTE-6NO6-KQY3',
                'type' => 'string',
            ],
        ];
    }
}
