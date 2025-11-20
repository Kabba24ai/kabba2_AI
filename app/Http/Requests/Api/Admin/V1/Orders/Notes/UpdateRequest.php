<?php

namespace App\Http\Requests\Api\Admin\V1\Orders\Notes;

use App\Http\Requests\ApiBaseFormRequest;

class UpdateRequest extends ApiBaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'order_note_unique_id' => 'required|string|exists:order_notes,unique_id', // Required unique ID parameter
            'note' => 'required|string|max:1000', // Required note content
            'user_id' => 'required|integer|exists:users,id', // Required user
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
            'order_note_unique_id' => [
                'description' => 'The unique ID of the order note.',
                'example' => 'ORD-NOTE-IZTO-LSK5',
                'type' => 'string',
            ],
            'note' => [
                'description' => 'The content of the note to be added to the order.',
                'example' => 'This is a note for the order.',
                'type' => 'string',
            ],
            'user_id' => [
                'description' => 'The ID of the user adding the note.',
                'example' => 1,
                'type' => 'integer',
            ],
        ];
    }
}
