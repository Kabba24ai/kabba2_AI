<?php

namespace Tests\Unit\Services\ResolutionCenter;

use App\Services\ResolutionCenter\CancellationRefundScenario;
use App\Services\ResolutionCenter\ManualResolutionScenario;
use App\Services\ResolutionCenter\ResolutionScenarioRegistry;
use PHPUnit\Framework\TestCase;

class ResolutionScenarioRegistryTest extends TestCase
{
    public function test_resolves_the_reference_scenario_by_key(): void
    {
        $scenario = ResolutionScenarioRegistry::get(CancellationRefundScenario::KEY);

        $this->assertInstanceOf(CancellationRefundScenario::class, $scenario);
    }

    public function test_has_returns_true_for_a_registered_key(): void
    {
        $this->assertTrue(ResolutionScenarioRegistry::has('cancellation_refund'));
    }

    public function test_has_returns_false_for_an_unregistered_key(): void
    {
        $this->assertFalse(ResolutionScenarioRegistry::has('equipment_exchange'));
    }

    public function test_get_throws_for_an_unregistered_key(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ResolutionScenarioRegistry::get('equipment_exchange');
    }

    public function test_all_returns_every_registered_scenario(): void
    {
        $scenarios = ResolutionScenarioRegistry::all();

        // Phase 3.5 registered the framework's second scenario, Manual
        // Resolution — this count must grow again whenever a future phase
        // registers a third.
        $this->assertCount(2, $scenarios);
        $this->assertInstanceOf(CancellationRefundScenario::class, $scenarios['cancellation_refund']);
        $this->assertInstanceOf(ManualResolutionScenario::class, $scenarios['manual_resolution']);
    }
}
