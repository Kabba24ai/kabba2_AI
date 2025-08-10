<?php

namespace App\Helpers;

use Illuminate\Support\Facades\URL;

class SignedUrlHelper
{
    /**
     * Make a temporary signed URL with optional encryption for dynamic payload.
     *
     * @param string $routeName   Route name to sign
     * @param array  $params      Route parameters (payload will be encrypted if passed under 'payload')
     * @param int    $minutes     TTL in minutes
     * @param bool   $encrypt     Whether to encrypt the $params['payload'] array
     * @return string
     */
    public static function make(string $routeName, array $params = [], int $minutes = 5, bool $encrypt = true ): string
    {
        if ($encrypt && isset($params['payload']) && is_array($params['payload'])) {
            $params['payload'] = encrypt($params['payload']);
        }

        if($encrypt) {
            $params = self::encryptParams($params);
        }

        return URL::temporarySignedRoute(
            $routeName,
            now()->addMinutes($minutes),
            $params
        );
    }


    /**
     * Encrypt all route parameters.
     */
    public static function encryptParams(array $params): array
    {
        foreach ($params as $key => $value) {
            $params[$key] = encrypt($value);
        }
        return $params;
    }

    /**
     * Decrypt a single parameter.
     */
    public static function decode(string $cipher)
    {
        return decrypt($cipher);
    }

    /**
     * Decrypt multiple parameters by key.
     */
    public static function decodeParams(array $params, array $keys): array
    {
        foreach ($keys as $key) {
            if (isset($params[$key])) {
                $params[$key] = decrypt($params[$key]);
            }
        }
        return $params;
    }
}
