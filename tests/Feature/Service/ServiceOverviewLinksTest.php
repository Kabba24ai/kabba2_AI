<?php

namespace Tests\Feature\Service;

use App\Models\Iam\Personnel\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ST-5 polish — the Service Overview's stale "Coming in Phase 3" Field
 * Service button is now a live link, and the KPI tiles deep-link into the
 * ticket list using filters the index already supports.
 */
class ServiceOverviewLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_overview_links_field_service_and_kpi_tiles(): void
    {
        $this->actingAs(User::create([
            'first_name' => 'Ov', 'last_name' => 'Erview',
            'email' => 'overview@test.local', 'status' => 'Active',
        ]));

        $html = $this->get(route('admin.service-management.overview'))->assertOk()->getContent();

        // Field Service Call is a live link now, not a disabled stub.
        $this->assertStringContainsString(route('admin.field-service.tickets.create'), $html);
        $this->assertStringNotContainsString('Coming in Phase 3', $html);

        // KPI tiles deep-link with the index's existing filters.
        $index = route('admin.service-management.tickets.index');
        $this->assertStringContainsString($index . '?priority=emergency', $html);
        $this->assertStringContainsString($index . '?state=blocked', $html);
        $this->assertStringContainsString($index . '?financial_status=ready_to_bill', $html);
    }
}
