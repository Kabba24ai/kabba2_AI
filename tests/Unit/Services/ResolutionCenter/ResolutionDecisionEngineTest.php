<?php

namespace Tests\Unit\Services\ResolutionCenter;

use App\Enums\Orders\OrderPaymentMethod;
use App\Services\ResolutionCenter\ResolutionDecisionEngine;
use App\Services\ResolutionCenter\ResolutionPolicy;
use PHPUnit\Framework\TestCase;

/**
 * ResolutionDecisionEngine is pure — no database access, no side effects —
 * so every branch of Phase 3.3's mandated decision tree is tested here
 * directly, with no rolled-back-transaction workaround needed (unlike most
 * of this initiative's database-touching services).
 */
class ResolutionDecisionEngineTest extends TestCase
{
    private ResolutionDecisionEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new ResolutionDecisionEngine;
    }

    public function test_step_1_yes_recommends_free_reschedule_and_stops(): void
    {
        $recommendation = $this->engine->recommend(canReschedule: true, creditWouldSatisfy: null, paymentMethod: null);

        $this->assertSame([ResolutionPolicy::RECOMMEND_FREE_RESCHEDULE], $recommendation->options);
        $this->assertFalse($recommendation->requiresManagerOverride);
    }

    public function test_step_2_yes_recommends_issue_store_credit_and_stops(): void
    {
        $recommendation = $this->engine->recommend(canReschedule: false, creditWouldSatisfy: true, paymentMethod: null);

        $this->assertSame([ResolutionPolicy::RECOMMEND_ISSUE_STORE_CREDIT], $recommendation->options);
    }

    public function test_step_3_credit_card_offers_standard_refund_and_waive_fee(): void
    {
        $recommendation = $this->engine->recommend(canReschedule: false, creditWouldSatisfy: false, paymentMethod: OrderPaymentMethod::Card->value);

        $this->assertSame(
            [ResolutionPolicy::RECOMMEND_STANDARD_REFUND, ResolutionPolicy::RECOMMEND_WAIVE_REFUND_FEE],
            $recommendation->options
        );
    }

    public function test_step_3_cash_recommends_standard_refund_only(): void
    {
        $recommendation = $this->engine->recommend(canReschedule: false, creditWouldSatisfy: false, paymentMethod: OrderPaymentMethod::Cash->value);

        $this->assertSame([ResolutionPolicy::RECOMMEND_STANDARD_REFUND], $recommendation->options);
    }

    public function test_step_3_cheque_recommends_standard_refund_only(): void
    {
        $recommendation = $this->engine->recommend(canReschedule: false, creditWouldSatisfy: false, paymentMethod: OrderPaymentMethod::Cheque->value);

        $this->assertSame([ResolutionPolicy::RECOMMEND_STANDARD_REFUND], $recommendation->options);
    }

    public function test_reschedule_takes_priority_over_credit_even_when_credit_would_also_satisfy(): void
    {
        // Reschedule-first policy: canReschedule=true always wins, regardless
        // of what creditWouldSatisfy would have been.
        $recommendation = $this->engine->recommend(canReschedule: true, creditWouldSatisfy: true, paymentMethod: OrderPaymentMethod::Card->value);

        $this->assertSame([ResolutionPolicy::RECOMMEND_FREE_RESCHEDULE], $recommendation->options);
    }

    public function test_credit_takes_priority_over_refund_even_for_a_card_payment(): void
    {
        // Store-Credit-second policy: creditWouldSatisfy=true wins over any
        // refund branch, regardless of payment method.
        $recommendation = $this->engine->recommend(canReschedule: false, creditWouldSatisfy: true, paymentMethod: OrderPaymentMethod::Card->value);

        $this->assertSame([ResolutionPolicy::RECOMMEND_ISSUE_STORE_CREDIT], $recommendation->options);
    }

    public function test_next_step_text_never_claims_automatic_execution(): void
    {
        foreach (
            [
                [true, null, null],
                [false, true, null],
                [false, false, OrderPaymentMethod::Card->value],
                [false, false, OrderPaymentMethod::Cash->value],
            ] as [$canReschedule, $creditWouldSatisfy, $paymentMethod]
        ) {
            $recommendation = $this->engine->recommend($canReschedule, $creditWouldSatisfy, $paymentMethod);

            $this->assertStringNotContainsStringIgnoringCase('automatically', $recommendation->nextStep);
        }
    }
}
