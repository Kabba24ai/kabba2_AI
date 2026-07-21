<?php

namespace App\Http\Requests\Api\Admin\V1\QueueLine;

use App\Http\Requests\ApiBaseFormRequest;

class QueueLineMarkStagedRequest extends ApiBaseFormRequest
{
    public function rules(): array
    {
        return [
            'equipment_unique_id' => ['required', 'string', 'max:255'],
            'performed_by' => ['required', 'string', 'max:255'], // user unique_id
            // Staging is all-or-nothing: both must be affirmatively true.
            // Anything else is rejected with a corrective error — there is
            // no partial staging state to record.
            'fuel_full' => ['required', 'boolean'],
            'key_with_machine' => ['required', 'boolean'],
            'idempotency_token' => ['nullable', 'string', 'max:255'],
        ];
    }
}
