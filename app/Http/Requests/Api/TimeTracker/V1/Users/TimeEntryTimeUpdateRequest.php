<?php

namespace App\Http\Requests\Api\TimeTracker\V1\Users;

use App\Http\Requests\ApiBaseFormRequest;
use App\Models\Iam\Personnel\TimeEntry;
use App\Models\Iam\Personnel\TimeEntryBreak;
use Illuminate\Support\Carbon;
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
                'date_format:H:i',
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $entryId   = $this->input('entry_id');
            $breakId   = $this->input('break_id');
            $entryType = $this->input('entry_type');
            $newTime   = $this->input('new_time');

            // ── Clock ordering check ───────────────────────────────────────
            // Applies only to clock_in / clock_out edits.
            // Skips if new_time failed date_format:H:i (guard against unsafe parse).
            // Skips if either timestamp is null (open shifts remain valid).
            if (
                in_array($entryType, ['clock_in', 'clock_out'], true)
                && $entryId
                && $newTime
                && !$validator->errors()->has('new_time')
            ) {
                $entry = TimeEntry::find($entryId);

                if ($entry) {
                    // Use the existing timestamp's own date as the base so that
                    // overnight shifts (e.g. clock_out already on the next calendar
                    // day) are correctly preserved after the edit.
                    $effectiveClockIn  = $entry->clock_in;
                    $effectiveClockOut = $entry->clock_out;

                    if ($entryType === 'clock_in' && $effectiveClockIn) {
                        $effectiveClockIn = Carbon::createFromFormat(
                            'Y-m-d H:i',
                            $effectiveClockIn->toDateString() . ' ' . $newTime
                        );
                    } elseif ($entryType === 'clock_out' && $effectiveClockOut) {
                        $effectiveClockOut = Carbon::createFromFormat(
                            'Y-m-d H:i',
                            $effectiveClockOut->toDateString() . ' ' . $newTime
                        );
                    }

                    if ($effectiveClockIn && $effectiveClockOut) {
                        if (!$effectiveClockIn->lessThan($effectiveClockOut)) {
                            $validator->errors()->add('new_time', 'Clock-out must be after clock-in.');
                        }
                    }
                }
            }

            // ── Break ownership + type checks ──────────────────────────────
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
            } elseif ($break) {
                $expectedType = in_array($entryType, ['lunch_in', 'lunch_out']) ? 'lunch' : 'other';

                if ($break->type !== $expectedType) {
                    Log::warning('[BreakType:update] MISMATCH — 422 will be returned', [
                        'entry_id'      => $entryId,
                        'break_id'      => $breakId,
                        'break.type'    => $break->type,
                        'entry_type'    => $entryType,
                        'expected_type' => $expectedType,
                    ]);
                    $validator->errors()->add(
                        'break_id',
                        'Break type does not match the requested action.'
                    );
                }
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
