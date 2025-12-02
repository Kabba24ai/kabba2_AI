<?php

namespace App\Http\Requests\Admin\MaintenanceManagement\ServiceMaster\Template;

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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'preset_id' => 'required|exists:interval_presets,id',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Template name is required.',
            'name.max' => 'Template name must not exceed 255 characters.',
            'preset_id.required' => 'Interval preset is required.',
            'preset_id.exists' => 'Selected interval preset does not exist.',
        ];
    }
}
