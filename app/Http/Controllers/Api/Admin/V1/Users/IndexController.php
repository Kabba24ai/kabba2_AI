<?php

namespace App\Http\Controllers\Api\Admin\V1\Users;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

// Requests
use App\Http\Requests\Api\Admin\V1\Users\IndexRequest;

// Resources
use App\Http\Resources\Api\Admin\V1\Users\ListResource;

// Model
use App\Models\Iam\Personnel\User;

class IndexController extends BaseController
{
    /**
     * Users List
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(IndexRequest $request)
    {
        $validatedData = $request->validated();

        $perPage = $validatedData['per_page'] ?? 10;

        $users = User::active()->orderByDesc('id')->when(isset($validatedData['is_driver']), function ($query) use ($validatedData) {
                        $query->where('is_driver', $validatedData['is_driver']);
                    })->paginate($perPage);
        
        $users = User::active()->orderByDesc('id')->paginate($perPage);

        // if ($users->isEmpty()) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => trans('messages.api.admin.v1.users.no_users_found'),
        //     ], JsonResponse::HTTP_NOT_FOUND);
        // }

        return response()->json([
            'success' => true,
            'message' => trans('messages.api.admin.v1.users.users_found'),
            'users' => ListResource::collection($users),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'per_page'     => $users->perPage(),
                'total'        => $users->total(),
            ],
        ]);
    }
}
