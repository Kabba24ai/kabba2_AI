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

    public function test_cards_group_by_order_and_link_to_order_edit(): void
    {
        $order = $this->makeOrder(['customer_name' => 'Grouped Customer']);
        $a = $this->makeRow($order);
        $b = $this->makeRow($order);
        $this->softAssign($a);
        $this->softAssign($b);

        Livewire::test(Board::class)
            ->assertSee('Order #' . $order->order_number)
            ->assertSee('Grouped Customer')
            ->assertSee(route('admin.order-management.orders.edit', $order->unique_id));

        // Order-level context renders ONCE per group even with two cards
        $html = Livewire::test(Board::class)->html();
        $this->assertSame(1, substr_count($html, 'Order #' . $order->order_number));
        $this->assertSame(2, substr_count($html, 'data-queue-row="card"'));
    }

    public function test_unassigned_item_renders_needs_equipment_row_without_stage_control(): void
    {
        $row = $this->makeRow(); // eligible, no soft assignment

        $component = Livewire::test(Board::class)
            ->assertSee('Needs Equipment Assignment')
            ->assertSee($row->product_name);

        $html = $component->html();
        $this->assertSame(1, substr_count($html, 'data-queue-row="unassigned"'));
        $this->assertSame(0, substr_count($html, 'data-queue-row="card"'));
        // No stage control on the attention row
        $this->assertStringNotContainsString('wire:click="stage(', $html);
    }

    public function test_unassigned_row_becomes_a_card_once_soft_assigned(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row);

        $html = Livewire::test(Board::class)->html();
        $this->assertSame(1, substr_count($html, 'data-queue-row="card"'));
        $this->assertSame(0, substr_count($html, 'data-queue-row="unassigned"'));
    }

    public function test_sections_render_with_tomorrow_visually_subdued(): void
    {
        $this->makeRow(null, ['delivery_date' => now()->subDay()->format('Y-m-d')]);
        $this->makeRow();
        $this->makeRow(null, ['delivery_date' => now()->addDay()->format('Y-m-d')]);

        Livewire::test(Board::class)
            ->assertSeeInOrder(['Overdue', 'Due Today', 'Due Tomorrow'])
            ->assertSee('opacity-70', false); // the Tomorrow wrapper treatment
    }

    public function test_rushed_item_moves_to_the_rush_section(): void
    {
        $row = $this->makeRow(null, ['delivery_date' => now()->addDay()->format('Y-m-d')]);
        QueueLineService::rush($row, $this->admin);

        Livewire::test(Board::class)
            ->assertSeeInOrder(['RUSH', $row->product_name]);
    }

    public function test_empty_state_renders_when_nothing_is_eligible(): void
    {
        Livewire::test(Board::class)
            ->assertSee('The Queue Line is clear.');
    }

    // ── Actions through the component ────────────────────────────────────

    public function test_stage_and_unstage_through_the_component(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row);

        Livewire::test(Board::class)
            ->call('stage', $row->id)
            ->assertSee('On Queue Line');

        $this->assertNotNull($row->queueLineItem->staged_at);

        Livewire::test(Board::class)
            ->call('unstage', $row->id)
            ->assertSee('Not Staged');

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
            ->assertDontSee('Order #' . $south->order->order_number)
            // an action must not reset the filter
            ->call('rush', $north->id)
            ->assertSet('store', (string) $this->storeNorth->id)
            ->assertDontSee('Order #' . $south->order->order_number);
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
            ->assertSee('Options: 2')
            ->assertSee('Options: 0')
            ->assertSee('Direct Assignment')
            ->assertSee($this->storeNorth->store_name);

        // Option NAMES never render on cards
        Livewire::test(Board::class)
            ->assertDontSee('Smooth Bucket');
    }

    public function test_alternate_and_unknown_cards_never_show_the_ordered_product_image(): void
    {
        $alternate = $this->makeRow();
        $otherProduct = \App\Models\ProductManagement\Product::create([
            'product_name' => 'Boom Lift 56ft', 'slug' => 'b56-' . uniqid(), 'product_type' => 'Rental',
        ]);
        $this->softAssign($alternate, $this->makeEquipment(['assigned_product_id' => $otherProduct->id]));

        $unknown = $this->makeRow();
        $this->softAssign($unknown, $this->makeEquipment(['assigned_product_id' => null]));

        $component = Livewire::test(Board::class)
            ->assertSee('Alternate Equipment')
            ->assertSee('Assignment Product Unknown');

        // Phase 1 renders no product imagery at all — nothing on the board
        // can misrepresent the assigned machine.
        $html = $component->html();
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringNotContainsString($this->orderedProduct->image_url, $html);
    }
}
