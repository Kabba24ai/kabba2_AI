<?php

namespace Tests\Unit\Services\ResolutionCenter;

use App\Enums\Orders\OrderPaymentMethod;
use App\Services\ResolutionCenter\CancellationRefundScenario;
use App\Services\ResolutionCenter\ResolutionDecisionEngine;
use App\Services\ResolutionCenter\ResolutionPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Phase 3.4 — Operational Knowledge Framework.
 *
 * Proves the refactor changed no behavior: for every branch
 * ResolutionDecisionEngineTest already covers (Phase 3.3), the new
 * CancellationRefundScenario wrapper — which internally delegates to that
 * same, untouched engine — must produce an identical recommendation.
 */
class CancellationRefundScenarioTest extends TestCase
{
    private CancellationRefundScenario $scenario;

    private ResolutionDecisionEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scenario = new CancellationRefundScenario;
        $this->engine = new ResolutionDecisionEngine;
    }

    public function test_key_and_label(): void
    {
        $this->assertSame('cancellation_refund', $this->scenario->key());
        $this->assertSame('Cancellation / Refund', $this->scenario->label());
    }

    public function test_returns_null_when_step_1_not_yet_answered(): void
    {
        $this->assertNull($this->scenario->recommend([]));
        $this->assertNull($this->scenario->recommend(['can_reschedule' => null]));
    }

    public function test_returns_null_when_step_2_not_yet_answered(): void
    {
        $this->assertNull($this->scenario->recommend(['can_reschedule' => false]));
    }

    public function test_returns_null_when_step_3_not_yet_answered(): void
    {
        $this->assertNull($this->scenario->recommend(['can_reschedule' => false, 'credit_would_satisfy' => false]));
    }

    #[DataProvider('terminalAnswerSets')]
    public function test_matches_the_untouched_engine_exactly(array $answers, bool $engineCanReschedule, ?bool $engineCreditWouldSatisfy, ?string $enginePaymentMethod): void
    {
        $viaScenario = $this->scenario->recommend($answers);
        $viaEngine = $this->engine->recommend($engineCanReschedule, $engineCreditWouldSatisfy, $enginePaymentMethod);

        $this->assertNotNull($viaScenario);
        $this->assertSame($viaEngine->options, $viaScenario->options);
        $this->assertSame($viaEngine->nextStep, $viaScenario->nextStep);
        $this->assertSame($viaEngine->requiresManagerOverride, $viaScenario->requiresManagerOverride);
    }

    public static function terminalAnswerSets(): array
    {
        return [
            'free reschedule' => [
                ['can_reschedule' => true], true, null, null,
            ],
            'issue store credit' => [
                ['can_reschedule' => false, 'credit_would_satisfy' => true], false, true, null,
            ],
            'card refund' => [
                ['can_reschedule' => false, 'credit_would_satisfy' => false, 'payment_method' => OrderPaymentMethod::Card->value],
                false, false, OrderPaymentMethod::Card->value,
            ],
            'cash refund' => [
                ['can_reschedule' => false, 'credit_would_satisfy' => false, 'payment_method' => OrderPaymentMethod::Cash->value],
                false, false, OrderPaymentMethod::Cash->value,
            ],
        ];
    }

    public function test_available_actions_cover_every_recommendation_key(): void
    {
        $actionKeys = array_map(fn ($a) => $a->key, $this->scenario->availableActions());

        $this->assertEqualsCanonicalizing(
            [
                ResolutionPolicy::RECOMMEND_FREE_RESCHEDULE,
                ResolutionPolicy::RECOMMEND_ISSUE_STORE_CREDIT,
                ResolutionPolicy::RECOMMEND_STANDARD_REFUND,
                ResolutionPolicy::RECOMMEND_WAIVE_REFUND_FEE,
            ],
            $actionKeys
        );
    }

    public function test_permission_requirements_reuse_existing_permissions_not_new_ones(): void
    {
        $permissions = $this->scenario->permissionRequirements();

        $this->assertContains('resolution_center.view', $permissions);
        $this->assertContains('resolution_center.use', $permissions);
        $this->assertContains('resolution_center.override', $permissions);
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
