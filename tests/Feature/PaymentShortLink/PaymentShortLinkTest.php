<?php

namespace Tests\Feature\PaymentShortLink;

use App\Models\Orders\PaymentShortLink;
use App\Services\PaymentShortLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PaymentShortLinkTest extends TestCase
{
    use RefreshDatabase;

    private PaymentShortLinkService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PaymentShortLinkService();
    }

    // ── Service: trusted URL validation ──────────────────────────────────────

    public function test_only_trusted_urls_can_be_shortened(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        // Force production-like env so the host check runs
        $this->app->detectEnvironment(fn () => 'production');

        $this->service->shorten('https://evil.com/steal?card=1234');
    }

    public function test_internal_url_is_accepted_in_local_env(): void
    {
        // local env (default for tests) skips domain validation
        $link = $this->service->shorten('https://rentnking.com/checkout/order-payment-form/encrypted');

        $this->assertInstanceOf(PaymentShortLink::class, $link);
        $this->assertNotEmpty($link->token);
        $this->assertEquals('https://rentnking.com/checkout/order-payment-form/encrypted', $link->original_url);
    }

    // ── Redirect: valid link ──────────────────────────────────────────────────

    public function test_valid_short_link_redirects_to_original_url(): void
    {
        $link = PaymentShortLink::factory()->create([
            'original_url' => 'https://rentnking.com/checkout/order-payment-form/test',
            'expires_at'   => now()->addDays(7),
        ]);

        $response = $this->get("/pay/{$link->token}");

        $response->assertRedirect('https://rentnking.com/checkout/order-payment-form/test');
    }

    // ── Redirect: 404 on unknown token ────────────────────────────────────────

    public function test_unknown_token_returns_404(): void
    {
        $response = $this->get('/pay/DOESNOTEXIST1');

        $response->assertNotFound();
    }

    // ── Redirect: expired link shows expired view ─────────────────────────────

    public function test_expired_link_shows_expired_view(): void
    {
        $link = PaymentShortLink::factory()->create([
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->get("/pay/{$link->token}");

        $response->assertOk();
        $response->assertViewIs('front.checkout.payment-link-expired');
    }

    // ── Redirect: max-clicks link shows expired view ──────────────────────────

    public function test_max_clicks_reached_shows_expired_view(): void
    {
        $link = PaymentShortLink::factory()->create([
            'clicks'     => 5,
            'max_clicks' => 5,
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->get("/pay/{$link->token}");

        $response->assertOk();
        $response->assertViewIs('front.checkout.payment-link-expired');
    }

    // ── Click tracking: count increments ─────────────────────────────────────

    public function test_click_count_increments_on_valid_redirect(): void
    {
        $link = PaymentShortLink::factory()->create([
            'original_url' => 'https://rentnking.com/checkout/order-payment-form/test',
            'clicks'       => 0,
            'expires_at'   => now()->addDays(7),
        ]);

        $this->get("/pay/{$link->token}");

        $this->assertEquals(1, $link->fresh()->clicks);
    }

    public function test_click_metadata_is_stored(): void
    {
        $link = PaymentShortLink::factory()->create([
            'original_url' => 'https://rentnking.com/checkout/order-payment-form/test',
            'expires_at'   => now()->addDays(7),
        ]);

        $this->get("/pay/{$link->token}", ['User-Agent' => 'TestBrowser/1.0']);

        $fresh = $link->fresh();
        $this->assertNotNull($fresh->last_clicked_at);
        $this->assertNotNull($fresh->used_at);
    }

    // ── Model helpers ─────────────────────────────────────────────────────────

    public function test_is_expired_returns_true_when_past(): void
    {
        $link = PaymentShortLink::factory()->make(['expires_at' => now()->subHour()]);

        $this->assertTrue($link->isExpired());
    }

    public function test_is_expired_returns_false_when_future(): void
    {
        $link = PaymentShortLink::factory()->make(['expires_at' => now()->addHour()]);

        $this->assertFalse($link->isExpired());
    }

    public function test_has_reached_max_clicks_when_at_limit(): void
    {
        $link = PaymentShortLink::factory()->make(['clicks' => 10, 'max_clicks' => 10]);

        $this->assertTrue($link->hasReachedMaxClicks());
    }

    public function test_has_not_reached_max_clicks_when_under_limit(): void
    {
        $link = PaymentShortLink::factory()->make(['clicks' => 3, 'max_clicks' => 10]);

        $this->assertFalse($link->hasReachedMaxClicks());
    }

    public function test_is_active_false_when_expired(): void
    {
        $link = PaymentShortLink::factory()->make([
            'expires_at' => now()->subDay(),
            'max_clicks' => null,
        ]);

        $this->assertFalse($link->isActive());
    }

    public function test_is_active_false_when_max_clicks_reached(): void
    {
        $link = PaymentShortLink::factory()->make([
            'expires_at' => now()->addDay(),
            'clicks'     => 5,
            'max_clicks' => 5,
        ]);

        $this->assertFalse($link->isActive());
    }

    public function test_is_active_true_when_valid(): void
    {
        $link = PaymentShortLink::factory()->make([
            'expires_at' => now()->addDay(),
            'clicks'     => 2,
            'max_clicks' => 10,
        ]);

        $this->assertTrue($link->isActive());
    }

    // ── Service: token uniqueness ─────────────────────────────────────────────

    public function test_generated_token_is_unique(): void
    {
        $a = $this->service->shorten('https://rentnking.com/checkout/order-payment-form/a');
        $b = $this->service->shorten('https://rentnking.com/checkout/order-payment-form/b');

        $this->assertNotEquals($a->token, $b->token);
    }

    public function test_short_url_contains_token(): void
    {
        $link = $this->service->shorten('https://rentnking.com/checkout/order-payment-form/x');

        $this->assertStringContainsString($link->token, $this->service->shortUrlFor($link->token));
        $this->assertStringContainsString('/pay/', $this->service->shortUrlFor($link->token));
    }
}
