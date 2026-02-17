<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\Users;

use App\Http\Controllers\Api\BaseController;
use App\Http\Resources\Api\TimeTracker\V1\Users\EmployeeResource;
use App\Models\Iam\Personnel\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ListUsersController extends BaseController
{
   public function __invoke(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 30);
        $perPage = in_array($perPage, [5, 10, 30, 50, 100, 500]) ? $perPage : 10;

        $users = User::with([
                'roles',
                'vacationAllotmentHour',
                'vacationStartDay',
                'store.hours'
            ])
            ->active()
            ->orderBy('first_name', 'ASC')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Employees fetched successfully.',
            'data' => EmployeeResource::collection($users),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'per_page'     => $users->perPage(),
                'total'        => $users->total(),
            ],
        ]);
    }

}
