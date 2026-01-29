<?php

namespace App\Http\Controllers\Api\EmploymentApplication\V1\Dashboard;

use App\Http\Controllers\Api\BaseController;
use App\Models\Stores\Application;
use App\Models\Stores\EmploymentPosition;
use App\Models\Stores\Store;
use Illuminate\Http\JsonResponse;

class StatsController extends BaseController
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Dashboard stats fetched successfully.',
            'data' => [
                'totalApplications'   => Application::count(),
                'pendingApplications' => Application::where('status', 0)->count(),
                'totalStores'         => Store::count(),
                'totalPositions'      => EmploymentPosition::count(),
            ],
        ]);
    }
}
