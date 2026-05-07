<?php

namespace App\Http\Controllers\Api\Admin\V1\Hrm;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

// Requests
use App\Http\Requests\Api\Admin\V1\Hrm\StoreRequest;

// Resources
use App\Http\Resources\Api\Admin\V1\Users\ListResource;

// Model
use App\Models\Iam\Personnel\User;

class StoreController extends BaseController
{
    /**
     * Create HRM User
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(StoreRequest $request)
    {
        $validatedData = $request->validated();

        $user = User::create([

            'first_name' => $validatedData['first_name'],
            'middle_name' => $validatedData['middle_name'] ?? null,
            'last_name' => $validatedData['last_name'],

            'email' => $validatedData['email'],

            'mobile_phone' => $validatedData['mobile_phone'] ?? null,
            'phone_number' => $validatedData['phone_number'] ?? null,

            'password' => Hash::make('12345678'),
             'status'         => 'Active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'HRM user created successfully.',
            'user' => new ListResource($user),
        ], JsonResponse::HTTP_CREATED);
    }
}