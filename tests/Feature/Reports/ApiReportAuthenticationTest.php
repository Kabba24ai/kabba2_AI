<?php

namespace Tests\Feature\Reports;

use App\Models\Configurations\Setting;
use App\Models\Iam\Personnel\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 3A — Reporting API authentication.
 *
 * Every endpoint that serves reporting DATA now requires a valid Sanctum
 * token via the api_user guard. Token issuance (login, SSO) stays public.
 *
 * Policy: authentication ONLY. Any authenticated employee may view reports
 * regardless of role — no role/permission or store-level authorization is
 * asserted or expected here.
 */
class ApiReportAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private string $apiBase;

    /** All 11 reporting + 3 reference endpoints that must require auth. */
    private const PROTECTED_ENDPOINTS = [
        'rolling-30-days',
        '7-day-comparison',
        'last-month-comparison',
        'top-products',
        'top-categories',
        'sales-summary',
        'discounts-report',
        'refunds-report',
        'revenue-breakdown',
        'tax-and-payments',
        'product-sales-details',
        'categories',
        'products',
        'stores',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->apiBase = 'http://' . config('app.domains.api') . '/api/sales-reports/v1';

        // Some engines read the tax rate; seed it so valid-token calls return
        // 200 rather than erroring for an unrelated reason. updateOrCreate,
        // not create: SettingSeeder already seeds a 'sales_tax' row against
        // a properly-migrated database (unique on setting_name).
        Setting::updateOrCreate(
            ['setting_name' => 'sales_tax'],
            ['setting_type' => 'General Settings', 'value_type' => 'text', 'setting_title' => 'sales_tax', 'setting_value' => '0.10']
        );
    }

    private function makeEmployee(string $email): User
    {
        return User::create([
            'first_name' => 'Report', 'last_name' => 'Viewer',
            'email' => $email, 'password' => bcrypt('secret-password'), 'status' => 'Active',
        ]);
    }

    private function token(User $user): string
    {
        return $user->createToken('sales-reports')->plainTextToken;
    }

    /** Anonymous requests to every protected endpoint return JSON 401. */
    public function test_anonymous_requests_are_rejected_with_json_401(): void
    {
        foreach (self::PROTECTED_ENDPOINTS as $path) {
            $response = $this->getJson("{$this->apiBase}/{$path}");

            $response->assertStatus(401);
            $this->assertSame(
                'application/json',
                explode(';', (string) $response->headers->get('Content-Type'))[0],
                "/{$path} must return JSON on 401, not an HTML redirect."
            );
        }
    }

    /** A malformed/invalid bearer token is rejected with JSON 401. */
    public function test_invalid_token_is_rejected_with_json_401(): void
    {
        foreach (['Bearer not-a-real-token', 'Bearer 999|deadbeef', 'Bearer '] as $header) {
            $response = $this->withHeader('Authorization', $header)
                ->getJson("{$this->apiBase}/sales-summary");

            $response->assertStatus(401);
        }
    }

    /** A valid token loads every protected endpoint (200, no auth failure). */
    public function test_valid_token_loads_every_reporting_endpoint(): void
    {
        $token = $this->token($this->makeEmployee('viewer@example.com'));

        foreach (self::PROTECTED_ENDPOINTS as $path) {
            $response = $this->withHeader('Authorization', "Bearer {$token}")
                ->getJson("{$this->apiBase}/{$path}");

            $this->assertNotContains(
                $response->getStatusCode(),
                [401, 403],
                "/{$path} must be reachable with a valid token; got {$response->getStatusCode()}."
            );
            $response->assertStatus(200);
        }
    }

    /**
     * Any authenticated employee may view — no role gating. A Technician-type
     * user (no special role) reaches the reports exactly like anyone else.
     */
    public function test_any_authenticated_employee_may_view_regardless_of_role(): void
    {
        $token = $this->token($this->makeEmployee('technician@example.com'));

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("{$this->apiBase}/sales-summary")
            ->assertStatus(200);
    }

    /** Token issuance endpoints remain public. */
    public function test_login_and_sso_remain_public(): void
    {
        $this->makeEmployee('login-user@example.com');

        // Login reachable without a token, returns a token.
        $login = $this->postJson("{$this->apiBase}/login", [
            'email' => 'login-user@example.com',
            'password' => 'secret-password',
        ]);
        $login->assertOk()->assertJsonPath('success', true);
        $this->assertNotEmpty(data_get($login->json(), 'user.token'));

        // SSO reachable without a token (returns 400 for a missing token param,
        // NOT 401 — proving the route itself is not auth-gated).
        $this->getJson("{$this->apiBase}/sso")->assertStatus(400);
    }
}
