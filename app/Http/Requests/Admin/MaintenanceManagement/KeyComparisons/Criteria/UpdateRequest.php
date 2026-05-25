<?php

namespace App\Http\Requests\Admin\MaintenanceManagement\KeyComparisons\Criteria;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'category_id' => ['required', 'integer', 'exists:product_categories,id'],
            'name' => ['required', 'string', 'max:150'],
            'unit' => ['nullable', 'string', 'max:30'],
            'default_weight' => ['required', 'integer', 'between:0,100'],
            'upgrade_exceeds_value' => ['nullable', 'boolean'],
            'caution_if_change_value' => ['nullable', 'boolean'],
            'upgrade_is_below_value' => ['nullable', 'boolean'],
            'caution_if_below_value' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
