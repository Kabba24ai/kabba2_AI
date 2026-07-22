<?php

namespace Tests\Feature\Service;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * ST-3 — Service module permission wiring. Enforcement is globally deferred
 * today (AppServiceProvider's Gate::before allow-all), so these assert the
 * ROUTE MIDDLEWARE is correctly declared — the module is gated the moment
 * the business switches granular roles back on. Inspects the router, not
 * runtime enforcement, so it's independent of the bypass.
 */
class ServicePermissionWiringTest extends TestCase
{
    private function permMiddleware(string $routeName): array
    {
        $route = Route::getRoutes()->getByName($routeName);
        $this->assertNotNull($route, "Route {$routeName} should exist");

        return array_values(array_filter(
            $route->gatherMiddleware(),
            fn ($m) => is_string($m) && str_starts_with($m, 'permission:'),
        ));
    }

    /** @dataProvider gatedRoutes */
    public function test_route_carries_expected_permissions(string $routeName, array $expected): void
    {
        $this->assertEqualsCanonicalizing(
            array_map(fn ($p) => 'permission:' . $p, $expected),
            $this->permMiddleware($routeName),
        );
    }

    public static function gatedRoutes(): array
    {
        return [
            // Read surface — view only.
            'overview'           => ['admin.service-management.overview', ['service_tickets.view']],
            'tickets index'      => ['admin.service-management.tickets.index', ['service_tickets.view']],
            'tickets show'       => ['admin.service-management.tickets.show', ['service_tickets.view']],
            'settlement preview' => ['admin.service-management.tickets.settlement.preview', ['service_tickets.view']],

            // Ticket work — view (group) + manage.
            'ticket store'   => ['admin.service-management.tickets.store', ['service_tickets.view', 'service_tickets.manage']],
            'ticket status'  => ['admin.service-management.tickets.status', ['service_tickets.view', 'service_tickets.manage']],
            'responsibility' => ['admin.service-management.tickets.responsibility.decide', ['service_tickets.view', 'service_tickets.manage']],

            // Approval / authorization — view + authorize.
            'approve'          => ['admin.service-management.tickets.approval.approve', ['service_tickets.view', 'service_tickets.authorize']],
            'auth override'    => ['admin.service-management.tickets.authorization.override', ['service_tickets.view', 'service_tickets.authorize']],
            'deposit override' => ['admin.service-management.tickets.deposit.override', ['service_tickets.view', 'service_tickets.authorize']],

            // Billing — view + bill.
            'settlement store' => ['admin.service-management.tickets.settlement.store', ['service_tickets.view', 'service_tickets.bill']],

            // Problem templates — own view/manage.
            'templates index'  => ['admin.service-management.problem-templates.index', ['problem_templates.view']],
            'templates save'   => ['admin.service-management.problem-templates.save-items', ['problem_templates.view', 'problem_templates.manage']],
            'equipment attach' => ['admin.service-management.problem-templates.equipment.attach', ['problem_templates.view', 'problem_templates.manage']],

            // Field Service + Warranty.
            'field index'    => ['admin.field-service.tickets.index', ['field_service.view']],
            'field store'    => ['admin.field-service.tickets.store', ['field_service.view', 'field_service.manage']],
            'warranty index' => ['admin.warranty.claims.index', ['warranty.view']],
            'warranty store' => ['admin.warranty.claims.store', ['warranty.view', 'warranty.manage']],
        ];
    }
}
