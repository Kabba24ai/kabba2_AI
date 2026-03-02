<?php

namespace App\Http\Controllers\Api\Admin\V1\Clients;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

// Request
use App\Http\Requests\Api\Admin\V1\Clients\PostRequest;

// Model
use App\Models\Clients\Client;

// Resource
use App\Http\Resources\Api\Admin\V1\Clients\ListResource;

class PostController extends BaseController
{
    /**
     * Get Client Information
     *
     * @group Admin App
     */
    public function __invoke(PostRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $client = Client::where('code', $validated['code'])->first();

        if (!$client) {
            return response()->json(
                [
                    'success' => false,
                    'message' => trans('messages.api.admin.v1.clients.no_clients_found'),
                ],
                JsonResponse::HTTP_NOT_FOUND,
            );
        }

        return response()->json([
            'success' => true,
            'message' => trans('messages.api.admin.v1.clients.clients_found'),
            'client' => new ListResource($client),
        ]);
    }
}
