<?php

namespace App\Http\Requests\Api\Admin\V1\Authorize;

use App\Http\Requests\ApiBaseFormRequest;

class ScheduleRequest extends ApiBaseFormRequest
{

    public function rules(): array
    {
        return [
            'unique_id' => 'required',
            'schedule_datetime' => ['nullable', 'date_format:Y-m-d H:i:s'],
        ];
    }

    public function messages(): array
    {
        return [
            'expiry_date.regex' => 'The expiry date must be provided in the MM/YY format.',
        ];
    }

    /**
     * Get the body parameters for the request documentation.
     *
     * @return array
     */
    public function bodyParameters(): array
    {
        return [
            'unique_id' => [
                'description' => 'The unique id of the user.',
                'example' => 'AUTH-JNU6-ULQ5',
            ],
            'schedule_datetime' => [
                'description' => 'The date and time to schedule the authorization (optional).',
                'example' => '2025-01-12 14:30:00',
            ],

        ];
    }
}
