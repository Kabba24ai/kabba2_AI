<?php

    namespace App\Http\Controllers\Api\TimeTracker\V1\TimeReports;

    use App\Http\Controllers\Api\BaseController;
    use App\Models\Iam\Personnel\User;
    use Illuminate\Http\JsonResponse;
    use Illuminate\Http\Request;
    use Carbon\Carbon;

    class ListTimeReportsController extends BaseController
    {
        public function __invoke(Request $request): JsonResponse
        {
            $perPage = (int) $request->get('per_page', 10);
            $perPage = in_array($perPage, [5, 10, 30, 50, 100, 500]) ? $perPage : 10;

            $year = Carbon::now()->year;

            $users = User::with([
                'timeEntries' => function ($q) use ($year) {
                    $q->whereYear('clock_in', $year)
                    ->whereNotNull('clock_out')
                    ->with('breaks');
                },
                'approvedVacationRequests.requestHour',
            ])
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'admin')) //
            ->active()
            ->orderBy('first_name')
            ->paginate($perPage);

            $data = $users->getCollection()->map(function ($user) use ($year) {

                // Paid hours (already net of breaks)
                    $paidHours = round($user->timeEntries->sum('total_hours'), 2);

                    // All break seconds (lunch + other)
                    $breakSeconds = 0;

                    foreach ($user->timeEntries as $entry) {
                        foreach ($entry->breaks as $break) {

                            if (!$break->start_time || !$break->end_time) {
                                continue;
                            }

                            $breakSeconds +=
                                $break->start_time->diffInSeconds($break->end_time);
                        }
                    }

                    // All breaks → hours
                    $lunchHours = round($breakSeconds / 3600, 2); // (ALL breaks)

                    // Unpaid = total - paid = breaks
                    $unpaidHours = $lunchHours;

                    // Total = paid + unpaid
                    $totalHours = round($paidHours + $unpaidHours, 2);


                $vacationHours = round(
                        $user->approvedVacationRequests
                            ->where(fn ($req) => 
                                Carbon::parse($req->start_date)->year === $year
                            )
                            ->sum(fn ($req) => $req->requestHour?->hours ?? 0),
                        2
                    );


                return [
                    'employee_id'     => (string) $user->id,
                    'employee_name'   => $user->full_name,
                    'total_hours'     => $totalHours,
                    'paid_hours'      => $paidHours,
                    'lunch_hours'     => $lunchHours,
                    'unpaid_hours'    => $unpaidHours,
                    'vacation_hours'  => $vacationHours,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Time reports fetched successfully',
                'data'    => $data,
                'meta'    => [
                    'current_page' => $users->currentPage(),
                    'last_page'    => $users->lastPage(),
                    'per_page'     => $users->perPage(),
                    'total'        => $users->total(),
                ],
            ]);
        }
    }
