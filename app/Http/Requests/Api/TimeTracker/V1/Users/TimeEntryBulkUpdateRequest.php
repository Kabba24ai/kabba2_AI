<?php

namespace App\Http\Requests\Api\TimeTracker\V1\Users;

use App\Http\Requests\ApiBaseFormRequest;
use App\Models\Iam\Personnel\TimeEntry;
use App\Models\Iam\Personnel\TimeEntryBreak;
use Illuminate\Support\Carbon;
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
                'min:1',
                'max:100',
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
            $updates = $this->input('updates', []);

            // ── Phase 1: Clock ordering — grouped by entry_id ──────────────
            // Collect all clock_in / clock_out items per entry, then apply them
            // on top of the DB values before comparing. This handles a bulk
            // payload that edits both fields for the same entry in one request.
            $entryGroups  = [];
            foreach ($updates as $index => $item) {
                $entryId   = $item['entry_id']   ?? null;
                $entryType = $item['entry_type'] ?? null;
                $newTime   = $item['new_time']   ?? null;

                if (
                    !$entryId
                    || !in_array($entryType, ['clock_in', 'clock_out'], true)
                    || !$newTime
                    || $validator->errors()->has("updates.{$index}.new_time")
                ) {
                    continue;
                }

                $entryGroups[$entryId][] = [
                    'index'    => $index,
                    'type'     => $entryType,
                    'new_time' => $newTime,
                ];
            }

            $loadedEntries = [];
            foreach ($entryGroups as $entryId => $items) {
                $entry = $loadedEntries[$entryId]
                    ?? ($loadedEntries[$entryId] = TimeEntry::find($entryId));

                if (!$entry) {
                    continue;
                }

                // Start from DB values; overlay submitted changes on top.
                // Use each existing timestamp's own date as the base so that
                // overnight shifts (clock_out already on the next calendar day)
                // are correctly preserved.
                $effectiveClockIn  = $entry->clock_in;
                $effectiveClockOut = $entry->clock_out;

                $clockInIndex  = null;
                $clockOutIndex = null;

                foreach ($items as $item) {
                    if ($item['type'] === 'clock_in' && $effectiveClockIn) {
                        $effectiveClockIn = Carbon::createFromFormat(
                            'Y-m-d H:i',
                            $effectiveClockIn->toDateString() . ' ' . $item['new_time']
                        );
                        $clockInIndex = $item['index'];
                    } elseif ($item['type'] === 'clock_out' && $effectiveClockOut) {
                        $effectiveClockOut = Carbon::createFromFormat(
                            'Y-m-d H:i',
                            $effectiveClockOut->toDateString() . ' ' . $item['new_time']
                        );
                        $clockOutIndex = $item['index'];
                    }
                }

                // Skip open shifts (either timestamp null → nothing to compare).
                if ($effectiveClockIn && $effectiveClockOut) {
                    if (!$effectiveClockIn->lessThan($effectiveClockOut)) {
                        // Attach error to clock_out item if present; otherwise clock_in.
                        $blameIndex = $clockOutIndex ?? $clockInIndex ?? $items[0]['index'];
                        $validator->errors()->add(
                            "updates.{$blameIndex}.new_time",
                            'Clock-out must be after clock-in.'
                        );
                    }
                }
            }

            // ── Phase 2: Break ownership + type checks ─────────────────────
            foreach ($updates as $index => $item) {
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
                } elseif ($break) {
                    $entryType    = $item['entry_type'] ?? null;
                    $expectedType = in_array($entryType, ['lunch_in', 'lunch_out']) ? 'lunch' : 'other';

                    if ($break->type !== $expectedType) {
                        Log::warning('[BreakType:bulk] MISMATCH — 422 will be returned', [
                            'index'         => $index,
                            'entry_id'      => $entryId,
                            'break_id'      => $breakId,
                            'break.type'    => $break->type,
                            'entry_type'    => $entryType,
                            'expected_type' => $expectedType,
                        ]);
                        $validator->errors()->add(
                            "updates.{$index}.break_id",
                            'Break type does not match the requested action.'
                        );
                    }
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