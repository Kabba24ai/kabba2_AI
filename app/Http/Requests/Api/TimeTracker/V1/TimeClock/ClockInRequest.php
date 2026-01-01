<?php

namespace App\Http\Requests\Api\TimeTracker\V1\TimeClock;

use App\Http\Requests\ApiBaseFormRequest;


class ClockInRequest extends ApiBaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'notes' => [
                'description' => 'Optional notes for clock-in',
                'example' => 'Started morning shift',
                'type' => 'string',
            ],
        ];
    }
}
