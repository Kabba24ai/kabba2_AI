<?php

namespace App\Http\Requests\Admin\MaintenanceManagement\KeyComparisons\Criteria;

use Illuminate\Foundation\Http\FormRequest;

class AiStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:product_categories,id'],
            'name' => ['required', 'string', 'max:150'],
            'unit' => ['nullable', 'string', 'max:30'],
        ];
    }
}
