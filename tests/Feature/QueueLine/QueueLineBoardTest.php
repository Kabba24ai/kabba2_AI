<?php

namespace Tests\Feature\QueueLine;

use App\Livewire\QueueLine\Board;
use App\Services\QueueLine\QueueLineService;
use Livewire\Livewire;

/**
 * The board page, Livewire component actions, grouping, filtering,
 * Needs Equipment rows, navigation, and presentation data.
 */
class QueueLineBoardTest extends QueueLineTestCase
{
    // ── Page + navigation ────────────────────────────────────────────────

    public function test_page_renders_for_authenticated_admin(): void
    {
        $this->get(route('admin.order-management.queue-line.index'))
            ->assertOk()
            ->assertSee('Queue Line')
            ->assertSeeLivewire(Board::class);
    }

    public function test_guest_is_redirected(): void
    {
        auth()->logout();

        $this->get(route('admin.order-management.queue-line.index'))->assertRedirect();
    }

    public function test_sidebar_places_queue_line_between_schedule_and_dispatch(): void
    {
        $html = $this->get(route('admin.order-management.queue-line.index'))->getContent();

        $schedulePos = strpos($html, route('admin.order-management.schedules.index'));
        $queuePos = strpos($html, route('admin.order-management.queue-line.index'));
        $dispatchPos = strpos($html, route('admin.order-management.dispatch.index'));

        $this->assertNotFalse($schedulePos);
        $this->assertNotFalse($queuePos);
        $this->assertNotFalse($dispatchPos);
        $this->assertStringContainsString('Queue Line', $html);
        $this->assertTrue($schedulePos < $queuePos && $queuePos < $dispatchPos,
            'Sidebar order must be Schedule -> Queue Line -> Dispatch');
    }

    // ── Board content ────────────────────────────────────────────────────

    public function test_every_card_is_standalone_with_its_own_order_context(): void
    {
        // UI Iteration 1: no order grouping — each card carries Order ID,
        // customer, and payment status itself (a technician never needs to
        // look outside the card).
        $order = $this->makeOrder(['customer_name' => 'Grouped Customer']);
        $a = $this->makeRow($order);
        $b = $this->makeRow($order);
        $this->softAssign($a);
        $this->softAssign($b);

        Livewire::test(Board::class)
            ->assertSee('ID: ' . $order->order_number)
            ->assertSee('Grouped Customer')
            ->assertSee(route('admin.order-management.orders.edit', $order->unique_id));

        $html = Livewire::test(Board::class)->html();
        $this->assertSame(2, substr_count($html, 'ID: ' . $order->order_number));
        $this->assertSame(2, substr_count($html, 'data-queue-row="card"'));
        $this->assertSame(2, substr_count($html, 'title="Grouped Customer"'));
    }

    public function test_unassigned_item_renders_the_uniform_card_with_no_workflow_actions(): void
    {
        $row = $this->makeRow(); // eligible, no soft assignment

        $component = Livewire::test(Board::class)
            ->assertSee($row->product_name);

        $html = $component->html();
        // Same card in every state — the equipment section adapts instead.
        // Refinement 2026-07-20: NO banner for the unassigned state —
        // selecting a machine is normal pull-list work, not an exception.
        $this->assertSame(1, substr_count($html, 'data-queue-row="card"'));
        $this->assertSame(1, substr_count($html, 'data-assignment="unassigned"'));
        $this->assertStringNotContainsString('Needs Equipment Assignment', $html);
        $this->assertStringContainsString('No equipment selected', $html);
        // UI Reset: the board is an information display — assignment/fuel/
        // history/stage workflows never render on the card
        $this->assertStringNotContainsString('Assign Equipment', $html);
        $this->assertStringNotContainsString('Verify Fuel', $html);
        $this->assertStringNotContainsString('History', $html);
        $this->assertStringNotContainsString('wire:click="stage(', $html);
        $this->assertStringNotContainsString('wire:click="openSwitch(', $html);
        $this->assertStringNotContainsString('wire:click="openFuelVerify(', $html);
        $this->assertStringNotContainsString('wire:click="openHistory(', $html);
    }

    public function test_unassigned_card_gains_equipment_section_once_soft_assigned(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row);

