<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\Attendance;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\TimeTracker\V1\TimeClock\AttendanceRecordResource;
use App\Http\Resources\Api\TimeTracker\V1\TimeClock\AchievementGoalResource;

use App\Http\Controllers\Api\BaseController;    
use App\Models\Iam\Personnel\AttendanceRecord;
use App\Models\Iam\Personnel\AchievementGoal;
use Illuminate\Http\Request;

class GetAttendanceController extends Controller
{
   public function __invoke(Request $request)
{
    $user = auth()->user();

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated',
        ], 401);
    }

    $start   = $request->query('start_date');
    $end     = $request->query('end_date');
    $perPage = (int) $request->query('per_page', 10);

    $recordsQuery = AttendanceRecord::where('employee_id', $user->id)
        ->when($start, fn ($q) => $q->whereDate('attendance_date', '>=', $start))
        ->when($end, fn ($q) => $q->whereDate('attendance_date', '<=', $end));

    $stats = [
        'days_present' => (clone $recordsQuery)->where('status', 'present')->count(),
        'days_late' => (clone $recordsQuery)->where('status', 'late')->count(),
        'days_missed' => (clone $recordsQuery)->where('status', 'missed')->count(),
        'days_excused' => (clone $recordsQuery)->where('status', 'excused')->count(),
        'total_minutes_late' => (clone $recordsQuery)->sum('minutes_late'),
    ];

    //  Achievement logic
    $achievement = null;

    $goals = AchievementGoal::where('is_active', true)
        ->orderBy('display_order')
        ->get();

    foreach ($goals as $goal) {
        if (
            $stats['days_missed'] <= $goal->days_missed_max &&
            $stats['days_late'] <= $goal->days_late_max
        ) {
            $achievement = $goal;
            break;
        }
    }

    $stats['achievement'] = $achievement
        ? new AchievementGoalResource($achievement)
        : null;

    $records = $recordsQuery
        ->orderByDesc('attendance_date')
        ->paginate($perPage);

    return response()->json([
        'success' => true,
        'data' => AttendanceRecordResource::collection($records),
        'stats' => $stats,
        'meta' => [
            'current_page' => $records->currentPage(),
            'last_page' => $records->lastPage(),
            'per_page' => $records->perPage(),
            'total' => $records->total(),
        ],
    ]);
}


}
