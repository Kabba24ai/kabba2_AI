<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class SsoHelper
{
    /**
     * Generate a secure SSO token for cross-application authentication
     * Uses a shared secret that both applications must have in their .env
     *
     * @param string $email User email
     * @param int|null $minutes Token expiration time in minutes (default from env, fallback 4320 = 3 days)
     * @return string Base64 encoded token
     */
    public static function generateToken(string $email, ?int $minutes = null): string
    {
        $secret = env('SSO_SHARED_SECRET', config('app.key'));
        $expiresInMinutes = $minutes ?? (int) env('SSO_TOKEN_EXPIRATION_MINUTES', 4320);
        $expiresAt = Carbon::now()->addMinutes($expiresInMinutes)->timestamp;
        
        $payload = [
            'email' => $email,
            'expires_at' => $expiresAt,
            'timestamp' => Carbon::now()->timestamp,
        ];
        
        $data = json_encode($payload);
        $signature = hash_hmac('sha256', $data, $secret);
        
        $token = base64_encode(json_encode([
            'data' => $data,
            'signature' => $signature,
        ]));
        
        return $token;
    }

    /**
     * Generate SSO URL for project manager
     *
     * @param string $email User email
     * @param string $targetUrl Base URL of the target application
     * @param int|null $minutes Token expiration time in minutes (default from env, fallback 4320 = 3 days)
     * @return string Complete SSO URL
     */
    public static function generateSsoUrl(string $email, string $targetUrl, ?int $minutes = null): string
    {
        $token = self::generateToken($email, $minutes);
        $targetUrl = rtrim($targetUrl, '/');
        
        return $targetUrl . '/auth/sso?token=' . urlencode($token);
    }
}
