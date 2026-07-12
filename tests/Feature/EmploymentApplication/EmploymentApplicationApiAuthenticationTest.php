<?php

namespace Tests\Feature\EmploymentApplication;

use App\Models\Iam\Personnel\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Employment Application API authentication.
 *
 * The system serves two audiences. The applicant/public surface (apply,
 * upload résumé, browse positions, reference data, careers content) stays
 * anonymous. The internal employee dashboard (applicant list/details/status,
 * position CRUD, opportunity-question CRUD, stats, careers-content editing)
 * now requires a Sanctum token via the existing api_user guard.
 *
 * Policy: authentication ONLY — any authenticated internal employee may use
 * the dashboard regardless of role. No role/permission or store gating is
 * asserted or expected here.
 */
class EmploymentApplicationApiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private string $base;

    /** [method, path] for every internal route that must require auth (16). */
    private const PROTECTED = [
        ['get',    'dashboard/stats'],
        ['get',    'dashboard/applications/list'],
        ['get',    'dashboard/applications/full/1'],
        ['post',   'dashboard/applications/1/status/1'],
        ['get',    'dashboard/positions/list'],
        ['post',   'dashboard/positions/store'],
        ['post',   'dashboard/positions/1'],
        ['get',    'dashboard/positions/1/delete'],
        ['post',   'dashboard/positions/1/status'],
        ['get',    'dashboard/opportunity-questions/list'],
        ['post',   'dashboard/opportunity-questions/reorder'],
        ['post',   'dashboard/opportunity-questions/1/status'],
        ['put',    'dashboard/opportunity-questions/1'],
        ['post',   'dashboard/opportunity-questions/store'],
        ['delete', 'dashboard/opportunity-questions/1'],
        ['post',   'site-content/opportunities/update/1'],
    ];

    /** [method, path] for the public surface that must stay reachable (7). */
    private const PUBLIC_SURFACE = [
        ['get',  'positions'],
        ['get',  'stores'],
        ['get',  'locations/states'],
        ['get',  'profile-settings'],
        ['get',  'site-content/opportunities'],
        ['get',  'auth/login'],            // SSO exchange (400 without a token)
        ['post', 'applications/store'],    // applicant submission
    ];

    /**
     * Read-only internal endpoints that load cleanly on an empty DB (expect
     * 200). opportunity-questions/list is excluded: it requires a ?type=
     * query param and returns a controller-level 422 without it — which still
     * proves auth passed and is covered by the not-401/403 loop above.
     */
    private const PROTECTED_READS = [
        'dashboard/stats',
        'dashboard/applications/list',
        'dashboard/positions/list',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->base = 'http://' . config('app.domains.api') . '/api/employment-application/v1';
    }

    private function hit(string $method, string $path, array $headers = [])
    {
        return $this->json(strtoupper($method), "{$this->base}/{$path}", [], $headers);
    }

    private function token(): string
    {
        $user = User::create([
            'first_name' => 'HR', 'last_name' => 'Staff',
            'email' => 'hr-' . uniqid() . '@example.com',
            'password' => bcrypt('secret-password'), 'status' => 'Active',
        ]);
        return $user->createToken('employment_application_react_app')->plainTextToken;
    }

    /** Every internal route returns JSON 401 for an anonymous caller. */
    public function test_internal_routes_reject_anonymous_with_json_401(): void
    {
        foreach (self::PROTECTED as [$method, $path]) {
            $response = $this->hit($method, $path);

            $response->assertStatus(401);
            $this->assertSame(
                'application/json',
                explode(';', (string) $response->headers->get('Content-Type'))[0],
                "{$method} /{$path} must return JSON on 401, not an HTML redirect."
            );
        }
    }

    /** Every internal route rejects an invalid token with JSON 401. */
    public function test_internal_routes_reject_invalid_token_with_json_401(): void
    {
        $headers = ['Authorization' => 'Bearer 999|invalidinvalidinvalid'];
        foreach (self::PROTECTED as [$method, $path]) {
            $this->hit($method, $path, $headers)->assertStatus(401);
        }
    }

    /** A valid employee token passes authentication on every internal route. */
    public function test_internal_routes_pass_with_valid_token(): void
    {
        $headers = ['Authorization' => 'Bearer ' . $this->token()];

        // Auth must pass everywhere (never 401/403), regardless of param data.
        foreach (self::PROTECTED as [$method, $path]) {
            $status = $this->hit($method, $path, $headers)->getStatusCode();
            $this->assertNotContains(
                $status,
                [401, 403],
                "{$method} /{$path} must pass authentication with a valid token; got {$status}."
            );
        }

        // Read endpoints with no params load cleanly (200) on an empty DB.
        foreach (self::PROTECTED_READS as $path) {
            $this->hit('get', $path, $headers)->assertStatus(200);
        }
    }

    /** The public applicant/reference surface stays reachable without a token. */
    public function test_public_surface_remains_anonymous(): void
    {
        foreach (self::PUBLIC_SURFACE as [$method, $path]) {
            $status = $this->hit($method, $path)->getStatusCode();
            $this->assertNotContains(
                $status,
                [401, 403],
                "{$method} /{$path} must remain public; got {$status}."
            );
        }
    }

    /** Public position browsing and reference dropdowns return data (200). */
    public function test_public_reference_endpoints_return_data(): void
    {
        foreach (['positions', 'stores', 'locations/states', 'profile-settings', 'site-content/opportunities'] as $path) {
            $this->hit('get', $path)->assertStatus(200);
        }
    }
}
