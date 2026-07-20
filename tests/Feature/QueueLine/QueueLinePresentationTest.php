<?php

namespace Tests\Feature\QueueLine;

use App\Livewire\QueueLine\Board;
use App\Models\ChecklistManagement\EquipmentChecklist\EquipmentRentalReadyTemplate;
use App\Models\ProductManagement\Product;
use App\Services\QueueLine\QueueLineService;
use Livewire\Livewire;

/**
 * Phase 2 — card imagery, assignment visual treatments, Rental Ready and
 * equipment-status display, priority tiers, fixed dimensions, payment
 * wording, and the wall-board route/mode.
 */
class QueueLinePresentationTest extends QueueLineTestCase
{
    // ── Imagery & assignment treatments ──────────────────────────────────

    public function test_direct_assignment_shows_the_canonical_product_image(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row);

        $html = Livewire::test(Board::class)->html();

        // One direct visual containing an <img> whose src is EXACTLY the
        // canonical accessor output (no second image-resolution path) with
        // meaningful alt text — and no overlay ribbon (direct is the quiet,
        // normal state)
        $this->assertSame(1, substr_count($html, 'data-assignment-visual="direct"'));
        $this->assertStringContainsString('src="' . $this->orderedProduct->image_url . '"', $html);
        $this->assertStringContainsString('alt="' . $this->orderedProduct->product_name . '"', $html);
        $this->assertStringNotContainsString('Substitute', $html);
        $this->assertStringNotContainsString('Confirm Match', $html);
    }

    public function test_product_without_media_uses_the_canonical_fallback_image(): void
    {
        $row = $this->makeRow(); // fixture products have no media
        $this->softAssign($row);

        $html = Livewire::test(Board::class)->html();

        $this->assertStringContainsString('No_Image_Available.jpg', $html);
    }

    public function test_substitution_shows_the_assigned_products_image_with_a_substitute_ribbon(): void
    {
        $row = $this->makeRow();
        $assignedProduct = Product::create([
            'product_name' => 'Boom Lift 56ft', 'slug' => 'b56-' . uniqid(), 'product_type' => 'Rental',
        ]);
        $unit = $this->makeEquipment(['assigned_product_id' => $assignedProduct->id]);
        $this->softAssign($row, $unit);

        $html = Livewire::test(Board::class)->html();

        // UI Iteration 1: the image is the ASSIGNED product's (the machine
        // class physically leaving the yard), branded with a bold ribbon —
        // alt text proves which product resolved the image.
        $this->assertSame(1, substr_count($html, 'data-assignment-visual="alternate"'));
        $this->assertStringContainsString('Substitute', $html);
        $this->assertStringContainsString('alt="' . $assignedProduct->product_name . '"', $html);
        // Ordered product stays first in the identity block; the substitution
        // is spelled out beneath it
        $this->assertStringContainsString($this->orderedProduct->product_name, $html);
        $this->assertStringContainsString('Substituting with', $html);
        $this->assertStringContainsString($assignedProduct->product_name, $html);
    }

    public function test_unknown_is_distinct_from_substitute_and_shows_the_unit(): void
    {
        $row = $this->makeRow();
        $unit = $this->makeEquipment(['assigned_product_id' => null]);
        $this->softAssign($row, $unit);

        $html = Livewire::test(Board::class)->html();

        // Unknown mapping: ordered product's image + a Confirm Match ribbon —
        // never presented as an intentional substitution
        $this->assertSame(1, substr_count($html, 'data-assignment-visual="unknown"'));
        $this->assertStringContainsString('Confirm Match', $html);
        $this->assertStringContainsString('alt="' . $this->orderedProduct->product_name . '"', $html);
        $this->assertStringContainsString($unit->equipment_name, $html); // reliable identifier
        $this->assertStringNotContainsString('Substitute', $html);
    }

    public function test_needs_equipment_renders_the_same_card_with_ordered_image_and_no_stage_control(): void
    {
        $this->makeRow(); // eligible, unassigned

        $html = Livewire::test(Board::class)->html();

        // UI Iteration 1: unassigned items use the SAME card — ordered
        // product image + Needs Equipment ribbon; the equipment section
        // adapts instead of the card changing shape
        // Refinement 2026-07-20: no banner — the equipment row carries the
        // unassigned state ("No equipment selected"), nothing shouts.
        $this->assertStringNotContainsString('Needs Equipment Assignment', $html);
        $this->assertSame(1, substr_count($html, 'data-queue-row="card"'));
        $this->assertSame(1, substr_count($html, 'data-assignment-visual="unassigned"'));
        $this->assertStringContainsString('alt="' . $this->orderedProduct->product_name . '"', $html);
        $this->assertStringContainsString('No equipment selected', $html);
        $this->assertStringNotContainsString('wire:click="stage(', $html);
    }

    // ── Rental Ready + equipment status display ──────────────────────────

    private function rentalReadyTemplate(int $equipmentId, string $status, bool $complete): EquipmentRentalReadyTemplate
    {
        return EquipmentRentalReadyTemplate::create([
            'equipment_id'    => $equipmentId,
            'employee_id'     => $this->admin->id,
            'employee_name'   => 'Shop Tech',
            'inspection_date' => now()->format('Y-m-d'),
            'inspection_time' => now()->format('H:i'),
            'status'          => $status,
            'is_complete'     => $complete,
        ]);
    }

    public function test_rental_ready_states_render_without_gating_anything(): void
    {
        $ready = $this->makeRow();
        $readyUnit = $this->makeEquipment(['assigned_product_id' => $ready->product_id]);
        $this->softAssign($ready, $readyUnit);
        $this->rentalReadyTemplate($readyUnit->id, 'Rental Ready', true);

        $draft = $this->makeRow();
        $draftUnit = $this->makeEquipment(['assigned_product_id' => $draft->product_id]);
        $this->softAssign($draft, $draftUnit);
        $this->rentalReadyTemplate($draftUnit->id, 'Draft', false);

        $damaged = $this->makeRow();
        $damagedUnit = $this->makeEquipment(['assigned_product_id' => $damaged->product_id]);
        $this->softAssign($damaged, $damagedUnit);
        $this->rentalReadyTemplate($damagedUnit->id, 'Damaged', true);

        $uninspected = $this->makeRow();
        $this->softAssign($uninspected);

        $html = Livewire::test(Board::class)->html();

        // Refinement 2026-07-20: Queue Line is NOT a Rental Ready status
        // board — no RR badge renders in ANY inspection state. The
        // inspection data, rules, and screens are untouched; only the queue
        // card presentation dropped it. Equipment status still shows.
        $this->assertStringNotContainsString('RR:', $html);
        $this->assertStringContainsString('Available', $html);
        // All four rows on the board — nothing filtered by inspection state
        $this->assertSame(4, substr_count($html, 'data-queue-row="card"'));
    }

    public function test_maint_hold_and_damaged_units_stay_visible_and_stageable(): void
    {
        $holdRow = $this->makeRow();
        $holdUnit = $this->makeEquipment([
            'assigned_product_id' => $holdRow->product_id,
            'current_status' => 'maintenance',
        ]);
        $this->softAssign($holdRow, $holdUnit);

        $damagedRow = $this->makeRow();
        $damagedUnit = $this->makeEquipment([
            'assigned_product_id' => $damagedRow->product_id,
            'current_status' => 'damaged',
        ]);
        $this->softAssign($damagedRow, $damagedUnit);

        $html = Livewire::test(Board::class)->html();
        $this->assertStringContainsString('Maintenance Hold', $html);
        $this->assertStringContainsString('Damaged', $html);
        $this->assertSame(2, substr_count($html, 'data-queue-row="card"'));

        // Neither status blocks staging (Rental Ready never gates Queue Line)
        QueueLineService::stage($holdRow->fresh(['softAssignment.equipment']), $this->admin);
        QueueLineService::stage($damagedRow->fresh(['softAssignment.equipment']), $this->admin);
        $this->assertNotNull($holdRow->queueLineItem->staged_at);
        $this->assertNotNull($damagedRow->queueLineItem->staged_at);
    }

    // ── Fixed dimensions & long values ───────────────────────────────────

    public function test_long_names_are_truncated_with_accessible_full_values(): void
    {
        $longProduct = Product::create([
            'product_name' => str_repeat('Ultra Heavy Duty Telescopic Boom Lift Platform ', 4),
            'slug' => 'long-' . uniqid(), 'product_type' => 'Rental',
        ]);
        $order = $this->makeOrder(['customer_name' => str_repeat('Extremely Long Customer Company Name ', 5)]);
        $row = $this->makeRow($order, ['product_id' => $longProduct->id, 'product_name' => $longProduct->product_name]);
        $this->softAssign($row, $this->makeEquipment(['assigned_product_id' => $longProduct->id]));

        $html = Livewire::test(Board::class)->html();

        // Fixed card dimensions regardless of content length
        $this->assertStringContainsString('h-[25rem]', $html);
        $this->assertStringContainsString('truncate', $html);
        // Full values remain accessible via title attributes
        $this->assertStringContainsString('title="Ordered: ' . $longProduct->product_name . '"', $html);
        $this->assertStringContainsString(e($order->customer_name), $html);
    }

    // ── Payment wording ──────────────────────────────────────────────────

    public function test_order_without_payments_reads_no_payment_recorded(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row);

        Livewire::test(Board::class)
            ->assertSee('No Payment Recorded')
            ->assertDontSee('Unknown');
    }

    // ── Priority tiers & polling ─────────────────────────────────────────

    public function test_workflow_sections_render_and_rush_marker_sorts_first(): void
    {
        // UI Reset: the board is organized by WORKFLOW (Pending / Staged /
        // Delivered Today). Urgency badges left the cards (calm information
        // display); the RUSH marker chip remains — it is queue management —
        // and priority ordering is unchanged (RUSH first, then Overdue →
        // Today → Tomorrow).
        $rushed = $this->makeRow(null, ['delivery_date' => now()->addDay()->format('Y-m-d')]);
        QueueLineService::rush($rushed, $this->admin);
        $overdue = $this->makeRow(null, ['delivery_date' => now()->subDay()->format('Y-m-d')]);
        $this->makeRow();
        $this->makeRow(null, ['delivery_date' => now()->addDay()->format('Y-m-d')]);

        $html = Livewire::test(Board::class)->html();

        $this->assertStringContainsString('Queue Line — Pending', $html);
        // Exactly ONE marker chip (menu wording never counts as a marker)
        $this->assertSame(1, substr_count($html, '>RUSH</span>'));
        // The rushed tomorrow item renders before the overdue one
        $this->assertTrue(
            strpos($html, 'ID: ' . $rushed->order->order_number)
                < strpos($html, 'ID: ' . $overdue->order->order_number),
            'the rushed item must sort first inside the section',
        );
    }

    public function test_standard_mode_polls_at_60s_and_wallboard_at_30s(): void
    {
        $this->assertStringContainsString('wire:poll.60s', Livewire::test(Board::class)->html());
        $this->assertStringContainsString('wire:poll.30s', Livewire::test(Board::class, ['wallboard' => true])->html());
    }

    // ── Wall-board route & mode ──────────────────────────────────────────

    public function test_wallboard_route_renders_for_authenticated_admin(): void
    {
        $this->get(route('admin.order-management.queue-line.wallboard'))
            ->assertOk()
            ->assertSeeLivewire(Board::class)
            ->assertDontSee('menu-dropdown-item'); // no admin sidebar chrome
    }

    public function test_wallboard_guest_is_redirected_and_standard_route_remains(): void
    {
        auth()->logout();
        $this->get(route('admin.order-management.queue-line.wallboard'))->assertRedirect();

        $this->actingAs($this->admin);
        $this->get(route('admin.order-management.queue-line.index'))->assertOk();
    }

    public function test_wallboard_mode_shares_the_canonical_board_and_actions(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row);

        Livewire::test(Board::class, ['wallboard' => true])
            ->assertSee('Standard View')
            ->assertSee($row->order->order_number)
            ->assertSee(route('admin.order-management.orders.edit', $row->order->unique_id))
            // actions work in wallboard mode
            ->call('stage', $row->id);

        $this->assertNotNull($row->queueLineItem->staged_at);
    }

    public function test_wallboard_store_filter_works_and_survives_polling_renders(): void
    {
        $north = $this->makeRow();
        $south = $this->makeRow(null, ['delivery_store_id' => $this->storeSouth->id]);
        $this->softAssign($north);
        $this->softAssign($south);

        Livewire::test(Board::class, ['wallboard' => true])
            ->set('store', (string) $this->storeNorth->id)
            ->assertSee('ID: ' . $north->order->order_number)
            ->assertDontSee('ID: ' . $south->order->order_number)
            // a poll is just a re-render: state must hold
            ->call('$refresh')
            ->assertSet('store', (string) $this->storeNorth->id)
            ->assertDontSee('ID: ' . $south->order->order_number);
    }

    public function test_wallboard_shows_operational_date_and_last_updated(): void
    {
        Livewire::test(Board::class, ['wallboard' => true])
            ->assertSee(now()->format('l, F j, Y'))
            ->assertSee('Updated');
    }
}
