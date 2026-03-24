<?php

namespace App\Http\Controllers\Api\SalesReports\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\TimeTracker\V1\Users\ListResource;
use App\Http\Resources\Api\TimeTracker\V1\Users\EmployeeResource;
use App\Helpers\SsoHelper;
use App\Models\Iam\Personnel\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SsoController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $token = $request->query('token');

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'SSO token is required',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            // Decode and validate the SSO token
            $decoded = json_decode(base64_decode($token), true);
            
            if (!isset($decoded['data']) || !isset($decoded['signature'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token format',
                ], JsonResponse::HTTP_UNAUTHORIZED);
            }

            // Verify signature
            $secret = env('SSO_SHARED_SECRET');
            if (!$secret) {
                Log::error('[SSO LOGIN] SSO_SHARED_SECRET is not set in .env on this server');
                return response()->json([
                    'success' => false,
                    'message' => 'SSO is not configured on this server (missing SSO_SHARED_SECRET)',
                ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
            }

            $expectedSignature = hash_hmac('sha256', $decoded['data'], $secret);

            Log::info('[SSO LOGIN] Signature check', [
                'received'  => $decoded['signature'],
                'expected'  => $expectedSignature,
                'match'     => hash_equals($expectedSignature, $decoded['signature']),
                'secret_first_8' => substr($secret, 0, 8) . '...',
            ]);
            
            if (!hash_equals($expectedSignature, $decoded['signature'])) {
                Log::warning('[SSO LOGIN] Invalid token signature — wrong SSO_SHARED_SECRET on this server');
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token signature',
                ], JsonResponse::HTTP_UNAUTHORIZED);
            }

            // Parse payload
            $payload = json_decode($decoded['data'], true);
            
            if (!isset($payload['email']) || !isset($payload['expires_at'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token payload',
                ], JsonResponse::HTTP_UNAUTHORIZED);
            }

            // Check expiration with small clock-skew tolerance.
            // This avoids false negatives when app servers are a bit out of sync.
            $clockSkewSeconds = (int) env('SSO_CLOCK_SKEW_SECONDS', 1440);
            $now = time();
            $expiresAt = (int) $payload['expires_at'];

            if ($now > ($expiresAt + $clockSkewSeconds)) {
                Log::warning('[SSO LOGIN] Token expired', [
                    'email' => $payload['email'] ?? null,
                    'now' => $now,
                    'expires_at' => $expiresAt,
                    'clock_skew_seconds' => $clockSkewSeconds,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Token has expired',
                ], JsonResponse::HTTP_UNAUTHORIZED);
            }

            // Find user by email
            /** @var \App\Models\Iam\Personnel\User|null $user */
            $user = User::active()->with(['store.hours', 'roles'])->where('email', $payload['email'])->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], JsonResponse::HTTP_NOT_FOUND);
            }

            // Generate API token (Sanctum)
            $user->token = $user->createToken('sales-reports-sso')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'SSO login successful',
                'user' => new ListResource($user),
                'employee' => new EmployeeResource($user),
                'roles' => $user->roles->pluck('short_name'),
            ]);
        } catch (\Exception $e) {
            Log::error('[SSO LOGIN] Error processing SSO token', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process SSO token',
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
