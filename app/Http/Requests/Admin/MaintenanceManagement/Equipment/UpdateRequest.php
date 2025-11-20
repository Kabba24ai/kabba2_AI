<?php

namespace App\Http\Requests\Admin\MaintenanceManagement\Equipment;

use App\Helpers\PurifyHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        // Purify all input data
        $input = PurifyHelper::purify($this->all(), ['equipment_notes']);
        $this->merge($input);

        // Normalize date_acquired
        if (!empty($this->date_acquired)) {
            try {
            $this->merge([
                'date_acquired' => date('Y-m-d', strtotime($this->date_acquired))
            ]);
            } catch (\Exception $e) {
            // Ignore invalid date format
            }
        }
    }

    public function rules()
    {
        $uniqueId = $this->route('unique_id');

        return [
            'equipment_name' => [
                'required',
                'string',
                'max:255',
            ],

            'product_category_id' => 'required|exists:product_categories,id',

            'equipment_id' => [
                'required',
                'string',
                'max:255',
                Rule::unique('equipment', 'equipment_id')->ignore($uniqueId, 'unique_id'),
            ],

            'store_id' => 'nullable|exists:stores,id',

            'equipment_hours' => 'nullable|numeric|min:0',
            'is_tracked' => 'nullable|boolean',
            'overage_rate' => 'nullable|numeric|min:0',

            'brand' => 'required|string|max:255',
            'model' => 'nullable|string|max:255',
            'model_year' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'date_acquired' => 'nullable|date',

            'purchase_cost' => 'nullable|numeric|min:0',

            'ownership_type' => 'nullable|in:owned,financed,leased',
            'finance_company' => 'nullable|string|max:255',
            'term_in_months' => 'nullable|integer|min:1',
            'interest_rate' => 'nullable|numeric|min:0',
            'monthly_payment' => 'nullable|numeric|min:0',

            'vehicle_identification_number' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'license_plate' => 'nullable|string|max:255',
            'imei' => 'nullable|string|max:255',

            'power_source_type'     => 'nullable|in:diesel,gas,batteries',
            'has_def'               => 'nullable|boolean',

            'diesel_tank_capacity'  => 'nullable|required_if:power_source_type,diesel|numeric|min:0',
            'def_tank_capacity'     => 'nullable|required_if:has_def,true|numeric|min:0',
            'gas_tank_capacity'     => 'nullable|required_if:power_source_type,gas|numeric|min:0',

            'standard_battery_count' => 'nullable|required_if:power_source_type,batteries|numeric|min:0',
            'expanded_battery_count' => 'nullable|required_if:power_source_type,batteries|numeric|min:0',

            'checklist_master_id' => 'nullable|exists:checklist_masters,id',
            'equipment_service_id' => 'nullable|exists:equipment_services,id',
            'part_id' => 'nullable|exists:parts,id',

            'equipment_notes' => 'nullable|string',
        ];
    }

    public function messages()
    {
        return [
            'equipment_name.required' => 'Equipment name is required',
            'product_category_id.required' => 'Category is required',

            'equipment_id.required' => 'Equipment ID is required',
            'equipment_id.unique' => 'Equipment ID already exists',

            'brand.required' => 'Brand is required',

            'model_year.integer' => 'Please enter a valid year',
            'model_year.min' => 'Please enter a valid year',
            'model_year.max' => 'Please enter a valid year',

            'purchase_cost.numeric' => 'Please enter a valid cost',
            'interest_rate.numeric' => 'Please enter a valid interest rate',
            'term_in_months.integer' => 'Please enter a valid term in months',

            'equipment_hours.numeric' => 'Please enter a valid number of hours',
            'power_source_type.required' => 'Power source is required',
            'power_source_type.in' => 'Power source must be diesel, gas, or batteries',
        ];
    }
}
