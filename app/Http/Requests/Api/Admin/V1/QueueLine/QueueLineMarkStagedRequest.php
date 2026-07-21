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
            // Staging is all-or-nothing for every check that APPLIES to the
            // unit (fuel only for Diesel/Gas, key only for keyed starting
            // mechanisms — the payload's fuel.state / key.state says which).
            // An applicable check must be affirmatively true; a
            // not_applicable one may be omitted and is ignored either way.
            'fuel_full' => ['nullable', 'boolean'],
            'key_with_machine' => ['nullable', 'boolean'],
            'idempotency_token' => ['nullable', 'string', 'max:255'],
        ];
    }
}
