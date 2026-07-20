<?php

namespace Tests\Feature\QueueLine;

use App\Livewire\QueueLine\Board;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\QueueLineFuelVerification;
use App\Services\Equipment\EquipmentReassignmentService;
use App\Services\QueueLine\QueueFuelVerificationService;
use App\Services\QueueLine\QueueLineService;
use Livewire\Livewire;

/**
 * Phase 3B — Queue Fuel Verification: append-only, dual-attributed,
 * EPISODE-BOUND. A verification belongs to the physical unit through the
 * live soft-assignment row; switching equipment (even back to the original
 * unit) starts a new episode and never revives an old sign-off.
 */
class QueueLineFuelVerificationTest extends QueueLineTestCase
{
    protected User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employee = User::create([
            'first_name' => 'Fuel', 'last_name' => 'Tech',
            'email' => 'fuel-tech@test.local', 'status' => 'Active',
        ]);
    }

    private function verify($row, $expected, array $overrides = []): array
    {
        return QueueFuelVerificationService::verify(
            orderProduct: $row->fresh(['softAssignment.equipment', 'order', 'queueLineItem']),
            expected: $expected,
            performedBy: $overrides['performedBy'] ?? $this->employee,
            actor: $this->admin,
            source: $overrides['source'] ?? QueueLineFuelVerification::SOURCE_WEB,
            idempotencyToken: $overrides['token'] ?? null,
        );
    }

    // ── Verification ─────────────────────────────────────────────────────

    public function test_assigned_equipment_verifies_full_with_complete_attribution(): void
    {
        $row = $this->makeRow();
        $unit = $this->softAssign($row);

        $result = $this->verify($row, $unit);

        $this->assertFalse($result['replayed']);
        $v = $result['verification'];
        $this->assertEquals($row->id, $v->order_product_id);
        $this->assertEquals($row->order_id, $v->order_id);
        $this->assertEquals($unit->id, $v->equipment_id);
        $this->assertEquals($row->fresh()->softAssignment->id, $v->equipment_soft_assign_id);
        $this->assertEquals($this->employee->id, $v->performed_by);
        $this->assertEquals($this->admin->id, $v->created_by);
        $this->assertSame('queue_line_web', $v->source);
        $this->assertSame('verified', $v->action);

        $this->assertNotNull(QueueFuelVerificationService::currentVerification($row->fresh(['softAssignment'])));
    }

    public function test_unassigned_ineligible_and_delivered_items_cannot_verify(): void
    {
        // Needs Equipment Assignment row
        $unassigned = $this->makeRow();
        try {
            $this->verify($unassigned, $this->makeEquipment());
            $this->fail('Unassigned item must not verify.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('No equipment is assigned', $e->getMessage());
        }

        // Outside the Queue Line window (due in 10 days)
        $ineligible = $this->makeRow(null, ['delivery_date' => now()->addDays(10)->format('Y-m-d')]);
        $unit = $this->softAssign($ineligible);
        try {
            $this->verify($ineligible, $unit);
            $this->fail('Ineligible item must not verify.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('not currently on the Queue Line', $e->getMessage());
        }

        // Delivered / hard-assigned
        $delivered = $this->makeRow(null, ['delivery_status' => 'Completed', 'is_delivered' => true]);
        $hardUnit = $this->makeEquipment();
        $delivered->update(['equipment_id' => $hardUnit->id]);
        try {
            $this->verify($delivered, $hardUnit);
            $this->fail('Delivered item must not verify.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('already been delivered', $e->getMessage());
        }

        $this->assertSame(0, QueueLineFuelVerification::count());
    }

    public function test_stale_equipment_submission_is_rejected_with_a_clear_message(): void
    {
        $row = $this->makeRow();
        $original = $this->softAssign($row); // screen loaded showing this unit

        // Assignment changes after the screen loaded
        $replacement = $this->makeEquipment(['assigned_product_id' => $row->product_id]);
        EquipmentReassignmentService::switch(
            $row->fresh(['softAssignment.equipment', 'order']), $replacement, $this->employee, $this->admin,
        );

        try {
            $this->verify($row, $original); // stale submission for the OLD unit
            $this->fail('Stale submission must be rejected.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('assignment changed', $e->getMessage());
            $this->assertStringContainsString($replacement->equipment_name, $e->getMessage());
        }

        $this->assertSame(0, QueueLineFuelVerification::count());
    }

    // ── Assignment-episode integration (the critical requirement) ────────

    public function test_switching_equipment_invalidates_current_but_preserves_history(): void
    {
        $row = $this->makeRow();
        $abc = $this->softAssign($row);
        $this->verify($row, $abc);

        $xyz = $this->makeEquipment(['assigned_product_id' => $row->product_id]);
        EquipmentReassignmentService::switch(
            $row->fresh(['softAssignment.equipment', 'order']), $xyz, $this->employee, $this->admin,
        );

        $fresh = $row->fresh(['softAssignment']);
        // XYZ requires its own verification — ABC's never transfers
        $this->assertNull(QueueFuelVerificationService::currentVerification($fresh));
        // …but the ABC event remains in append-only history
        $history = QueueFuelVerificationService::history($fresh);
        $this->assertCount(1, $history);
        $this->assertEquals($abc->id, $history->first()->equipment_id);

        // XYZ can be verified normally
        $this->verify($row, $xyz);
        $this->assertEquals($xyz->id, QueueFuelVerificationService::currentVerification($row->fresh(['softAssignment']))->equipment_id);
    }

    public function test_switching_back_to_the_original_unit_never_revives_the_old_verification(): void
    {
        $row = $this->makeRow();
        $abc = $this->softAssign($row);
        $this->verify($row, $abc);                     // episode 1: ABC verified

        $xyz = $this->makeEquipment(['assigned_product_id' => $row->product_id]);
        EquipmentReassignmentService::switch($row->fresh(['softAssignment.equipment', 'order']), $xyz, $this->employee, $this->admin);
        EquipmentReassignmentService::switch($row->fresh(['softAssignment.equipment', 'order']), $abc, $this->employee, $this->admin); // back to ABC — episode 3

        $fresh = $row->fresh(['softAssignment']);
        // Same order_product + same equipment id as the old sign-off — but a
        // NEW soft-assign episode: the old verification must stay historical
        $this->assertEquals($abc->id, $fresh->softAssignment->equipment_id);
        $this->assertNull(QueueFuelVerificationService::currentVerification($fresh));

        // Reverification of the returned unit succeeds as a NEW event
        $this->verify($row, $abc);
        $this->assertNotNull(QueueFuelVerificationService::currentVerification($row->fresh(['softAssignment'])));
        $this->assertSame(2, QueueLineFuelVerification::where('action', 'verified')->count());
    }

    public function test_classification_never_alters_the_equipment_bound_rule(): void
    {
        // Alternate and unknown units verify exactly like direct ones —
        // the fuel rule binds to the physical unit, not the mapping
        $row = $this->makeRow();
        $unknown = $this->makeEquipment(['assigned_product_id' => null]);
        $this->softAssign($row, $unknown);

        $result = $this->verify($row, $unknown);
        $this->assertFalse($result['replayed']);

        $row2 = $this->makeRow();
        $otherProduct = \App\Models\ProductManagement\Product::create([
            'product_name' => 'Alt 56', 'slug' => 'alt56-' . uniqid(), 'product_type' => 'Rental',
        ]);
        $alternate = $this->makeEquipment(['assigned_product_id' => $otherProduct->id]);
        $this->softAssign($row2, $alternate);

        $this->assertFalse($this->verify($row2, $alternate)['replayed']);
    }

    // ── Reversal ─────────────────────────────────────────────────────────

    public function test_reversal_requires_a_reason_appends_history_and_allows_reverification(): void
    {
        $row = $this->makeRow();
        $unit = $this->softAssign($row);
        $verification = $this->verify($row, $unit)['verification'];

        // Reason required
        try {
            QueueFuelVerificationService::reverse($verification, $this->employee, $this->admin, ' ');
            $this->fail('Reversal without a reason must be rejected.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('reason', $e->getMessage());
        }

        $result = QueueFuelVerificationService::reverse(
            $verification, $this->employee, $this->admin, 'verified the wrong machine',
        );

        $this->assertFalse($result['replayed']);
        // Original row untouched, reversal appended
        $this->assertSame('verified', $verification->fresh()->action);
        $this->assertSame(2, QueueLineFuelVerification::count());
        $this->assertEquals($verification->id, $result['reversal']->reversed_verification_id);
        $this->assertSame('verified the wrong machine', $result['reversal']->reason);

        // Current state falls back to Not Verified…
        $this->assertNull(QueueFuelVerificationService::currentVerification($row->fresh(['softAssignment'])));

        // …and the same unit can be verified again (legitimate reverification)
        $again = $this->verify($row, $unit);
        $this->assertFalse($again['replayed']);
        $this->assertNotNull(QueueFuelVerificationService::currentVerification($row->fresh(['softAssignment'])));
        $this->assertSame(3, QueueLineFuelVerification::count());
    }

    // ── Idempotency & concurrency ────────────────────────────────────────

    public function test_duplicate_verify_produces_one_effective_verification(): void
    {
        $row = $this->makeRow();
        $unit = $this->softAssign($row);

        $first = $this->verify($row, $unit);
        $second = $this->verify($row, $unit); // double-click / Livewire retry

        $this->assertFalse($first['replayed']);
        $this->assertTrue($second['replayed']);
        $this->assertEquals($first['verification']->id, $second['verification']->id);
        $this->assertSame(1, QueueLineFuelVerification::count());
    }

    public function test_mobile_style_idempotency_token_replays_safely(): void
    {
        $row = $this->makeRow();
        $unit = $this->softAssign($row);

        $first = $this->verify($row, $unit, ['token' => 'mob-abc-123']);
        // Even after the assignment CHANGES, the same token replays the
        // original event instead of failing or double-writing
        $xyz = $this->makeEquipment(['assigned_product_id' => $row->product_id]);
        EquipmentReassignmentService::switch($row->fresh(['softAssignment.equipment', 'order']), $xyz, $this->employee, $this->admin);
        $replay = $this->verify($row, $unit, ['token' => 'mob-abc-123']);

        $this->assertTrue($replay['replayed']);
        $this->assertEquals($first['verification']->id, $replay['verification']->id);
        $this->assertSame(1, QueueLineFuelVerification::count());
    }

    public function test_duplicate_reversal_replays_the_original_reversal(): void
    {
        $row = $this->makeRow();
        $unit = $this->softAssign($row);
        $verification = $this->verify($row, $unit)['verification'];

        $first = QueueFuelVerificationService::reverse($verification, $this->employee, $this->admin, 'wrong machine');
        $second = QueueFuelVerificationService::reverse($verification, $this->employee, $this->admin, 'wrong machine again');

        $this->assertFalse($first['replayed']);
        $this->assertTrue($second['replayed']);
        $this->assertEquals($first['reversal']->id, $second['reversal']->id);
        $this->assertSame(2, QueueLineFuelVerification::count()); // verify + ONE reversal
    }

    // ── Presentation (standard + wall-board) ─────────────────────────────

    /**
     * UI Reset (2026-07-20): cards no longer carry fuel chips — fuel
     * currency is expressed by WORKFLOW SECTION membership (verified →
     * Staged, otherwise Pending). This helper slices the rendered board
     * into its section blocks so tests can assert which section a card
     * landed in.
     */
    private function sectionBlock(string $html, string $section): string
    {
        $start = strpos($html, 'data-queue-section="' . $section . '"');
        if ($start === false) {
            return '';
        }
        $end = strpos($html, 'data-queue-section="', $start + 1);

        return $end === false ? substr($html, $start) : substr($html, $start, $end - $start);
    }

    public function test_fuel_state_places_cards_in_the_correct_workflow_section(): void
    {
        $verified = $this->makeRow();
        $unit = $this->softAssign($verified);
        $this->verify($verified, $unit);

        $notVerified = $this->makeRow();
        $this->softAssign($notVerified);

        foreach ([[], ['wallboard' => true]] as $params) {
            $html = Livewire::test(Board::class, $params)->html();
            $this->assertStringContainsString(
                'data-order-product-id="' . $verified->id . '"',
                $this->sectionBlock($html, 'ready'),
                'the fuel-verified card must render in the Staged section',
            );
            $this->assertStringContainsString(
                'data-order-product-id="' . $notVerified->id . '"',
                $this->sectionBlock($html, 'pending'),
                'the unverified card must render in the Pending section',
            );
        }
    }

    public function test_historical_verification_on_prior_equipment_never_displays_as_current(): void
    {
        $row = $this->makeRow();
        $abc = $this->softAssign($row);
        $this->verify($row, $abc);

        $xyz = $this->makeEquipment(['assigned_product_id' => $row->product_id, 'equipment_name' => 'Unit XYZ']);
        EquipmentReassignmentService::switch($row->fresh(['softAssignment.equipment', 'order']), $xyz, $this->employee, $this->admin);

        $html = Livewire::test(Board::class)->html();
        // The replacement's episode has no verification → back to Pending
        $this->assertStringContainsString('data-order-product-id="' . $row->id . '"', $this->sectionBlock($html, 'pending'));
        $this->assertSame('', $this->sectionBlock($html, 'ready'), 'no card may present as fuel-verified');
    }

    public function test_livewire_verify_flow_updates_the_board_and_preserves_the_store_filter(): void
    {
        $row = $this->makeRow();
        $unit = $this->softAssign($row);

        $component = Livewire::test(Board::class)
            ->set('store', (string) $this->storeNorth->id)
            ->call('openFuelVerify', $row->id)
            ->assertSee('Verify Fuel Full')
            ->assertSee($unit->equipment_id) // exact unit shown before confirming
            // employee not selected → held with error, nothing written
            ->call('confirmFuelVerify', $unit->id)
            ->assertSee('Select yourself')
            ->set('fuelPerformedBy', (string) $this->employee->id)
            ->call('confirmFuelVerify', $unit->id)
            ->assertSet('fuelItemId', null)
            ->assertSee('Fuel Full verified for')
            ->assertSet('store', (string) $this->storeNorth->id);

        // Verified → the card now lives in the Staged section
        $this->assertStringContainsString(
            'data-order-product-id="' . $row->id . '"',
            $this->sectionBlock($component->html(), 'ready'),
        );
        $this->assertSame(1, QueueLineFuelVerification::count());
    }

    public function test_livewire_reverse_flow_requires_reason_and_returns_card_to_pending(): void
    {
        $row = $this->makeRow();
        $unit = $this->softAssign($row);
        $verification = $this->verify($row, $unit)['verification'];

        $component = Livewire::test(Board::class)
            ->call('openFuelReverse', $row->id)
            ->assertSee('Reverse Fuel Verification')
            ->set('fuelPerformedBy', (string) $this->employee->id)
            ->call('confirmFuelReverse', $verification->id) // no reason
            ->assertSee('reason is required')
            ->set('fuelReason', 'verified the wrong machine')
            ->call('confirmFuelReverse', $verification->id)
            ->assertSee('Fuel verification reversed');

        // Reversed → back to Pending; nothing presents as verified
        $this->assertStringContainsString(
            'data-order-product-id="' . $row->id . '"',
            $this->sectionBlock($component->html(), 'pending'),
        );
        $this->assertSame(2, QueueLineFuelVerification::count());
    }

    public function test_fuel_history_lists_events_with_full_attribution(): void
    {
        $row = $this->makeRow();
        $unit = $this->softAssign($row);
        $verification = $this->verify($row, $unit)['verification'];
        QueueFuelVerificationService::reverse($verification, $this->employee, $this->admin, 'wrong machine');

        Livewire::test(Board::class)
            ->call('openFuelVerify', $row->id)
            ->assertSee('Fuel History')
            ->assertSee('Reversed')
            ->assertSee('wrong machine')
            ->assertSee('queue_line_web')
            ->assertSee($unit->equipment_id);
    }

    public function test_guest_cannot_reach_the_board(): void
    {
        auth()->logout();
        $this->get(route('admin.order-management.queue-line.index'))->assertRedirect();
        $this->get(route('admin.order-management.queue-line.wallboard'))->assertRedirect();
    }
}
