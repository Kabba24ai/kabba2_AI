<?php

namespace App\Http\Controllers\Api\Admin\V1\Clients;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

// Request
use App\Http\Requests\Api\Admin\V1\Clients\StoreApplicationCodeRequest;

// Model
use App\Models\Clients\Client;

// Logging
use Illuminate\Support\Facades\Log;

class StoreApplicationCodeController extends BaseController
{
    public function __invoke(
        StoreApplicationCodeRequest $request
    ): JsonResponse {

        // Log::info('Client Application Code API Hit', [
        //     'payload' => $request->all(),
        //     'host' => $request->getHost(),
        //     'ip' => $request->ip(),
        // ]);

        try {

            $validated = $request->validated();

            $client = Client::createOrUpdateByDomain(
                $validated['application_code'],
                $request
            );

            // Log::info('Client Saved Successfully', [
            //     'client_id' => $client->id,
            //     'client_code' => $client->code,
            //     'admin_url' => $client->admin_url,
            // ]);

            return response()->json([
                'success' => true,
                'message' => 'Client saved successfully.',
                'client' => $client,
            ]);

        } catch (\Exception $e) {

            Log::error('Client Save Failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.',
            ], 500);
        }
    }
}