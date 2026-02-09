<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\Users;

use App\Http\Controllers\Controller;
use App\Models\Iam\Personnel\User;
use App\Models\Iam\Personnel\AttendanceRecord;
use App\Models\Iam\Personnel\AchievementGoal;
use Illuminate\Http\Request;

class GetAdminAttendanceSummaryController extends Controller
{
    public function __invoke(Request $request)
    {
        $start   = $request->query('start_date');
        $end     = $request->query('end_date');

        $perPage = (int) $request->query('per_page', 30);
        $perPage = in_array($perPage, [5, 10, 30, 50, 100, 500]) ? $perPage : 10;

        $employees = User::active()
            ->orderBy('first_name', 'ASC')
            ->paginate($perPage);

        $goals = AchievementGoal::where('is_active', true)
            ->orderBy('display_order')
            ->get();

        $data = collect($employees->items())->map(function ($employee) use ($start, $end, $goals) {

            $q = AttendanceRecord::where('employee_id', $employee->id)
                ->when($start, fn ($q) => $q->whereDate('attendance_date', '>=', $start))
                ->when($end, fn ($q) => $q->whereDate('attendance_date', '<=', $end));

            $stats = [
                'employee_id' => $employee->id,
                'first_name'  => $employee->first_name,
                'last_name'   => $employee->last_name,
                'email'       => $employee->email,
                'days_present' => (clone $q)->where('status', 'present')->count(),
                'days_late'    => (clone $q)->where('status', 'late')->count(),
                'days_missed'  => (clone $q)->where('status', 'missed')->count(),
                'days_excused' => (clone $q)->where('status', 'excused')->count(),
                'total_minutes_late' => (clone $q)->sum('minutes_late'),
                'achievement' => null,
            ];

            foreach ($goals as $goal) {
                if (
                    $goal->goal_type === 'positive' &&
                    $stats['days_missed'] <= $goal->days_missed_max &&
                    $stats['days_late'] <= $goal->days_late_max
                ) {
                    $stats['achievement'] = [
                        'id' => $goal->id,
                        'goal_name' => $goal->goal_name,
                        'icon' => $goal->icon,
                        'color' => $goal->color,
                    ];
                    break;
                }
            }

            return $stats;
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Employees With Attendance Record fetched successfully.',
            'meta' => [
                'current_page' => $employees->currentPage(),
                'last_page'    => $employees->lastPage(),
                'per_page'     => $employees->perPage(),
                'total'        => $employees->total(),
            ],
        ]);
    }
}
