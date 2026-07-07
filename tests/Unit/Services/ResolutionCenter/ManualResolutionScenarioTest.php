<?php

namespace Tests\Unit\Services\ResolutionCenter;

use App\Services\ResolutionCenter\ManualResolutionScenario;
use App\Services\ResolutionCenter\ScenarioAction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Phase 3.5 — Manual Resolution Scenario Foundation.
 *
 * Unlike CancellationRefundScenarioTest, there is no separate decision
 * engine to prove equivalence against — this scenario's `recommend()` only
 * echoes the employee's own two answers back, so these tests confirm that
 * echo (and its gating) directly.
 */
class ManualResolutionScenarioTest extends TestCase
{
    private ManualResolutionScenario $scenario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scenario = new ManualResolutionScenario;
    }

    public function test_key_and_label(): void
    {
        $this->assertSame('manual_resolution', $this->scenario->key());
        $this->assertSame('Manual Resolution', $this->scenario->label());
    }

    public function test_returns_null_until_both_answers_are_present(): void
    {
        $this->assertNull($this->scenario->recommend([]));
        $this->assertNull($this->scenario->recommend(['issue_category' => ManualResolutionScenario::CATEGORY_WEATHER_DELAY]));
        $this->assertNull($this->scenario->recommend(['selected_resolution' => ManualResolutionScenario::RESOLUTION_NO_ACTION]));
    }

    #[DataProvider('resolutionOptions')]
    public function test_echoes_the_selected_resolution_with_contextual_next_step(string $resolution): void
    {
        $recommendation = $this->scenario->recommend([
            'issue_category' => ManualResolutionScenario::CATEGORY_OTHER,
            'selected_resolution' => $resolution,
        ]);

        $this->assertNotNull($recommendation);
        $this->assertSame([$resolution], $recommendation->options);
        $this->assertSame($resolution, $recommendation->primaryOption());
        $this->assertNotEmpty($recommendation->nextStep);
        $this->assertFalse($recommendation->requiresManagerOverride);
    }

    public static function resolutionOptions(): array
    {
        return array_map(
            fn ($resolution) => [$resolution],
            array_keys(ManualResolutionScenario::RESOLUTION_LABELS)
        );
    }

    public function test_available_actions_cover_every_resolution_option(): void
    {
        $actionKeys = array_map(fn ($a) => $a->key, $this->scenario->availableActions());

        $this->assertEqualsCanonicalizing(array_keys(ManualResolutionScenario::RESOLUTION_LABELS), $actionKeys);
    }

    public function test_store_credit_is_the_only_service_executed_action(): void
    {
        $serviceActions = array_filter(
            $this->scenario->availableActions(),
            fn ($a) => $a->executionType === ScenarioAction::EXECUTION_SERVICE
        );

        $this->assertCount(1, $serviceActions);
        $this->assertSame(ManualResolutionScenario::RESOLUTION_STORE_CREDIT, array_values($serviceActions)[0]->key);
    }

    public function test_permission_requirements_reuse_existing_permissions_not_new_ones(): void
    {
        $permissions = $this->scenario->permissionRequirements();

        $this->assertContains('resolution_center.view', $permissions);
        $this->assertContains('resolution_center.use', $permissions);
        $this->assertContains('resolution_center.override', $permissions);
        $this->assertContains('resolution_center.view_audit_history', $permissions);
        $this->assertContains('customer_credit.grant', $permissions);
    }

    public function test_completion_criteria_matches_resolution_center_service_outcomes(): void
    {
        $this->assertSame(
            ['pending', 'completed', 'cancelled'],
            $this->scenario->completionCriteria()
        );
    }
}
