<?php

namespace App\Http\Requests\Admin\FieldService;

use App\Enums\FieldService\FieldMissionStatus;
use App\Enums\FieldService\FieldOperationalExpectation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MissionStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mission_status' => ['required', Rule::enum(FieldMissionStatus::class)],
            'note'           => [
                'nullable',
                Rule::requiredIf($this->input('mission_status') === FieldMissionStatus::Cancelled->value),
                'string', 'max:2000',
            ],
            // Completing the assessment records what was found in the field.
            'assessment_summary' => [
                'nullable',
                Rule::requiredIf($this->input('mission_status') === FieldMissionStatus::AssessmentComplete->value),
                'string', 'max:10000',
            ],
            'operational_expectation' => ['nullable', Rule::enum(FieldOperationalExpectation::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'note.required'               => 'A short note is required when cancelling a mission.',
            'assessment_summary.required' => 'Record what was found on site before completing the assessment.',
        ];
    }
}
