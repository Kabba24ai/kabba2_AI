<?php

namespace App\Http\Requests\Api\Admin\V1\QueueLine;

use App\Http\Requests\ApiBaseFormRequest;

class QueueLineSwitchEquipmentRequest extends ApiBaseFormRequest
{
    public function rules(): array
    {
        return [
            'equipment_unique_id' => ['required', 'string', 'max:255'],
            'performed_by' => ['required', 'string', 'max:255'], // user unique_id
            'reason' => ['nullable', 'string', 'max:255'],
            'idempotency_token' => ['nullable', 'string', 'max:255'],
        ];
    }
}
