<?php

namespace App\Http\Requests\Admin\MaintenanceManagement\Parts\PartsList;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\MaintenanceManagement\Equipment;

class StoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'part_name'         => 'required|string|max:255',
            'description'       => 'nullable|string',
            'category_id'       => 'nullable',
            'selected_products' => 'nullable|array',
            'template_all_part_ids' => 'nullable|array',
        ];
    }

    protected function prepareForValidation()
    {
        // You can add any data manipulation here if needed before validation
    }
}
