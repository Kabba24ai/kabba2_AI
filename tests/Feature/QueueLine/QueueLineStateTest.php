<?php

namespace Tests\Feature\QueueLine;

use App\Models\Orders\QueueLineItem;
use App\Services\QueueLine\QueueLineService;
use Illuminate\Support\Carbon;

/**
 * The queue_line_items sidecar: lazy row creation, null-latch idempotency,
 * RUSH, self-expiring Remove Today, persistent-but-reversible Remove Forever.
 */
class QueueLineStateTest extends QueueLineTestCase
{
    public function test_default_state_is_not_staged_with_no_sidecar_row(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row);

        $this->assertDatabaseCount('queue_line_items', 0);
        $this->assertContains($row->id, $this->boardIds()); // eligible without a row
    }

    public function test_stage_creates_one_row_and_is_idempotent(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row);
        $row->refresh();

        $item = QueueLineService::stage($row, $this->admin);
        $firstStagedAt = $item->staged_at;

        $this->assertNotNull($firstStagedAt);
        $this->assertEquals($this->admin->id, $item->staged_by);

        // Duplicate action: same row, original stamp preserved (null-latch)
        Carbon::setTestNow(now()->addMinutes(10));
        QueueLineService::stage($row, $this->admin);
        Carbon::setTestNow();

        $this->assertDatabaseCount('queue_line_items', 1);
        $this->assertTrue($item->fresh()->staged_at->equalTo($firstStagedAt));
    }

    public function test_stage_requires_assigned_equipment(): void
    {
        $row = $this->makeRow(); // no soft assignment

        $this->expectException(\InvalidArgumentException::class);
        QueueLineService::stage($row, $this->admin);
    }

    public function test_unstage_returns_to_not_staged(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row);
        $row->refresh();

        QueueLineService::stage($row, $this->admin);
        $item = QueueLineService::unstage($row, $this->admin);

        $this->assertNull($item->staged_at);
        $this->assertNull($item->staged_by);
        $this->assertDatabaseCount('queue_line_items', 1);
        $this->assertContains($row->id, $this->boardIds()); // still on the board
    }

    public function test_rush_on_off_is_item_specific_with_attribution(): void
    {
        $order = $this->makeOrder();
        $a = $this->makeRow($order);
        $b = $this->makeRow($order);

        $item = QueueLineService::rush($a, $this->admin);

        $this->assertNotNull($item->rush_at);
        $this->assertEquals($this->admin->id, $item->rush_by);
        // Sibling untouched — RUSH never spreads across the order
        $this->assertNull(QueueLineItem::where('order_product_id', $b->id)->first());

        $item = QueueLineService::unrush($a, $this->admin);
        $this->assertNull($item->rush_at);
        $this->assertNull($item->rush_by);
        $this->assertDatabaseCount('queue_line_items', 1);
    }

    public function test_remove_today_hides_now_and_reappears_next_operational_day(): void
    {
        $row = $this->makeRow(null, ['delivery_date' => now()->subDay()->format('Y-m-d')]); // overdue

        QueueLineService::removeToday($row, $this->admin);
        $this->assertNotContains($row->id, $this->boardIds());

        // The operational day changes — the suppression stops matching, no
        // scheduler involved. The overdue item is eligible again.
        Carbon::setTestNow(now()->addDay()->startOfDay()->addHours(6));
        $this->assertContains($row->id, $this->boardIds());
        Carbon::setTestNow();
    }

    public function test_remove_forever_survives_date_changes_and_is_restorable(): void
    {
        $row = $this->makeRow();
        QueueLineService::removeForever($row, $this->admin);

        $this->assertNotContains($row->id, $this->boardIds());

        // Reschedule the delivery — suppression must hold
        $row->update(['delivery_date' => now()->addDay()->format('Y-m-d')]);
        $this->assertNotContains($row->id, $this->boardIds());

        // …and across day boundaries
        Carbon::setTestNow(now()->addDay());
        $this->assertNotContains($row->id, $this->boardIds());
        Carbon::setTestNow();

        // Deliberate restore brings it back
        QueueLineService::restore($row, $this->admin);
        $this->assertContains($row->id, $this->boardIds());
    }

    public function test_deleting_and_recreating_the_item_invalidates_old_suppression(): void
    {
        $order = $this->makeOrder();
        $row = $this->makeRow($order);
        QueueLineService::removeForever($row, $this->admin);

        $row->delete(); // item removed from the order

        // Recreated as a NEW order_product — new id, no suppression attached
        $recreated = $this->makeRow($order);

        $this->assertContains($recreated->id, $this->boardIds());
        $this->assertNotEquals($row->id, $recreated->id);
    }

    public function test_duplicate_actions_never_create_duplicate_rows(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row);
        $row->refresh();

        QueueLineService::rush($row, $this->admin);
        QueueLineService::rush($row, $this->admin);
        QueueLineService::stage($row, $this->admin);
        QueueLineService::removeToday($row, $this->admin);
        QueueLineService::removeToday($row, $this->admin);
        QueueLineService::removeForever($row, $this->admin);
        QueueLineService::restore($row, $this->admin);

        $this->assertDatabaseCount('queue_line_items', 1);
    }

    public function test_rush_history_remains_when_item_leaves_eligibility(): void
    {
        $row = $this->makeRow();
        QueueLineService::rush($row, $this->admin);

        // Item leaves the active window entirely — RUSH becomes operationally
        // irrelevant (not on the board) but the sidecar history remains.
        $row->update(['delivery_date' => now()->addDays(10)->format('Y-m-d')]);

        $this->assertNotContains($row->id, $this->boardIds());
        $this->assertNotNull(QueueLineItem::where('order_product_id', $row->id)->first()->rush_at);
    }
}
