<?php

namespace App\Http\Requests\Admin\MaintenanceManagement\Parts;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\MaintenanceManagement\Equipment;

class StoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'part_name' => 'required|string|max:255',
            'part_description' => 'required',

            'current_stock' => 'nullable|integer|min:0',
            'min_stock' => 'nullable|integer|min:0',

            'dni' => 'boolean',
            'gsi' => 'boolean',

            'description' => 'nullable|string',
            // Primary part details
            'part_number' => 'required|string|max:100',
            'unit_cost' => 'required|numeric|min:0',
            'supplier' => 'required|string|max:255',

            // Alternative 1
            'part_number_alt_1' => 'nullable|string|max:100',
            'cost_alt_1' => 'nullable|numeric|min:0',
            'supplier_alt_1' => 'nullable|string|max:255',

            // Alternative 2
            'part_number_alt_2' => 'nullable|string|max:100',
            'cost_alt_2' => 'nullable|numeric|min:0',
            'supplier_alt_2' => 'nullable|string|max:255',

        ];
    }

    protected function prepareForValidation()
    {


        $this->merge([
            'stock_level' => $this->dni ? 0 : ($this->current_stock ?? 0),
            'min_stock' => $this->dni ? 0 : ($this->min_stock ?? 0),
            'dni' => $this->boolean('dni'),
            'gsi' => $this->boolean('gsi'),
        ]);
    }
}
