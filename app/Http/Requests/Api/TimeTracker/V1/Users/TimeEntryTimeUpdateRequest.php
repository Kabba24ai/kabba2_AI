<?php

namespace App\Http\Requests\Api\TimeTracker\V1\Users;

use App\Http\Requests\ApiBaseFormRequest;
use App\Models\Iam\Personnel\TimeEntryBreak;
use Illuminate\Support\Facades\Log;

class TimeEntryTimeUpdateRequest extends ApiBaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'entry_id' => [
                'required',
                'integer',
                'exists:time_entries,id',
            ],

            'break_id' => [
                'nullable',
                'integer',
                'exists:time_entry_breaks,id',
            ],

            'entry_type' => [
                'required',
                'string',
                'in:clock_in,clock_out,lunch_in,lunch_out,unpaid_in,unpaid_out',
            ],

            'new_time' => [
                'required',
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $breakId = $this->input('break_id');
            $entryId = $this->input('entry_id');

            if (!$breakId || !$entryId) {
                Log::info('[BreakOwnership:update] skipped — no break_id in payload', [
                    'entry_id' => $entryId,
                    'break_id' => $breakId,
                ]);
                return;
            }

            $break = TimeEntryBreak::find($breakId);

            if ($break && (int) $break->time_entry_id !== (int) $entryId) {
                Log::warning('[BreakOwnership:update] MISMATCH — 422 will be returned', [
                    'entry_id'            => $entryId,
                    'break_id'            => $breakId,
                    'break.time_entry_id' => $break->time_entry_id,
                ]);
                $validator->errors()->add(
                    'break_id',
                    'The selected break does not belong to the given time entry.'
                );
            } else {
                // Log::info('[BreakOwnership:update] PASS', [
                //     'entry_id' => $entryId,
                //     'break_id' => $breakId,
                // ]);
            }
        });
    }

    public function bodyParameters(): array
    {
        return [
            'entry_id' => [
                'description' => 'Main time entry ID',
                'example' => 133,
                'type' => 'integer',
            ],
            'break_id' => [
                'description' => 'Break ID (if updating lunch/unpaid break)',
                'example' => 81,
                'type' => 'integer',
            ],
            'entry_type' => [
                'description' => 'Type of entry being updated',
                'example' => 'clock_in',
                'type' => 'string',
            ],
            'new_time' => [
                'description' => 'New time value (24h format)',
                'example' => '14:30',
                'type' => 'string',
            ],
        ];
    }
}
