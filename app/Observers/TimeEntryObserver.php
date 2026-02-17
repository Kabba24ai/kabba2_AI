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

    // private static function resolveStatus(TimeEntry $timeEntry): array
    // {
    //     $user = $timeEntry->employee;

    //     // No shift defined → treat as present
    //     if (!$user || !$user->shift_start_time) {
    //         return ['present', 0];
    //     }

    //     $shiftStart = Carbon::parse(
    //         $timeEntry->clock_in->format('Y-m-d') . ' ' . $user->shift_start_time
    //     );

    //     if ($timeEntry->clock_in->lessThanOrEqualTo($shiftStart)) {
    //         return ['present', 0];
    //     }

    //     return [
    //         'late',
    //         $shiftStart->diffInMinutes($timeEntry->clock_in),
    //     ];
    // }

    private static function resolveStatus(TimeEntry $timeEntry): array
{
    $user = $timeEntry->employee;

    //  If no user OR no store assigned → always present
    if (!$user || !$user->store) {
        return ['present', 0];
    }

    // Get today's name (Monday, Tuesday...)
    $dayName = $timeEntry->clock_in->format('l');

    // Get store hours for today
    $storeHours = $user->store->hoursOfOperation()
        ->where('day_name', $dayName)
        ->first();

    // If no schedule found OR store closed → present
    if (!$storeHours || $storeHours->is_closed) {
        return ['present', 0];
    }

    // Build store opening time
    $storeStart = Carbon::parse(
        $timeEntry->clock_in->format('Y-m-d') . ' ' . $storeHours->start_time
    );

    // If on time or early
    if ($timeEntry->clock_in->lessThanOrEqualTo($storeStart)) {
        return ['present', 0];
    }

    // Otherwise late
    return [
        'late',
        $storeStart->diffInMinutes($timeEntry->clock_in),
    ];
}

}