        $html = Livewire::test(Board::class)->html();
        $this->assertSame(1, substr_count($html, 'data-queue-row="card"'));
        $this->assertSame(0, substr_count($html, 'data-assignment="unassigned"'));
        $this->assertSame(1, substr_count($html, 'data-assignment="direct"'));
    }

    public function test_urgency_still_orders_cards_inside_the_section(): void
    {
        // UI Reset: urgency badges left the card (calm information display),
        // but sortItems ordering inside the section is unchanged:
        // Overdue → Today → Tomorrow.
        $overdue = $this->makeRow(null, ['delivery_date' => now()->subDay()->format('Y-m-d')]);
        $today = $this->makeRow();
        $tomorrow = $this->makeRow(null, ['delivery_date' => now()->addDay()->format('Y-m-d')]);

        Livewire::test(Board::class)
            ->assertSee('Queue Line — Pending')
            ->assertSeeInOrder([
                'ID: ' . $overdue->order->order_number,
                'ID: ' . $today->order->order_number,
                'ID: ' . $tomorrow->order->order_number,
            ]);
    }

    public function test_rushed_item_gets_the_rush_marker_and_sorts_first(): void
    {
        $other = $this->makeRow(null, ['delivery_date' => now()->subDay()->format('Y-m-d')]);
        $row = $this->makeRow(null, ['delivery_date' => now()->addDay()->format('Y-m-d')]);
        QueueLineService::rush($row, $this->admin);

        // A rushed tomorrow item outranks an overdue one inside the section
        Livewire::test(Board::class)
            ->assertSee('RUSH')
            ->assertSeeInOrder(['ID: ' . $row->order->order_number, 'ID: ' . $other->order->order_number]);
    }

    public function test_empty_state_renders_when_nothing_is_eligible(): void
    {
        // Lifecycle pipeline: every segment still renders, each with its
        // own placeholder — the board always reads as the three stages.
        Livewire::test(Board::class)
            ->assertSee('No orders waiting')
            ->assertSee('Nothing staged yet')
            ->assertSee('Nothing has left the yard yet today')
            ->assertSee('Queue Line — Pending')
            ->assertSee('Queue Line — Staged')
            ->assertSee('Queue Line — Completed');
    }

    // ── Actions through the component ────────────────────────────────────

    public function test_stage_and_unstage_actions_still_work_through_the_component(): void
    {
        // UI Reset: the staging latch has no card control anymore (the board
        // is display-only) — the component actions and service behavior are
        // retained untouched for the workflows that own them.
        $row = $this->makeRow();
        $this->softAssign($row);

        Livewire::test(Board::class)->call('stage', $row->id);
        $this->assertNotNull($row->queueLineItem->staged_at);

        Livewire::test(Board::class)->call('unstage', $row->id);
        $this->assertNull($row->queueLineItem->fresh()->staged_at);
    }

    public function test_stage_on_unassigned_item_surfaces_the_error_state(): void
    {
        $row = $this->makeRow(); // no equipment

        Livewire::test(Board::class)
            ->call('stage', $row->id)
            ->assertSee('no assigned equipment');

        $this->assertDatabaseCount('queue_line_items', 0);
    }

    public function test_remove_today_and_forever_and_restore_through_the_component(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row);

        Livewire::test(Board::class)
            ->call('removeToday', $row->id)
            ->assertDontSee('data-queue-row="card"');

        // New item for the forever path
        $other = $this->makeRow();
        $this->softAssign($other);

        Livewire::test(Board::class)
            ->call('removeForever', $other->id)
            ->set('showSuppressed', true)
            ->assertSee('Removed Forever')
            ->assertSee('Order #' . $other->order->order_number)
            ->call('restoreItem', $other->id);

        $this->assertContains($other->id, $this->boardIds());
    }

    public function test_store_filter_narrows_the_board_and_survives_actions(): void
    {
        $north = $this->makeRow();
        $south = $this->makeRow(null, ['delivery_store_id' => $this->storeSouth->id]);
        $this->softAssign($north);
        $this->softAssign($south);

        Livewire::test(Board::class)
            ->assertSee($north->order->order_number)
            ->assertSee($south->order->order_number)
            ->set('store', (string) $this->storeNorth->id)
            ->assertSee($north->order->order_number)
            ->assertDontSee('ID: ' . $south->order->order_number)
            // an action must not reset the filter
            ->call('rush', $north->id)
            ->assertSet('store', (string) $this->storeNorth->id)
            ->assertDontSee('ID: ' . $south->order->order_number);
    }

    // ── Presentation data ────────────────────────────────────────────────

    public function test_card_shows_labels_badges_and_options_count(): void
    {
        $truck = $this->makeRow(null, [], [
            ['unique_id' => 'o1', 'name' => 'Smooth Bucket', 'price' => 0, 'charged' => '1 Time Max', 'comment' => null],
            ['unique_id' => 'o2', 'name' => 'Forks', 'price' => 25, 'charged' => '1 Time Max', 'comment' => null],
        ]);
        $this->softAssign($truck);
        $truck->order->payments()->create([
            'payment_method' => \App\Enums\Orders\OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(), 'amount' => 100,
            'status' => \App\Enums\Orders\OrderPaymentStatus::Paid->value,
        ]);

        $inStore = $this->makeRow(null, ['delivery_transport_mode' => 'Store']);
        $this->softAssign($inStore);

        Livewire::test(Board::class)
            ->assertSee('Delivery Truck')
            ->assertSee('Delivery In Store')
            ->assertSee('+ 2 options')
            // zero options render nothing — no space reserved
            ->assertDontSee('+ 0 options')
            ->assertSee($this->storeNorth->store_name);

        // Option NAMES never render on cards
        Livewire::test(Board::class)
            ->assertDontSee('Smooth Bucket');
    }

    public function test_image_always_comes_from_a_product_and_substitution_uses_the_assigned_products(): void
    {
        $alternate = $this->makeRow();
        $otherProduct = \App\Models\ProductManagement\Product::create([
            'product_name' => 'Boom Lift 56ft', 'slug' => 'b56-' . uniqid(), 'product_type' => 'Rental',
        ]);
        $this->softAssign($alternate, $this->makeEquipment(['assigned_product_id' => $otherProduct->id]));

        $unknown = $this->makeRow();
        $this->softAssign($unknown, $this->makeEquipment(['assigned_product_id' => null]));

        $component = Livewire::test(Board::class)
            ->assertSee('Substitute')
            ->assertSee('Confirm Match');

        // UI Iteration 1: every card carries a PRODUCT image (never an
        // equipment photo) — the substitution card resolves it from the
        // ASSIGNED product, the unknown card from the ordered product; alt
        // text proves which product resolved each image.
        $html = $component->html();
        $this->assertSame(2, substr_count($html, '<img'));
        $this->assertStringContainsString('alt="' . $otherProduct->product_name . '"', $html);
        $this->assertStringContainsString('alt="' . $this->orderedProduct->product_name . '"', $html);
    }
}
