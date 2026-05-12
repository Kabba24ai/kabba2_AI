<?php

namespace App\Http\Controllers\Api\Admin\V1\Hrm;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

// Requests
use App\Http\Requests\Api\Admin\V1\Hrm\IndexRequest;

// Resources
use App\Http\Resources\Api\Admin\V1\Users\ListResource;

// Model
use App\Models\Iam\Personnel\User;

class IndexController extends BaseController
{
    /**
     * HRM Users List
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(IndexRequest $request)
    {
        $validatedData = $request->validated();

        $perPage = $validatedData['per_page'] ?? 10;

        $users = User::query()
            ->orderBy('first_name')
            ->paginate($perPage);

        if ($users->isEmpty()) {

            return response()->json([
                'success' => false,
                'message' => 'No HRM users found.',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'message' => 'HRM users found successfully.',
            'users' => ListResource::collection($users),

            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }
}