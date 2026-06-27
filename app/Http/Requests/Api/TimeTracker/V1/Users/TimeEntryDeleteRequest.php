<?php

namespace App\Http\Requests\Api\TimeTracker\V1\Users;

use App\Http\Requests\ApiBaseFormRequest;
use App\Models\Iam\Personnel\TimeEntryBreak;
use Illuminate\Support\Facades\Log;

class TimeEntryDeleteRequest extends ApiBaseFormRequest
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
                'in:clock_in,clock_out,lunch_out,lunch_in,unpaid_out,unpaid_in',
            ],

        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $breakId = $this->input('break_id');
            $entryId = $this->input('entry_id');

            if (!$breakId || !$entryId) {
                Log::info('[BreakOwnership:delete] skipped — no break_id in payload', [
                    'entry_id' => $entryId,
                    'break_id' => $breakId,
                ]);
                return;
            }

            $break = TimeEntryBreak::find($breakId);

            if ($break && (int) $break->time_entry_id !== (int) $entryId) {
                Log::warning('[BreakOwnership:delete] MISMATCH — 422 will be returned', [
                    'entry_id'            => $entryId,
                    'break_id'            => $breakId,
                    'break.time_entry_id' => $break->time_entry_id,
                ]);
                $validator->errors()->add(
                    'break_id',
                    'The selected break does not belong to the given time entry.'
                );
            } else {
                // Log::info('[BreakOwnership:delete] PASS', [
                //     'entry_id' => $entryId,
                //     'break_id' => $breakId,
                // ]);
            }
        });
    }
}