<?php

namespace App\Services;

use App\Models\Orders\PaymentShortLink;
use Carbon\Carbon;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PaymentShortLinkService
{
    /**
     * Trusted hosts that may appear in original_url.
     * Add additional internal domains here if needed.
     */
    private const TRUSTED_HOSTS = [
        'rentnking.com',
        'www.rentnking.com',
    ];

    /**
     * Default link lifetime in days.
     */
    private const DEFAULT_EXPIRY_DAYS = 14;

    /**
     * Generate a short link for a payment URL.
     *
     * @param  string       $originalUrl  The full payment URL to shorten.
     * @param  int|null     $orderId
     * @param  int|null     $customerId
     * @param  Carbon|null  $expiresAt    Defaults to 14 days from now.
     * @param  int|null     $maxClicks    Null = unlimited.
     * @param  int|null     $createdBy    Admin user ID if triggered from admin panel.
     */
    public function shorten(
        string  $originalUrl,
        ?int    $orderId    = null,
        ?int    $customerId = null,
        ?Carbon $expiresAt  = null,
        ?int    $maxClicks  = null,
        ?int    $createdBy  = null
    ): PaymentShortLink {
        $this->assertTrustedUrl($originalUrl);

        return PaymentShortLink::create([
            'token'        => $this->generateToken(),
            'order_id'     => $orderId,
            'customer_id'  => $customerId,
            'created_by'   => $createdBy,
            'original_url' => $originalUrl,
            'expires_at'   => $expiresAt ?? now()->addDays(self::DEFAULT_EXPIRY_DAYS),
            'max_clicks'   => $maxClicks,
        ]);
    }

    /**
     * Return the branded short URL for a given token.
     */
    public function shortUrlFor(string $token): string
    {
        return route('front.pay.redirect', ['token' => $token]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function generateToken(): string
    {
        do {
            // Str::random uses random_bytes() internally — cryptographically secure.
            $token = Str::random(10);
        } while (PaymentShortLink::where('token', $token)->exists());

        return $token;
    }

    /**
     * Reject URLs that do not belong to a trusted host.
     * Prevents this service from being used as an open redirect generator.
     */
    private function assertTrustedUrl(string $url): void
    {
        $host = parse_url($url, PHP_URL_HOST);

        // Also allow localhost / internal dev domains when APP_ENV is local/testing
        if (in_array(app()->environment(), ['local', 'testing'], true)) {
            return;
        }

        if ($host === null || !in_array(strtolower($host), self::TRUSTED_HOSTS, true)) {
            throw new InvalidArgumentException(
                "Short links may only target trusted internal domains. Rejected host: {$host}"
            );
        }
    }
}
