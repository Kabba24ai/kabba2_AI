<?php

namespace App\Http\Requests\Admin\MaintenanceManagement\ServiceMaster\Template;

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
            'preset_id' => 'required|exists:interval_presets,id',
            'tasks' => 'required|array|min:1',
            'tasks.*.task_id' => 'required|exists:service_tasks,id',
            'tasks.*.intervals' => 'required|array',
            'tasks.*.intervals.*' => 'integer|min:1',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Template name is required.',
            'name.max' => 'Template name must not exceed 255 characters.',
            'preset_id.required' => 'Interval preset is required.',
            'preset_id.exists' => 'Selected interval preset does not exist.',
            'tasks.required' => 'At least one task must be assigned.',
            'tasks.min' => 'At least one task must be assigned.',
            'tasks.*.task_id.required' => 'Task ID is required.',
            'tasks.*.task_id.exists' => 'Selected task does not exist.',
            'tasks.*.intervals.required' => 'Task intervals are required.',
        ];
    }
}
