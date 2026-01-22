<?php

namespace App\Http\Requests\Admin\MaintenanceManagement\ServiceMaster\Settings;

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
            'pending_before_hours' => 'required|integer|min:0',
            'pending_after_hours' => 'required|integer|min:0',
            'pending_before_dates' => 'required|integer|min:0',
            'pending_after_dates' => 'required|integer|min:0',
            'master_admin_code' => 'nullable|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'pending_before_hours.required' => 'Pending before hours is required.',
            'pending_before_hours.integer' => 'Pending before hours must be a number.',
            'pending_before_hours.min' => 'Pending before hours cannot be negative.',
            'pending_after_hours.required' => 'Pending after hours is required.',
            'pending_after_hours.integer' => 'Pending after hours must be a number.',
            'pending_after_hours.min' => 'Pending after hours cannot be negative.',
            'pending_before_dates.required' => 'Pending before dates is required.',
            'pending_before_dates.integer' => 'Pending before dates must be a number.',
            'pending_before_dates.min' => 'Pending before dates cannot be negative.',
            'pending_after_dates.required' => 'Pending after dates is required.',
            'pending_after_dates.integer' => 'Pending after dates must be a number.',
            'pending_after_dates.min' => 'Pending after dates cannot be negative.',
            'master_admin_code.max' => 'Master admin code must not exceed 255 characters.',
        ];
    }
}
