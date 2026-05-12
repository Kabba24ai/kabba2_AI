<?php

namespace App\Http\Controllers\Api\Admin\V1\Hrm;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

// Requests
use App\Http\Requests\Api\Admin\V1\Hrm\ShowRequest;

// Resources
use App\Http\Resources\Api\Admin\V1\Users\ListResource;

// Model
use App\Models\Iam\Personnel\User;

class ShowController extends BaseController
{
    /**
     * Show HRM User
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(ShowRequest $request)
    {
        $validatedData = $request->validated();

        $user = User::find($validatedData['user_id']);

        return response()->json([
            'success' => true,
            'message' => 'HRM user found successfully.',
            'user' => new ListResource($user),
        ], JsonResponse::HTTP_OK);
    }
}