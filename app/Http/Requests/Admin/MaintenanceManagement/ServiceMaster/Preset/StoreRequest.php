<?php

namespace App\Http\Requests\Admin\MaintenanceManagement\ServiceMaster\Preset;

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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'intervals' => 'required|array|min:1',
            'intervals.*' => 'required|integer|min:1',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Preset name is required.',
            'name.max' => 'Preset name must not exceed 255 characters.',
            'intervals.required' => 'At least one interval is required.',
            'intervals.min' => 'At least one interval is required.',
            'intervals.*.required' => 'Interval value is required.',
            'intervals.*.integer' => 'Interval must be a number.',
            'intervals.*.min' => 'Interval must be at least 1 hour.',
        ];
    }
}
