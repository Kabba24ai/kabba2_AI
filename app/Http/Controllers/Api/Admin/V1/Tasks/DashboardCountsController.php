<?php

namespace App\Http\Controllers\Api\Admin\V1\Tasks;

use App\Http\Controllers\Api\BaseController;
use App\Models\Tasks\Task;
use Illuminate\Support\Facades\DB;

class DashboardCountsController extends BaseController
{
    public function __invoke()
    {
        $userId = auth('api_user')->id();

        $counts = Task::query()
            ->select('category', DB::raw('count(*) as count'))
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->groupBy('category')
            ->pluck('count', 'category');

        return response()->json([
            'success' => true,
            'data'    => [
                'sales_open_count' => (int) ($counts['sales'] ?? 0),
                'yard_open_count'  => (int) ($counts['yard']  ?? 0),
                'shop_open_count'  => (int) ($counts['shop']  ?? 0),
                'admin_open_count' => (int) ($counts['admin'] ?? 0),
                'my_open_count'    => (int) Task::where('assigned_to_user_id', $userId)
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->count(),
                'overdue_count'    => (int) Task::overdue()->count(),
            ],
        ]);
    }
}
