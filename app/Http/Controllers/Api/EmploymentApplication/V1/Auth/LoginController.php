<?php

namespace App\Http\Controllers\Api\EmploymentApplication\V1\Auth;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Log;

class LoginController extends BaseController
{
    public function __invoke(Request $request): JsonResponse
    {
        // Log::info('[SSO LOGIN] Request received', [
        //     'ip' => $request->ip(),
        //     'has_token' => $request->has('token'),
        // ]);

        $token = $request->token;

        if (!$token) {
            // Log::warning('[SSO LOGIN] Token missing in request');

            return response()->json([
                'success' => false,
                'message' => 'Token missing'
            ], 400);
        }

        // Log::info('[SSO LOGIN] Token received', [
        //     'token_preview' => substr($token, 0, 10) . '***',
        // ]);

        $accessToken = PersonalAccessToken::findToken($token);

        if (!$accessToken) {
            // Log::warning('[SSO LOGIN] Token not found in personal_access_tokens');

            return response()->json([
                'success' => false,
                'message' => 'Invalid token'
            ], 401);
        }

        // Log::info('[SSO LOGIN] Token found', [
        //     'token_id' => $accessToken->id,
        //     'abilities' => $accessToken->abilities,
        //     'expires_at' => $accessToken->expires_at,
        // ]);

        if (!$accessToken->can('sso')) {
            // Log::warning('[SSO LOGIN] Token does not have SSO ability', [
            //     'abilities' => $accessToken->abilities,
            // ]);

            return response()->json([
                'success' => false,
                'message' => 'Token does not have SSO permission'
            ], 401);
        }

        $user = $accessToken->tokenable;

        // Log::info('[SSO LOGIN] User resolved from token', [
        //     'user_id' => $user->id,
        //     'email' => $user->email ?? null,
        // ]);

        // Create long-lived API token
        $apiToken = $user->createToken(
            'employment_application_react_app'
        )->plainTextToken;

        // Log::info('[SSO LOGIN] New API token generated', [
        //     'user_id' => $user->id,
        // ]);

        // One-time SSO token
        $accessToken->delete();

        // Log::info('[SSO LOGIN] SSO token deleted (one-time use)', [
        //     'token_id' => $accessToken->id,
        // ]);

        return response()->json([
            'success' => true,
            'message' => 'SSO login successful',
            'user' => $user,
            'employee' => $user->employee,
            'token' => $apiToken,
        ]);
    }
}
