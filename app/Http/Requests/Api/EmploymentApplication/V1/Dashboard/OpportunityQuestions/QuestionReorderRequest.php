<?php

namespace App\Http\Requests\Api\EmploymentApplication\V1\Dashboard\OpportunityQuestions;

use App\Http\Requests\ApiBaseFormRequest;

class QuestionReorderRequest extends ApiBaseFormRequest
{
    public function authorize(): bool
    {
        return true; // add policy later
    }

    public function rules(): array
    {
        return [
            'source_id' => [
                'required',
                'string',
                'exists:opportunity_questions,unique_id',
            ],
            'target_id' => [
                'required',
                'string',
                'exists:opportunity_questions,unique_id',
            ],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'source_id' => [
                'description' => 'Unique ID of the question being moved',
                'example' => 'EOQ-3E8L-4YOT',
            ],
            'target_id' => [
                'description' => 'Unique ID of the question to swap order with',
                'example' => 'EOQ-R9I2-QU2R',
            ],
        ];
    }
}
