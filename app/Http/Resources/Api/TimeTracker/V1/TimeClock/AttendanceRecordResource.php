<?php

namespace App\Http\Resources\Api\TimeTracker\V1\TimeClock;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'employee_id' => (string) $this->employee_id,
            'attendance_date' => $this->attendance_date,
            'status' => $this->status,
            'check_in_time' => optional($this->check_in_time)?->toIso8601String(),
            'minutes_late' => (int) $this->minutes_late,
        ];
    }
}
    