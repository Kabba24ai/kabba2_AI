<?php

namespace App\Http\Requests\Api\Admin\V1\Customers\Tags;

use App\Http\Requests\ApiBaseFormRequest;

class AssignTagsRequest extends ApiBaseFormRequest
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
            'tag_ids'   => 'required|array|min:1',
            'tag_ids.*' => 'required|integer|exists:tags,id',
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
                'description' => 'The unique_id of the customer to which the tags will be assigned.',
                'example'     => 'CUS-00001',
                'type'        => 'string',
            ],
            'tag_ids' => [
                'description' => 'Array of tag IDs to assign to the customer.',
                'example'     => [1, 2, 3],
                'type'        => 'array',
            ],
            'tag_ids.*' => [
                'description' => 'A valid tag ID.',
                'example'     => 1,
                'type'        => 'integer',
            ],
        ];
    }
}
