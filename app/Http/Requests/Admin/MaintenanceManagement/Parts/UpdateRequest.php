<?php

namespace App\Http\Requests\Admin\MaintenanceManagement\Parts;

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
            'part_name' => 'required|string|max:255',
            'equipment_id' => 'required|string|max:50',
            'current_stock' => 'nullable|integer|min:0',
            'min_stock' => 'nullable|integer|min:0',
            'dni' => 'boolean',
            'description' => 'nullable|string',
            'part_number' => 'required|string|max:100',
            'unit_cost' => 'required|numeric|min:0',
            'supplier' => 'required|string|max:255',
            'part_number_alt_1' => 'nullable|string|max:100',
            'cost_alt_1' => 'nullable|numeric|min:0',
            'supplier_alt_1' => 'nullable|string|max:255',
            'part_number_alt_2' => 'nullable|string|max:100',
            'cost_alt_2' => 'nullable|numeric|min:0',
            'supplier_alt_2' => 'nullable|string|max:255',
        ];
    }

    protected function prepareForValidation()
    {
        // Same logic as StoreRequest
        $equipmentCategories = [
            'N/A' => 'Supplies',
            'EXC-001' => 'Excavators',
            // ... rest of mappings
        ];

        $equipmentNames = [
            'N/A' => 'General Use',
            'EXC-001' => 'CAT 320D Excavator',
            // ... rest of mappings
        ];

        $this->merge([
            'category' => $equipmentCategories[$this->equipment_id] ?? 'Unknown',
            'equipment_name' => $equipmentNames[$this->equipment_id] ?? 'Unknown',
            'stock_level' => $this->dni ? 0 : ($this->current_stock ?? 0),
            'min_stock' => $this->dni ? 0 : ($this->min_stock ?? 0),
            'dni' => $this->boolean('dni')
        ]);
    }
}
