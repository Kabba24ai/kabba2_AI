<?php

namespace App\Http\Requests\Admin\MaintenanceManagement\Equipments;

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
            'equipment_name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'equipment_id' => 'required|string|max:255|unique:equipments,equipment_id',
            'equipment_hours' => 'nullable|numeric|min:0',
            'brand' => 'required|string|max:255',
            'model' => 'nullable|string|max:255',
            'model_year' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'date_acquired' => 'nullable|date',
            'cost' => 'nullable|numeric|min:0',
            'ownership_type' => 'nullable|in:owned,financed,leased',
            'finance_company' => 'nullable|string|max:255',
            'term' => 'nullable|integer|min:1',
            'rate' => 'nullable|numeric|min:0',
            'monthly_payment' => 'nullable|numeric|min:0',
            'vin' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'plate' => 'nullable|string|max:255',
            'imei' => 'nullable|string|max:255',
            'rental_ready_checklist' => 'nullable|string|max:255',
            'equipment_service_list' => 'nullable|string|max:255',
            'power_source_type'=>'required|string|max:255',
            'has_def' => 'nullable|string|max:10',
            'diesel_tank_capacity'=> 'nullable|numeric|min:0',
            'gas_tank_capacity'=> 'nullable|numeric|min:0',
            'standard_battery_count'=> 'nullable|numeric|min:0',
            'expanded_battery_count'=> 'nullable|numeric|min:0',
            'checklist_master'=> 'required|string|max:255',
            'equipment_parts_list' => 'required|string|max:255',
            'equipment_notes' => 'nullable|string',
        ];
    }

    public function messages()
    {
        return [
            'equipment_name.required' => 'Equipment name is required',
            'category.required' => 'Category is required',
            'equipment_id.required' => 'Equipment ID is required',
            'equipment_id.unique' => 'Equipment ID already exists',
            'brand.required' => 'Brand is required',
            'model_year.integer' => 'Please enter a valid year',
            'model_year.min' => 'Please enter a valid year',
            'model_year.max' => 'Please enter a valid year',
            'cost.numeric' => 'Please enter a valid cost',
            'rate.numeric' => 'Please enter a valid interest rate',
            'term.integer' => 'Please enter a valid term in months',
        ];
    }
}
