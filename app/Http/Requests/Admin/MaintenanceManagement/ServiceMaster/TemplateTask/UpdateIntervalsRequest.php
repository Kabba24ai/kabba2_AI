<?php

namespace App\Http\Requests\Admin\MaintenanceManagement\ServiceMaster\TemplateTask;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIntervalsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'intervals' => 'nullable|array',
            'intervals.*' => 'integer|min:1',
        ];
    }

    public function messages()
    {
        return [
            'intervals.array' => 'Intervals must be an array.',
            'intervals.*.integer' => 'Each interval must be a number.',
            'intervals.*.min' => 'Interval values must be at least 1.',
        ];
    }
}
