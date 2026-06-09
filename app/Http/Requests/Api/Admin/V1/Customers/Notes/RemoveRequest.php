<?php

namespace App\Http\Requests\Api\Admin\V1\Customers\Notes;

use App\Http\Requests\ApiBaseFormRequest;

class RemoveRequest extends ApiBaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'customer_note_unique_id' => 'required|string|exists:customer_notes,unique_id',
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
                'description' => 'The unique_id of the customer note.',
                'example' => 'CUS-NOTE-00001',
                'type' => 'string',
            ],
        ];
    }
}
