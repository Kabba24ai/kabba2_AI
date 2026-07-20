<?php

namespace Tests\Feature\QueueLine;

use App\Livewire\QueueLine\Board;
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;
use Livewire\Livewire;

/**
 * Filter Expansion (2026-07-20) — Time / Store / Delivery Method /
 * Category / Product on the standard board. All filters NARROW the
 * existing eligible set (eligibility itself is untouched), combine with
 * AND logic, and every displayed count derives from the same filtered
 * collection. The wall board stays a passive display (original store
 * select only); the mobile feeds are unchanged.
 */
class QueueLineFilterTest extends QueueLineTestCase
{
    private function cardCount(string $html): int
    {
        return substr_count($html, 'data-queue-row="card"');
    }

    private function makeCategorizedProduct(string $name, ProductCategory $category): Product
    {
        $product = Product::create([
            'product_name' => $name, 'slug' => str($name)->slug() . '-' . uniqid(), 'product_type' => 'Rental',
        ]);
        $product->categories()->attach($category->id);

        return $product;
    }

    // ── Defaults ─────────────────────────────────────────────────────────

    public function test_all_is_the_default_state_for_every_filter(): void
    {
        Livewire::test(Board::class)
            ->assertSet('time', 'all')
            ->assertSet('method', 'all')
            ->assertSet('category', '')
            ->assertSet('product', '');
    }

    // ── Time filter ──────────────────────────────────────────────────────

    public function test_time_semantics_for_all_and_today_only(): void
    {
        $overdue = $this->makeRow(null, ['delivery_date' => now()->subDay()->format('Y-m-d')]);
        $today = $this->makeRow();
        $tomorrow = $this->makeRow(null, ['delivery_date' => now()->addDay()->format('Y-m-d')]);

        // Today Only = overdue + today, never tomorrow
        $html = Livewire::test(Board::class)->set('time', 'today')->html();
        $this->assertStringContainsString('data-order-product-id="' . $overdue->id . '"', $html);
        $this->assertStringContainsString('data-order-product-id="' . $today->id . '"', $html);
        $this->assertStringNotContainsString('data-order-product-id="' . $tomorrow->id . '"', $html);
        $this->assertSame(2, $this->cardCount($html));

        // All = everything eligible under today's window rules
        // (overdue + today + tomorrow)
        $html = Livewire::test(Board::class)->set('time', 'all')->html();
        $this->assertSame(3, $this->cardCount($html));
        $this->assertStringContainsString('data-order-product-id="' . $tomorrow->id . '"', $html);
    }

    public function test_the_time_toggle_offers_exactly_all_and_today_only(): void
    {
        Livewire::test(Board::class)
            ->assertSee('Today Only')
            ->assertDontSee('+ Tomorrow');
    }

    // ── Store filter ─────────────────────────────────────────────────────

    public function test_store_selection_filters_and_all_stores_restores_cross_location_visibility(): void
    {
        $north = $this->makeRow();
        $south = $this->makeRow(null, ['delivery_store_id' => $this->storeSouth->id]);

        $component = Livewire::test(Board::class)
            ->set('store', (string) $this->storeNorth->id);
        $this->assertStringContainsString('data-order-product-id="' . $north->id . '"', $component->html());
        $this->assertStringNotContainsString('data-order-product-id="' . $south->id . '"', $component->html());

        $component->set('store', 'all');
        $this->assertSame(2, $this->cardCount($component->html()));
    }

    public function test_store_persistence_uses_the_established_filter_freezer_mechanism(): void
    {
        // The sticky behavior is the app-wide FilterFreezer localStorage
        // pattern, wired on the standard page shell only.
        $html = $this->get(route('admin.order-management.queue-line.index'))->getContent();

        $this->assertStringContainsString('FilterFreezer', $html);
        $this->assertStringContainsString("'queue_line_filters'", $html);
        $this->assertStringContainsString('queue-store-filter', $html);

        // The wall board never inherits a persisted scope
        $wall = $this->get(route('admin.order-management.queue-line.wallboard'))->getContent();
        $this->assertStringNotContainsString('queue_line_filters', $wall);
    }

    // ── Delivery method ──────────────────────────────────────────────────

    public function test_truck_and_in_store_filters_use_the_canonical_transport_mode(): void
    {
        $truck = $this->makeRow();
        $inStore = $this->makeRow(null, ['delivery_transport_mode' => 'Store']);

        $html = Livewire::test(Board::class)->set('method', 'Truck')->html();
        $this->assertStringContainsString('data-order-product-id="' . $truck->id . '"', $html);
        $this->assertStringNotContainsString('data-order-product-id="' . $inStore->id . '"', $html);

        $html = Livewire::test(Board::class)->set('method', 'Store')->html();
        $this->assertStringContainsString('data-order-product-id="' . $inStore->id . '"', $html);
        $this->assertStringNotContainsString('data-order-product-id="' . $truck->id . '"', $html);

        $this->assertSame(2, $this->cardCount(Livewire::test(Board::class)->html()));
    }

    // ── Dependent Category → Product ─────────────────────────────────────

