<?php

namespace App\Http\Resources\Api\TimeTracker\V1\TimeClock;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimeEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,

            'clock_in' => $this->clock_in?->toDateTimeString(),
            'clock_out' => $this->clock_out?->toDateTimeString(),

            'break_duration' => $this->break_duration,
            'total_hours' => $this->total_hours,

            'notes' => $this->notes,
            'status' => $this->status,

            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
