<?php

namespace App\Http\Requests\Admin\OrderManagement\Orders;

use Illuminate\Foundation\Http\FormRequest;

class BulkDeleteRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Add authorization logic if needed
    }

    public function rules()
    {
        return [
            'unique_ids' => 'required|array',
            'unique_ids.*' => 'required|string|distinct',
        ];
    }
}
