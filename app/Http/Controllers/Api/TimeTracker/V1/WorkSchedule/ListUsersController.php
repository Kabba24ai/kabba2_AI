<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\WorkSchedule;

use App\Http\Controllers\Api\BaseController;
use App\Models\Iam\Personnel\User;
use Illuminate\Http\JsonResponse;

class ListUsersController extends BaseController
{
    public function __invoke(): JsonResponse
    {
        $employees = User::with(['store.hours', 'roles'])
            ->active()
            ->orderBy('first_name', 'ASC')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->full_name,

                     // primary role (for display)
                    'role' => $user->roles->first()?->short_name ?? 'employee',

                    // ALL roles for filtering
                    'roles' => $user->roles->pluck('short_name')->values(),

                     'roles_with_color' => $user->roles->map(function ($role) {
                        return [
                            'name' => $role->short_name,
                            'color' => $role->color,
                        ];
                    })->values(),

                    'primary_store' => $user->store?->store_name,

                    'store' => $user->store ? (function () use ($user) {

                        $today = now()->format('l');

                        $weekly = $user->store->hours->map(function ($hour) {
                            return [
                                'day' => $hour->day_name,
                                'open' => $hour->start_time,
                                'close' => $hour->end_time,
                                'is_closed' => (bool) $hour->is_closed,
                            ];
                        })->values();

                        $todayHours = $user->store->hours
                            ->where('day_name', $today)
                            ->first();

                        return [
                            'id' => $user->store->id,
                            'store_name' => $user->store->store_name,

                            'today_schedule' => $todayHours ? [
                                'day' => $todayHours->day_name,
                                'open' => $todayHours->start_time,
                                'close' => $todayHours->end_time,
                                'is_closed' => (bool) $todayHours->is_closed,
                            ] : null,

                            'weekly_schedule' => $weekly,
                        ];

                    })() : null,
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Employees fetched successfully.',
            'data' => $employees,
        ]);
    }
}