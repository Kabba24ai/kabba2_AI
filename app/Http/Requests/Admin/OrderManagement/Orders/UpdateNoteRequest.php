<?php

namespace App\Http\Requests\Admin\OrderManagement\Orders;

use App\Http\Requests\ApiBaseFormRequest;

// Helpers
use App\Helpers\PurifyHelper;

class UpdateNoteRequest extends ApiBaseFormRequest
{
    public function rules()
    {
        return [
            'order_note' => 'nullable|string|max:500',
        ];
    }

}
