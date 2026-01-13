<?php

namespace App\Http\Requests\Api\Admin\V1\UserNotification;

use App\Http\Requests\ApiBaseFormRequest;

class UpdateStatusRequest extends ApiBaseFormRequest
{
    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [
            'user_id'  => 'required|integer|exists:users,id',
            'order_id' => 'required|integer|exists:orders,id',
        ];
    }

    /**
     * API documentation for body parameters
     */
    public function bodyParameters(): array
    {
        return [
            'user_id' => [
                'description' => 'User ID',
                'example' => 1,
                'type' => 'integer',
            ],
            'order_id' => [
                'description' => 'Order ID',
                'example' => 10,
                'type' => 'integer',
            ],
        ];
    }
}
