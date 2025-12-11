<?php

namespace App\Http\Requests\Admin\MaintenanceManagement\EquipmentService;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRecordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'record_id' => 'nullable|exists:equipment_service_tasks,id',
            'equipment_id' => 'required|exists:equipment,id',
            'service_template_id' => 'required|exists:service_templates,id',
            'service_task_id' => 'required|exists:service_tasks,id',
            'interval' => 'required|numeric|min:0',
            'interval_value' => 'required|integer|min:0',
            'interval_type' => 'required|in:hour,date',
            'performed_by' => 'required|exists:users,id',
            'performed_date' => 'required|date_format:m/d/Y',
            'checked_by' => 'required|exists:users,id',
            'checked_date' => 'required|date_format:m/d/Y|after_or_equal:performed_date',
            'actual_hours' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:5000',
        ];
    }

    /**
     * Get custom error messages for validator.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'checked_date.after_or_equal' => 'The checked date must be on the same day or after the performed date.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'equipment_id' => 'equipment',
            'service_template_id' => 'service template',
            'service_task_id' => 'service task',
            'performed_by' => 'performed by',
            'performed_date' => 'performed date',
            'checked_by' => 'checked by',
            'checked_date' => 'checked date',
            'actual_hours' => 'actual machine hours',
        ];
    }
}
