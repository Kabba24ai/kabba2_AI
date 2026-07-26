<?php

namespace Tests\Feature\Credit;

use App\Enums\AiRules\AiRuleAuthority;
use App\Enums\AiRules\AiRuleImplementationStatus;
use App\Models\Ai\AiRule;
use App\Models\Iam\Personnel\User;
use Database\Seeders\Credit\AiRuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AI Rules Repository — CRUD, governance (approval gate + edit-resets-approval),
 * authority boundary, and the seeded first rule.
 */
class AiRulesRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::create([
            'first_name' => 'Rules', 'last_name' => 'Admin',
            'email' => 'rules-admin@test.local', 'status' => 'Active',
        ]);
        $this->actingAs($this->admin);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'rule_key' => 'sample_rule',
            'rule_name' => 'Sample Rule',
            'business_area' => 'Credit / Billing',
            'purpose' => 'A test rule.',
            'authority' => AiRuleAuthority::RecommendOnly->value,
            'implementation_status' => AiRuleImplementationStatus::DefinedNotAutomated->value,
            'ai_may_recommend' => "contact customer\nrequest payment",
            'ai_may_execute' => '',
            'ai_must_not' => "write off debt",
            'is_active' => '1',
        ], $overrides);
    }

    public function test_index_renders(): void
    {
        $this->get(route('admin.crm.ai-rules.index'))
            ->assertOk()
            ->assertSee('AI Rules');
    }

    public function test_store_creates_a_pending_rule_with_parsed_lists(): void
    {
        $this->post(route('admin.crm.ai-rules.store'), $this->validPayload())
            ->assertRedirect(route('admin.crm.ai-rules.index'));

        $rule = AiRule::where('rule_key', 'sample_rule')->firstOrFail();
        $this->assertFalse($rule->approved_by_admin, 'new rules start pending');
        $this->assertSame(1, $rule->version);
        $this->assertSame($this->admin->id, $rule->created_by);
        $this->assertEquals(['contact customer', 'request payment'], $rule->ai_may_recommend);
        $this->assertEquals([], $rule->ai_may_execute, 'execute authority is never implied');
    }

    public function test_approve_marks_the_rule_canonical(): void
    {
        $this->post(route('admin.crm.ai-rules.store'), $this->validPayload());
        $rule = AiRule::where('rule_key', 'sample_rule')->firstOrFail();

        $this->post(route('admin.crm.ai-rules.approve', $rule->id))
            ->assertRedirect();

        $rule->refresh();
        $this->assertTrue($rule->approved_by_admin);
        $this->assertSame($this->admin->id, $rule->approved_by);
        $this->assertNotNull($rule->approved_at);
    }

    public function test_editing_an_approved_rule_resets_approval_and_bumps_version(): void
    {
        $this->post(route('admin.crm.ai-rules.store'), $this->validPayload());
        $rule = AiRule::where('rule_key', 'sample_rule')->firstOrFail();
        $this->post(route('admin.crm.ai-rules.approve', $rule->id));

        $this->put(route('admin.crm.ai-rules.update', $rule->id), $this->validPayload([
            'rule_name' => 'Sample Rule (edited)',
        ]));

        $rule->refresh();
        $this->assertSame('Sample Rule (edited)', $rule->rule_name);
        $this->assertFalse($rule->approved_by_admin, 'edit resets approval (anti-drift)');
        $this->assertNull($rule->approved_by);
        $this->assertSame(2, $rule->version);
        $this->assertSame($this->admin->id, $rule->updated_by);
        $this->assertNotEmpty($rule->change_log);
    }

    public function test_destroy_soft_deletes(): void
    {
        $this->post(route('admin.crm.ai-rules.store'), $this->validPayload());
        $rule = AiRule::where('rule_key', 'sample_rule')->firstOrFail();

        $this->delete(route('admin.crm.ai-rules.destroy', $rule->id))->assertRedirect();

        $this->assertSoftDeleted('ai_rules', ['id' => $rule->id]);
    }

    public function test_seeded_first_rule_is_the_approved_credit_threshold_rule(): void
    {
        (new AiRuleSeeder())->run();

        $rule = AiRule::where('rule_key', 'credit_threshold_exception_review')->firstOrFail();
        $this->assertSame('Credit Threshold Exception Review', $rule->rule_name);
        $this->assertTrue($rule->approved_by_admin);
        $this->assertEquals(AiRuleAuthority::RecommendOnly, $rule->authority);
        $this->assertEquals(AiRuleImplementationStatus::DefinedNotAutomated, $rule->implementation_status);
        $this->assertEquals([], $rule->ai_may_execute, 'the AI may not execute anything under this rule');
        $this->assertContains('make a binding credit decision', $rule->ai_must_not);
        $this->assertFalse($rule->mayExecute());

        // Idempotent.
        (new AiRuleSeeder())->run();
        $this->assertSame(1, AiRule::where('rule_key', 'credit_threshold_exception_review')->count());
    }
}
