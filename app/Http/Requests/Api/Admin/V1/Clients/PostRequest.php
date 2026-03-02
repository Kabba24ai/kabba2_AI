<?php

namespace App\Http\Requests\Api\Admin\V1\Clients;

use App\Http\Requests\ApiBaseFormRequest;

class PostRequest extends ApiBaseFormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'code' => 'required|string|max:10|exists:clients,code',
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
            'code' => [
                'description' => 'The unique code of the client.',
                'example' => 'CL123',
                'type' => 'string',
            ],
        ];
    }
}
