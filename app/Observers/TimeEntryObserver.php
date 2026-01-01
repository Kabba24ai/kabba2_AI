<?php

namespace App\Observers;

use App\Models\Iam\Personnel\TimeEntry;
use App\Models\Iam\Personnel\AttendanceRecord;
use Carbon\Carbon;

class TimeEntryObserver
{
    public function created(TimeEntry $timeEntry): void
    {
        $employeeId = $timeEntry->employee_id;
        $clockIn    = $timeEntry->clock_in;
        $date       = $clockIn->toDateString();

        // If attendance already exists → do nothing
        $attendanceExists = AttendanceRecord::where('employee_id', $employeeId)
            ->whereDate('attendance_date', $date)
            ->exists();

        if ($attendanceExists) {
            return;
        }

        [$status, $minutesLate] = self::resolveStatus($timeEntry);

        AttendanceRecord::create([
            'employee_id'     => $employeeId,
            'attendance_date' => $date,
            'status'          => $status,
            'check_in_time'   => $clockIn,
            'minutes_late'    => $minutesLate,
        ]);
    }

    private static function resolveStatus(TimeEntry $timeEntry): array
    {
        $user = $timeEntry->employee;

        // No shift defined → treat as present
        if (!$user || !$user->shift_start_time) {
            return ['present', 0];
        }

        $shiftStart = Carbon::parse(
            $timeEntry->clock_in->format('Y-m-d') . ' ' . $user->shift_start_time
        );

        if ($timeEntry->clock_in->lessThanOrEqualTo($shiftStart)) {
            return ['present', 0];
        }

        return [
            'late',
            $shiftStart->diffInMinutes($timeEntry->clock_in),
        ];
    }
}
