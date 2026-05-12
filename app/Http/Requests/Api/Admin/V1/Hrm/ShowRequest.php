<?php

namespace App\Http\Requests\Api\Admin\V1\Hrm;

use App\Http\Requests\ApiBaseFormRequest;

class ShowRequest extends ApiBaseFormRequest
{
    /**
     * Validation Rules
     */
    public function rules(): array
    {
        return [

            'user_id' => 'required|exists:users,id',

        ];
    }

    /**
     * API Documentation Parameters
     */
    public function bodyParameters(): array
    {
        return [

            'user_id' => [
                'description' => 'HRM User ID',
                'example' => 1,
                'type' => 'integer',
            ],

        ];
    }
}