    public function test_category_limits_the_product_options(): void
    {
        $catA = ProductCategory::create(['title' => 'Boom Lifts']);
        $catB = ProductCategory::create(['title' => 'Excavators']);
        $boom = $this->makeCategorizedProduct('Boom Lift 40', $catA);
        $exc = $this->makeCategorizedProduct('Excavator 6T', $catB);

        $noCategory = Livewire::test(Board::class);
        $this->assertTrue($noCategory->viewData('productOptions')->has($boom->id));
        $this->assertTrue($noCategory->viewData('productOptions')->has($exc->id));

        $withCategory = Livewire::test(Board::class)->set('category', (string) $catA->id);
        $this->assertTrue($withCategory->viewData('productOptions')->has($boom->id));
        $this->assertFalse($withCategory->viewData('productOptions')->has($exc->id));
    }

    public function test_changing_category_clears_an_incompatible_product_but_keeps_a_compatible_one(): void
    {
        $catA = ProductCategory::create(['title' => 'Boom Lifts']);
        $catB = ProductCategory::create(['title' => 'Excavators']);
        $boom = $this->makeCategorizedProduct('Boom Lift 40', $catA);
        $this->makeCategorizedProduct('Excavator 6T', $catB);

        Livewire::test(Board::class)
            ->set('product', (string) $boom->id)
            ->set('category', (string) $catA->id)
            ->assertSet('product', (string) $boom->id) // compatible — survives
            ->set('category', (string) $catB->id)
            ->assertSet('product', '');                // incompatible — cleared
    }

    public function test_product_filter_matches_the_ordered_product_never_the_assigned_equipments(): void
    {
        $catA = ProductCategory::create(['title' => 'Boom Lifts']);
        $ordered = $this->makeCategorizedProduct('Boom Lift 40', $catA);
        $substituteProduct = $this->makeCategorizedProduct('Boom Lift 60', $catA);

        // Ordered product A, physically staged with a unit mapped to B
        $row = $this->makeRow(null, ['product_id' => $ordered->id, 'product_name' => $ordered->product_name]);
        $this->softAssign($row, $this->makeEquipment(['assigned_product_id' => $substituteProduct->id]));

        // Filtering by the ORDERED product finds it…
        $html = Livewire::test(Board::class)->set('product', (string) $ordered->id)->html();
        $this->assertStringContainsString('data-order-product-id="' . $row->id . '"', $html);

        // …filtering by the substitute's product must NOT
        $html = Livewire::test(Board::class)->set('product', (string) $substituteProduct->id)->html();
        $this->assertSame(0, $this->cardCount($html));
    }

    // ── Combination + totals ─────────────────────────────────────────────

    public function test_all_five_filters_combine_with_and_logic_and_totals_match(): void
    {
        $cat = ProductCategory::create(['title' => 'Boom Lifts']);
        $boom = $this->makeCategorizedProduct('Boom Lift 40', $cat);

        // The one row matching every condition
        $match = $this->makeRow(null, ['product_id' => $boom->id, 'product_name' => $boom->product_name]);

        // Near-misses, each failing exactly one condition
        $this->makeRow(null, ['product_id' => $boom->id, 'product_name' => $boom->product_name,
            'delivery_date' => now()->addDay()->format('Y-m-d')]);                        // tomorrow (time)
        $this->makeRow(null, ['product_id' => $boom->id, 'product_name' => $boom->product_name,
            'delivery_store_id' => $this->storeSouth->id]);                                // wrong store
        $this->makeRow(null, ['product_id' => $boom->id, 'product_name' => $boom->product_name,
            'delivery_transport_mode' => 'Store']);                                        // in-store (method)
        $this->makeRow();                                                                  // other product

        $component = Livewire::test(Board::class)
            ->set('time', 'today')
            ->set('store', (string) $this->storeNorth->id)
            ->set('method', 'Truck')
            ->set('category', (string) $cat->id)
            ->set('product', (string) $boom->id);

        $html = $component->html();
        $this->assertSame(1, $this->cardCount($html));
        $this->assertStringContainsString('data-order-product-id="' . $match->id . '"', $html);
        // Section count badge derives from the same filtered set
        $this->assertStringContainsString('>1</span>', $html);
    }

    public function test_changing_one_filter_never_resets_unrelated_filters(): void
    {
        Livewire::test(Board::class)
            ->set('time', 'today')
            ->set('method', 'Truck')
            ->set('store', (string) $this->storeNorth->id)
            ->set('time', 'all')
            ->assertSet('method', 'Truck')
            ->assertSet('store', (string) $this->storeNorth->id);
    }

    // ── Wall board stays passive ─────────────────────────────────────────

    public function test_wall_board_renders_no_filter_bar_and_keeps_its_store_select(): void
    {
        $html = Livewire::test(Board::class, ['wallboard' => true])->html();

        $this->assertStringNotContainsString('data-queue-filter-bar', $html);
        $this->assertStringNotContainsString('Today Only', $html);
        $this->assertStringContainsString('queue-store-filter', $html);
    }
}
