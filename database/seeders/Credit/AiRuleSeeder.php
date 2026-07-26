<?php

namespace Database\Seeders\Credit;

use App\Enums\AiRules\AiRuleAuthority;
use App\Enums\AiRules\AiRuleImplementationStatus;
use App\Models\Ai\AiRule;
use Illuminate\Database\Seeder;

/**
 * Seeds the first approved rule into the canonical AI Rules Repository:
 * `credit_threshold_exception_review`. Idempotent by rule_key. Recorded now so
 * the future AI integration has an approved, auditable starting point — the AI
 * may assess and recommend, never execute.
 */
class AiRuleSeeder extends Seeder
{
    public function run(): void
    {
        AiRule::updateOrCreate(
            ['rule_key' => 'credit_threshold_exception_review'],
            [
                'rule_name'     => 'Credit Threshold Exception Review',
                'business_area' => 'Credit / Billing',
                'purpose'       => 'Ensure that customer credit-limit exceptions are recorded and reviewed by management without requiring counter staff to make credit-policy decisions.',
                'trigger_description' => 'A posted transaction causes the customer’s outstanding account balance to exceed the approved credit limit.',
                'evaluable_inputs' => [
                    'current balance', 'approved credit limit', 'amount over threshold',
                    'age of outstanding invoices', 'payment timeliness', 'payment history',
                    'prior threshold exceptions', 'prior credit-limit changes',
                    'broken payment commitments', 'returned payments', 'chargebacks',
                    'current open orders', 'upcoming exposure', 'recent growth in rental activity',
                    'unresolved account-review tasks',
                ],
                'deterministic_action' => "Allow the transaction. Record the threshold exception. Create or update the Credit Account Review task. Assign the task to the Primary Billing Admin. Preserve the customer, order, financial, and threshold context.",
                'ai_assessment_instruction' => 'In parallel with task creation, a future AI agent may assess whether the customer’s credit position appears to be regressing and prepare an evidence-based account assessment for the Billing Admin.',
                'ai_may_recommend' => [
                    'no change', 'credit-limit increase', 'request for payment', 'customer contact',
                    'temporary account review', 'suspension of future rentals',
                    'suspension of additional on-account transactions', 'escalation to owner or senior management',
                ],
                'ai_may_execute' => [], // none — assessment/recommendation only
                'ai_must_not' => [
                    'stop or cancel the triggering rental', 'reverse the transaction',
                    'change the credit limit', 'suspend the account', 'restore the account',
                    'blacklist the customer', 'place the customer on cash-only terms', 'write off debt',
                    'make a binding credit decision', 'communicate a final credit decision to the customer',
                ],
                'required_human_reviewer' => 'Primary Billing Admin',
                'escalation_destination'  => 'Owner / senior management (per future approved policy)',
                'authority'               => AiRuleAuthority::RecommendOnly->value,
                'implementation_status'   => AiRuleImplementationStatus::DefinedNotAutomated->value,
                'is_active'               => true,
                'version'                 => 1,
                'effective_date'          => now()->toDateString(),
                // Recorded as an approved starting point.
                'approved_by_admin'       => true,
                'approved_at'             => now(),
                'last_reviewed_at'        => now(),
            ],
        );
    }
}
