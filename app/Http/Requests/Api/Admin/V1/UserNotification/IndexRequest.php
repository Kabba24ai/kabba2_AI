<?php

namespace App\Http\Requests\Api\Admin\V1\UserNotification;

use App\Http\Requests\ApiBaseFormRequest;

class IndexRequest extends ApiBaseFormRequest
{
    public function rules(): array
    {
        return [
            'status' => 'nullable|in:read,unread',
        ];
    }

    public function queryParameters(): array
    {
        return [
            'status' => [
                'description' => 'Filter notifications by status (default: unread)',
                'example' => 'unread',
                'type' => 'string',
            ],
        ];
    }
}
