<?php

namespace App\Http\Requests\Admin\MaintenanceManagement\KeyComparisons\Criteria;

use Illuminate\Foundation\Http\FormRequest;

class IndexRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'category_id' => ['required', 'integer', 'exists:product_categories,id'],
        ];
    }
}
