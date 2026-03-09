<?php

namespace App\Http\Controllers\Api\Admin\V1\Clients;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

// Request

// Model
use App\Models\Clients\Client;

// Resource
use App\Http\Resources\Api\Admin\V1\Clients\ListResource;
use Illuminate\Support\Facades\Request;

class PostController extends BaseController
{
    /**
     * Get Client Information
     *
     * @group Admin App
     */
    public function __invoke(Request $request): JsonResponse
    {
        $code = $request->input('code');

        if (!$code) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Code is required.',
                ],
                JsonResponse::HTTP_BAD_REQUEST,
            );
        }

        if (strlen($code) > 10) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Code must not exceed 10 characters.',
                ],
                JsonResponse::HTTP_BAD_REQUEST,
            );
        }

        if (!Client::where('code', $code)->exists()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Invalid Code.',
                ],
                JsonResponse::HTTP_BAD_REQUEST,
            );
        }

        $client = Client::where('code', $code)->first();

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
