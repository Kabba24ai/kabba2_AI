<?php

namespace App\Http\Requests\Api\TimeTracker\V1\Auth;

use App\Http\Requests\ApiBaseFormRequest;

class LoginRequest extends ApiBaseFormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'employee_code' => 'required|string|max:10',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'user_id' => [
                'description' => 'Selected employee user ID',
                'example' => 5,
                'type' => 'integer',
            ],
            'employee_code' => [
                'description' => 'Employee code for authentication',
                'example' => '123456',
                'type' => 'string',
            ],
        ];
    }
}
