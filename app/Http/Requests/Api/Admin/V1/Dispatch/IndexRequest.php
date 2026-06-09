<?php

namespace App\Http\Requests\Api\Admin\V1\Dispatch;

use App\Http\Requests\ApiBaseFormRequest;

class IndexRequest extends ApiBaseFormRequest
{
    public function rules(): array
    {
        return [
            'date_filter' => ['nullable', 'in:Today,Tomorrow,All'],
            'driver_id'   => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
