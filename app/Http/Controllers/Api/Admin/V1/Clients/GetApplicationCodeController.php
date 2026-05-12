<?php

namespace App\Http\Controllers\Api\Admin\V1\Clients;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

// Model
use App\Models\Clients\Client;

class GetApplicationCodeController extends BaseController
{
    public function __invoke(
        Request $request
    ): JsonResponse {

        /*
        |--------------------------------------------------------------------------
        | Request Started
        |--------------------------------------------------------------------------
        */

        // Log::info('Get Application Code API Hit', [

        //     'query' => $request->all(),

        //     'host' => $request->getHost(),

        //     'ip' => $request->ip(),

        // ]);

        /*
        |--------------------------------------------------------------------------
        | Get Current Admin URL
        |--------------------------------------------------------------------------
        */

       $currentAdminUrl = rtrim(
            $request->query('current_admin_url'),
            '/'
        ) . '/';

        if (!$currentAdminUrl) {

            // Log::warning('Missing current_admin_url');

            return response()->json([
                'success' => false,
                'message' => 'Current admin URL is required.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Find Client
        |--------------------------------------------------------------------------
        */

        // Log::info('Searching Client By Admin URL', [

        //     'admin_url' => $currentAdminUrl,

        // ]);

        $client = Client::where(
            'admin_url',
            $currentAdminUrl
        )->first();

        /*
        |--------------------------------------------------------------------------
        | Client Not Found
        |--------------------------------------------------------------------------
        */

        if (!$client) {

            // Log::warning('Client Not Found', [

            //     'admin_url' => $currentAdminUrl,

            // ]);

            return response()->json([
                'success' => false,
                'message' => 'Client not found.',
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | Success
        |--------------------------------------------------------------------------
        */

        // Log::info('Application Code Found', [

        //     'client_id' => $client->id,

        //     'application_code' => $client->code,

        // ]);

        return response()->json([
            'success' => true,
            'application_code' => $client->code,
            'client' => $client,
        ]);
    }
}