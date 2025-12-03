<?php

namespace App\Http\Requests\Admin\MaintenanceManagement\ServiceMaster\Task;

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
            'estimated_duration' => 'nullable|integer|min:0',
            'category_id' => 'nullable|exists:service_categories,id',
            'auto_apply' => 'boolean',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Task name is required.',
            'name.max' => 'Task name must not exceed 255 characters.',
            'estimated_duration.integer' => 'Estimated duration must be a number.',
            'estimated_duration.min' => 'Estimated duration cannot be negative.',
            'category_id.exists' => 'Selected category does not exist.',
        ];
    }
}
