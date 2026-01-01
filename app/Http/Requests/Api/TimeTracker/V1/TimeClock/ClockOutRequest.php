<?php

namespace App\Http\Requests\Api\TimeTracker\V1\TimeClock;

use App\Http\Requests\ApiBaseFormRequest;

class ClockOutRequest extends ApiBaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'break_duration' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'break_duration' => [
                'description' => 'Break duration in minutes',
                'example' => 30,
                'type' => 'integer',
            ],
        ];
    }
}
