<?php

namespace App\Http\Requests\Api\EmploymentApplication\V1\Dashboard\OpportunityQuestions;

use App\Http\Requests\ApiBaseFormRequest;

class StoreQuestionRequest extends ApiBaseFormRequest
{
    public function authorize(): bool
    {
        return true; // add policy later
    }

    public function rules(): array
    {
        return [
            'type' => [
                'required',
                'in:mechanical,driving,computer',
            ],
            'question_text' => [
                'required',
                'string',
                'max:1000',
            ],
            'sub_text' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'required' => [
                'boolean',
            ],
            'answer_type' => [
                'required',
                'in:radio,checkbox,text,number,textarea',
            ],
            'answer_grid' => ['nullable', 'in:100,50,25'],
            'display_order' => [
                'nullable',
                'integer',
                'min:0',
            ],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'type' => [
                'description' => 'Question category',
                'example' => 'mechanical',
            ],
            'question_text' => [
                'description' => 'Main question text',
                'example' => 'Which tool is used to tighten bolts?',
            ],
            'sub_text' => [
                'description' => 'Optional helper text',
                'example' => 'Select the most appropriate tool',
            ],
            'required' => [
                'description' => 'Whether the question is mandatory',
                'example' => true,
            ],
            'answer_type' => [
                'description' => 'Answer input type',
                'example' => 'radio',
            ],
            'display_order' => [
                'description' => 'Order in which the question appears',
                'example' => 1,
            ],
        ];
    }
}
