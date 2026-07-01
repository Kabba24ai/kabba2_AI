<?php

namespace App\Http\Requests\Api\TimeTracker\V1\MasterPassword;

use App\Http\Requests\ApiBaseFormRequest;

class VerifyRequest extends ApiBaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'master_password' => ['required', 'string'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'master_password' => [
                'description' => 'The master password to unlock editing of old pay periods',
                'example' => '********',
                'type' => 'string',
            ],
        ];
    }
}
