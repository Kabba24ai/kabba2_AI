<?php

namespace App\Http\Requests\Api\TimeTracker\V1\Users;

use App\Http\Requests\ApiBaseFormRequest;
use App\Models\Iam\Personnel\TimeEntryBreak;
use Illuminate\Support\Facades\Log;

class TimeEntryBulkUpdateRequest extends ApiBaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'updates' => [
                'required',
                'array',
                'min:1'
            ],

            'updates.*.entry_id' => [
                'required',
                'integer',
                'exists:time_entries,id'
            ],

            'updates.*.break_id' => [
                'nullable',
                'integer',
                'exists:time_entry_breaks,id'
            ],

            'updates.*.entry_type' => [
                'required',
                'string',
                'in:clock_in,clock_out,lunch_in,lunch_out,unpaid_in,unpaid_out'
            ],

            'updates.*.new_time' => [
                'required',
                'date_format:H:i'
            ]

        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ($this->input('updates', []) as $index => $item) {
                $breakId = $item['break_id'] ?? null;
                $entryId = $item['entry_id'] ?? null;

                if (!$breakId || !$entryId) {
                    continue;
                }

                $break = TimeEntryBreak::find($breakId);

                if ($break && (int) $break->time_entry_id !== (int) $entryId) {
                    Log::warning('[BreakOwnership:bulk] MISMATCH — 422 will be returned', [
                        'index'               => $index,
                        'entry_id'            => $entryId,
                        'break_id'            => $breakId,
                        'break.time_entry_id' => $break->time_entry_id,
                    ]);
                    $validator->errors()->add(
                        "updates.{$index}.break_id",
                        'The selected break does not belong to the given time entry.'
                    );
                } else {
                    // Log::info('[BreakOwnership:bulk] PASS', [
                    //     'index'    => $index,
                    //     'entry_id' => $entryId,
                    //     'break_id' => $breakId,
                    // ]);
                }
            }
        });
    }

    public function bodyParameters(): array
    {
        return [

            'updates' => [
                'description' => 'Array of time entry updates',
                'type' => 'array',
                'example' => [
                    [
                        'entry_id' => 144,
                        'break_id' => null,
                        'entry_type' => 'clock_in',
                        'new_time' => '11:25'
                    ],
                    [
                        'entry_id' => 144,
                        'break_id' => 81,
                        'entry_type' => 'lunch_in',
                        'new_time' => '06:30'
                    ]
                ]
            ]

        ];
    }
}