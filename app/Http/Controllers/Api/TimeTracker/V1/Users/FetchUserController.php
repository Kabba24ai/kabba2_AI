<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\Users;

use App\Http\Controllers\Api\BaseController;
use App\Http\Resources\Api\TimeTracker\V1\Users\EmployeeResource;
use App\Models\Iam\Personnel\User;
use Illuminate\Http\JsonResponse;

class FetchUserController extends BaseController
{
    public function __invoke(int $userId): JsonResponse
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Employee record not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new EmployeeResource($user),
        ]);
    }
}
