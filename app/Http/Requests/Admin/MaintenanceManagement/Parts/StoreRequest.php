<?php

namespace App\Http\Requests\Admin\MaintenanceManagement\Parts;

use Illuminate\Foundation\Http\FormRequest;

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
        // Auto-determine category based on equipment_id
        $equipmentCategories = [
            'N/A' => 'Supplies',
            'EXC-001' => 'Excavators',
            'EXC-002' => 'Excavators',
            'GEN-045' => 'Generators',
            'GEN-046' => 'Generators',
            'BUL-012' => 'Bulldozers',
            'BUL-013' => 'Bulldozers',
            'LDR-023' => 'Loaders',
            'LDR-024' => 'Loaders',
            'CMP-078' => 'Compressors',
            'CMP-079' => 'Compressors'
        ];

        $equipmentNames = [
            'N/A' => 'General Use',
            'EXC-001' => 'CAT 320D Excavator',
            'EXC-002' => 'John Deere 350G Excavator',
            'GEN-045' => 'Kohler 150kW Generator',
            'GEN-046' => 'Cummins 200kW Generator',
            'BUL-012' => 'John Deere 650K Dozer',
            'BUL-013' => 'CAT D6T Dozer',
            'LDR-023' => 'CAT 950 Wheel Loader',
            'LDR-024' => 'John Deere 644K Loader',
            'CMP-078' => 'Atlas Copco GA30',
            'CMP-079' => 'Ingersoll Rand R55'
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
