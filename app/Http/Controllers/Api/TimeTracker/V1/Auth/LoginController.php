<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\Auth;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Api\TimeTracker\V1\Auth\LoginRequest;
use App\Http\Resources\Api\TimeTracker\V1\Users\ListResource;
use App\Http\Resources\Api\TimeTracker\V1\Users\EmployeeResource;
use App\Models\Iam\Personnel\User;

class LoginController extends BaseController
{
    public function __invoke(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::active()->with(['store.hours'])->find($data['user_id']);

        if (!$user || $user->employee_code !== $data['employee_code']) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid employee code',
            ], JsonResponse::HTTP_NOT_ACCEPTABLE);
        }

        // Generate token
        $user->token = $user->createToken('api_user')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => trans("messages.api.time_tracker.v1.auth.login_success"),
            'user' => new ListResource($user),
            'employee' => new EmployeeResource($user),
            'roles' => $user->roles->pluck('short_name'),
        ]);
    }
}
