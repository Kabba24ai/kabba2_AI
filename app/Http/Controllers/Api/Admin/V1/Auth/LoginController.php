<?php

namespace App\Http\Controllers\Api\Admin\V1\Auth;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Api\BaseController;

// Resources
use App\Http\Resources\Api\Admin\V1\Users\ListResource;

// Requests
use App\Http\Requests\Api\Admin\V1\Auth\LoginRequest;
// Model
use App\Models\Iam\Personnel\User;

class LoginController extends BaseController
{

    /**
     * Login
     * @group Admin App
     */
    public function __invoke(LoginRequest $request)
    {
        $validatedData = $request->validated();

        try {
            $user = User::where("email", $validatedData['email'])->firstOrFail();

            if (!$user || !Hash::check($validatedData['password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => trans("messages.api.admin.v1.auth.login_failed"),
                ], JsonResponse::HTTP_NOT_ACCEPTABLE);
            }

            // If the customer is found, continue with your logic
            // $user->tokens()->delete();
            $user->token = $user->createToken('api_user')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => trans("messages.api.admin.v1.auth.login_success"),
                'user' =>  new ListResource($user),
            ]);

        } catch (\Exception $exception) {
            // Customer not found
            return response()->json([
                'success' => false,
                'message' => trans("messages.api.admin.v1.auth.login_failed"),
            ], JsonResponse::HTTP_NOT_FOUND);
        }
    }
}
