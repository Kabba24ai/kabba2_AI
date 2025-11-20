<?php

namespace App\Http\Requests\Api\Admin\V1\Users;

use App\Http\Requests\ApiBaseFormRequest;

class DeviceTokenRequest extends ApiBaseFormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'device_token' => 'required|string|max:255',
            'fcm_token' => 'nullable|string|max:255',
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
            'device_token' => [
                'description' => 'The device token for push notifications.',
                'example' => 'abc123',
                'type' => 'string',
            ],
            'fcm_token' => [
                'description' => 'The Firebase Cloud Messaging token.',
                'example' => 'def456',
                'type' => 'string',
            ],
        ];
    }
}
