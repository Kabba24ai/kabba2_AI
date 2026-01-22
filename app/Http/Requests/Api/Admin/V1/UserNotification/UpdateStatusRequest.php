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
            'order_id' => 'nullable|integer|exists:orders,id',
        ];
    }

    /**
     * API documentation for body parameters
     */
    public function bodyParameters(): array
    {
        return [

            'order_id' => [
                'description' => 'Order ID',
                'example' => 10,
                'type' => 'integer',
            ],
        ];
    }
}
