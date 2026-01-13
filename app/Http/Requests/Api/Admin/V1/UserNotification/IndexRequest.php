<?php

namespace App\Http\Requests\Api\Admin\V1\UserNotification;

use App\Http\Requests\ApiBaseFormRequest;

class IndexRequest extends ApiBaseFormRequest
{
    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [
            'user_id' => 'nullable|integer|exists:users,id',
            'order_id' => 'nullable|integer|exists:orders,id',
            'status' => 'nullable|string|in:read,unread',
            'per_page' => 'nullable|integer|min:1',
            'page' => 'nullable|integer|min:1',
        ];
    }


   /**
 * API documentation for query parameters
 */
public function queryParameters(): array
{
    return [
        'user_id' => [
            'description' => 'Filter notifications by user ID',
            'example' => 1,
            'type' => 'integer',
        ],
        'order_id' => [
            'description' => 'Filter notifications by order ID',
            'example' => 5,
            'type' => 'integer',
        ],
        'status' => [
            'description' => 'Filter notifications by status',
            'example' => 'unread',
            'type' => 'string',
        ],
        'per_page' => [
            'description' => 'Number of items per page',
            'example' => 10,
            'type' => 'integer',
        ],
        'page' => [
            'description' => 'Page number for pagination',
            'example' => 1,
            'type' => 'integer',
        ],
    ];
}

}
