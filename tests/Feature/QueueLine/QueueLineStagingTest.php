<?php

namespace Tests\Feature\QueueLine;

use App\Livewire\QueueLine\Board;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\OrderProduct;
use App\Models\Orders\QueueLineFuelVerification;
use App\Models\Orders\QueueLineKeyConfirmation;
use App\Services\Equipment\EquipmentReassignmentService;
use App\Services\QueueLine\QueueLineMobilePresenter;
use App\Services\QueueLine\QueueLineService;
use App\Services\QueueLine\QueueLineStagingService;
use Livewire\Livewire;

/**
 * Admin readiness modal (2026-07-20) — the one Thumbs Up staging workflow.
 * Gray = not staged (opens Mark as Staged), green = fully staged (opens
 * the status dialog). Staging is ONE atomic operation over the canonical
 * records mobile shares: fuel verification (QueueFuelVerificationService),
 * key confirmation (append-only sibling ledger), staged latch
 * (QueueLineService::stage). Nothing is admin-only.
 */
class QueueLineStagingTest extends QueueLineTestCase
{
    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employee = User::create([
            'first_name' => 'Yard', 'last_name' => 'Tech',
            'email' => 'yard-tech@test.local', 'status' => 'Active',
        ]);
    }

    private function sectionBlock(string $html, string $section): string
    {
        $start = strpos($html, 'data-queue-section="' . $section . '"');
        if ($start === false) {
            return '';
        }
        $end = strpos($html, 'data-queue-section="', $start + 1);

        return $end === false ? substr($html, $start) : substr($html, $start, $end - $start);
    }

    /** Drive the full modal flow through the component. */
    private function stageViaModal(OrderProduct $row, int $expectedEquipmentId)
    {
        return Livewire::test(Board::class)
            ->call('openStaging', $row->id)
            ->set('stagingPerformedBy', (string) $this->employee->id)
            ->set('stagingFuel', 'full')
            ->set('stagingKey', 'with_machine')
            ->call('confirmStaging', $expectedEquipmentId);
    }

    // ── Thumb states + modal opening ─────────────────────────────────────

    public function test_gray_thumb_renders_for_unstaged_and_opens_the_staging_modal(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row);

        $component = Livewire::test(Board::class);
        $this->assertSame(1, substr_count($component->html(), 'data-thumb="pending"'));
        $this->assertSame(0, substr_count($component->html(), 'data-thumb="staged"'));

        $component->call('openStaging', $row->id)
            ->assertSee('Mark as Staged')
            ->assertSee('Equipment being staged');
    }

    public function test_unassigned_card_shows_a_disabled_thumb_and_cannot_open_the_modal(): void
    {
        $row = $this->makeRow(); // no equipment

        $component = Livewire::test(Board::class);
        $this->assertSame(1, substr_count($component->html(), 'data-thumb="unavailable"'));

        // Even a forced call refuses without an assigned unit
        $component->call('openStaging', $row->id);
        $this->assertStringNotContainsString('data-staging-modal', $component->html());
    }

    // ── Modal validation ─────────────────────────────────────────────────

    public function test_employee_is_required(): void
    {
        $row = $this->makeRow();
        $unit = $this->softAssign($row);

        Livewire::test(Board::class)
            ->call('openStaging', $row->id)
            ->set('stagingFuel', 'full')
            ->set('stagingKey', 'with_machine')
            ->call('confirmStaging', $unit->id)
            ->assertSee('Select the employee');

        $this->assertDatabaseCount('queue_line_fuel_verifications', 0);
        $this->assertDatabaseCount('queue_line_key_confirmations', 0);
    }

    public function test_fuel_must_be_full_and_key_must_be_with_machine(): void
    {
        $row = $this->makeRow();
        $unit = $this->softAssign($row);

        // Fuel not full → rejected, nothing recorded
        Livewire::test(Board::class)
            ->call('openStaging', $row->id)
            ->set('stagingPerformedBy', (string) $this->employee->id)
            ->set('stagingFuel', 'not_full')
            ->set('stagingKey', 'with_machine')
            ->call('confirmStaging', $unit->id)
            ->assertSee('Fuel must be Full');

        // Key missing → rejected, nothing recorded
        Livewire::test(Board::class)
            ->call('openStaging', $row->id)
            ->set('stagingPerformedBy', (string) $this->employee->id)
            ->set('stagingFuel', 'full')
            ->set('stagingKey', 'missing')
            ->call('confirmStaging', $unit->id)
            ->assertSee('key must be with the machine');

        $this->assertDatabaseCount('queue_line_fuel_verifications', 0);
        $this->assertDatabaseCount('queue_line_key_confirmations', 0);
        $this->assertNull($row->fresh('queueLineItem')->queueLineItem?->staged_at);
    }

    public function test_the_submit_button_is_disabled_until_every_condition_is_met(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row);

        $html = Livewire::test(Board::class)
            ->call('openStaging', $row->id)
            ->html();

        // Rendered disabled with no selections
        $this->assertMatchesRegularExpression('/data-staging-submit[^>]*disabled|disabled[^>]*data-staging-submit/s', $html);
    }

    // ── Successful staging: atomic + canonical ───────────────────────────

    public function test_successful_staging_records_fuel_key_attribution_and_staged_state_atomically(): void
    {
        $row = $this->makeRow();
        $unit = $this->softAssign($row);

        $component = $this->stageViaModal($row, $unit->id);
        $component->assertSet('stagingItemId', null)
            ->assertSee('marked as staged');

        // Canonical fuel verification — dual attribution
        $fuel = QueueLineFuelVerification::sole();
        $this->assertSame('verified', $fuel->action);
        $this->assertEquals($this->employee->id, $fuel->performed_by);
        $this->assertEquals($this->admin->id, $fuel->created_by);
        $this->assertEquals($unit->id, $fuel->equipment_id);

        // Canonical key confirmation — same episode, same attribution
        $key = QueueLineKeyConfirmation::sole();
        $this->assertSame('confirmed', $key->action);
        $this->assertEquals($this->employee->id, $key->performed_by);
        $this->assertEquals($this->admin->id, $key->created_by);
        $this->assertEquals($fuel->equipment_soft_assign_id, $key->equipment_soft_assign_id);

        // Canonical staged latch
        $this->assertNotNull($row->fresh('queueLineItem')->queueLineItem->staged_at);

        // Card moved Pending → Staged; thumb is green
        $html = $component->html();
        $this->assertStringContainsString('data-order-product-id="' . $row->id . '"', $this->sectionBlock($html, 'ready'));
        $this->assertSame(1, substr_count($html, 'data-thumb="staged"'));
    }

    public function test_desktop_staging_and_mobile_read_the_same_canonical_fuel_record(): void
    {
        $row = $this->makeRow();
        $unit = $this->softAssign($row);
        $this->stageViaModal($row, $unit->id);

        // The mobile presenter reads the exact record the modal wrote
        $mobile = QueueLineMobilePresenter::item($row->fresh(['softAssignment.equipment', 'order', 'queueLineItem']));
        $this->assertSame('verified', $mobile['fuel']['state']);
        $this->assertSame($this->employee->full_name, $mobile['fuel']['verified_by']);
        $this->assertTrue($mobile['staged']);
    }

    public function test_duplicate_submissions_remain_idempotent(): void
    {
        $row = $this->makeRow();
        $unit = $this->softAssign($row);

        $this->stageViaModal($row, $unit->id);
        $stagedAt = $row->fresh('queueLineItem')->queueLineItem->staged_at;

        // Double-click / repeat submission
        $this->stageViaModal($row, $unit->id);

        $this->assertSame(1, QueueLineFuelVerification::count());
        $this->assertSame(1, QueueLineKeyConfirmation::count());
        $this->assertTrue($row->fresh('queueLineItem')->queueLineItem->staged_at->equalTo($stagedAt));
    }

    public function test_a_stale_assignment_is_rejected_and_nothing_partial_is_recorded(): void
    {
        $row = $this->makeRow();
        $original = $this->softAssign($row);

        // The assignment moves on after the modal loaded
        $replacement = $this->makeEquipment(['assigned_product_id' => $row->product_id]);
        EquipmentReassignmentService::switch($row->fresh(['softAssignment.equipment', 'order']), $replacement, $this->employee, $this->admin);

        $this->stageViaModal($row->fresh(), $original->id)
            ->assertSee('assignment changed');

        // All-or-nothing: no fuel row, no key row, no staged latch
        $this->assertSame(0, QueueLineFuelVerification::where('action', 'verified')->count());
        $this->assertSame(0, QueueLineKeyConfirmation::count());
        $this->assertNull($row->fresh('queueLineItem')->queueLineItem?->staged_at);
    }

    public function test_staging_after_the_item_left_queue_line_is_rejected(): void
    {
        $row = $this->makeRow();
        $unit = $this->softAssign($row);
        QueueLineService::complete($row, QueueLineService::VIA_DISPATCH_STARTED, $unit->id);

        $this->stageViaModal($row->fresh(['softAssignment.equipment', 'queueLineItem']), $unit->id)
            ->assertSee('already left');

        $this->assertSame(0, QueueLineKeyConfirmation::count());
    }

    // ── Green thumb: status dialog + Return to Pending ───────────────────

    public function test_green_thumb_opens_the_status_dialog_with_attribution_and_timestamps(): void
    {
        $row = $this->makeRow();
        $unit = $this->softAssign($row);
        $this->stageViaModal($row, $unit->id);

        Livewire::test(Board::class)
            ->call('openStagedStatus', $row->id)
            ->assertSee('Staged')
            ->assertSee($this->employee->first_name)   // Performed By
            ->assertSee($this->admin->first_name)      // Entered By
            ->assertSee('Full — Verified')
            ->assertSee('With Machine')
            ->assertSee('Return to Pending');
    }

    public function test_return_to_pending_restores_pending_and_preserves_all_history(): void
    {
        $row = $this->makeRow();
        $unit = $this->softAssign($row);
        $this->stageViaModal($row, $unit->id);

        $component = Livewire::test(Board::class)
            ->call('openStagedStatus', $row->id)
            ->call('returnToPending', $row->id)
            ->assertSet('statusItemId', null)
            ->assertSee('Returned to Pending');

        // Staged latch cleared; card back in Pending; thumb gray again
        $this->assertNull($row->fresh('queueLineItem')->queueLineItem->staged_at);
        $html = $component->html();
        $this->assertStringContainsString('data-order-product-id="' . $row->id . '"', $this->sectionBlock($html, 'pending'));
        $this->assertSame(1, substr_count($html, 'data-thumb="pending"'));

        // History preserved, append-only: original rows + reversal rows
        $this->assertSame(2, QueueLineFuelVerification::count());   // verified + reversed
        $this->assertSame(2, QueueLineKeyConfirmation::count());    // confirmed + reversed
        $this->assertSame(1, QueueLineFuelVerification::where('action', 'verified')->count());
        $this->assertSame(1, QueueLineKeyConfirmation::where('action', 'reversed')
            ->where('reason', QueueLineStagingService::RETURN_REASON)->count());

        // And the item can be fully staged again afterwards
        $this->stageViaModal($row->fresh(['softAssignment.equipment', 'queueLineItem']), $unit->id);
        $this->assertNotNull($row->fresh('queueLineItem')->queueLineItem->staged_at);
        $this->assertSame(3, QueueLineFuelVerification::count());   // + fresh verification
    }

    // ── Lifecycle exits (unchanged — pinned here for the record) ─────────

    public function test_fully_staged_item_still_leaves_at_dispatch_start(): void
    {
        $row = $this->makeRow();
        $unit = $this->softAssign($row);
        $this->stageViaModal($row, $unit->id);

        // "Start Delivery" — the dispatch_started completion latch
        QueueLineService::complete($row->fresh('queueLineItem'), QueueLineService::VIA_DISPATCH_STARTED, $unit->id);

        $html = Livewire::test(Board::class)->html();
        $this->assertSame('', $this->sectionBlock($html, 'ready'));
        // Present only as the read-only Delivered Today reference card
        $this->assertSame(1, substr_count($html, 'data-delivered="1"'));
    }
}
