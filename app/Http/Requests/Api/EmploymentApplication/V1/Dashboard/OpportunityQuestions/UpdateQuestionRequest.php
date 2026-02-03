<?php

namespace App\Http\Requests\Api\EmploymentApplication\V1\Dashboard\OpportunityQuestions;

use App\Http\Requests\ApiBaseFormRequest;

class UpdateQuestionRequest extends ApiBaseFormRequest
{
    public function authorize(): bool
    {
        return true; // add policy later
    }

    public function rules(): array
    {
        return [
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
            'question_text' => [
                'description' => 'Updated question text',
                'example' => 'Which wrench is best for this task?',
            ],
            'sub_text' => [
                'description' => 'Optional helper text',
                'example' => 'Choose one option',
            ],
            'required' => [
                'description' => 'Whether the question is mandatory',
                'example' => false,
            ],
            'answer_type' => [
                'description' => 'Answer input type',
                'example' => 'checkbox',
            ],
            'display_order' => [
                'description' => 'Order of the question',
                'example' => 2,
            ],
        ];
    }
}
