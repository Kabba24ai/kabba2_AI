<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\Auth;

use App\Http\Controllers\Api\BaseController;
use App\Models\Iam\Personnel\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListUsersController extends BaseController
{
    public function __invoke(Request $request): JsonResponse
    {
        $users = User::active()
            ->orderBy('first_name', 'ASC')
            ->get(['id', 'first_name', 'last_name']);

        return response()->json([
            'success' => true,
            'message' => 'Employees fetched successfully.',
            'data' => $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'full_name' => $user->full_name, // accessor
                ];
            }),
        ]);
    }
}
