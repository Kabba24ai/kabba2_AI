<?php

namespace App\Http\Requests\Admin\MaintenanceManagement\ServiceMaster\Template;

use Illuminate\Foundation\Http\FormRequest;

class AddTaskRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'task_id' => 'required|exists:service_tasks,id',
            'intervals' => 'nullable|array',
            'intervals.*' => 'integer|min:1',
        ];
    }

    public function messages()
    {
        return [
            'task_id.required' => 'Task ID is required.',
            'task_id.exists' => 'Selected task does not exist.',
            'intervals.array' => 'Intervals must be an array.',
            'intervals.*.integer' => 'Each interval must be a number.',
            'intervals.*.min' => 'Interval values must be at least 1.',
        ];
    }
}
