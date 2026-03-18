<?php

namespace App\Http\Controllers\Api\SalesReports\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\TimeTracker\V1\Users\ListResource;
use App\Http\Resources\Api\TimeTracker\V1\Users\EmployeeResource;
use App\Models\Iam\Personnel\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        /** @var \App\Models\Iam\Personnel\User|null $user */
        $user = User::active()->with(['store.hours', 'roles'])->where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password',
                'errors' => [
                    'email' => ['The provided credentials are incorrect.'],
                ],
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Generate API token (Sanctum)
        $user->token = $user->createToken('sales-reports')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'user' => new ListResource($user),
            'employee' => new EmployeeResource($user),
            'roles' => $user->roles->pluck('short_name'),
        ]);
    }
}

