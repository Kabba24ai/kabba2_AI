<?php

namespace Tests\Feature\QueueLine;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Services\QueueLine\QueueLineEligibility;
use App\Services\QueueLine\QueueLineService;

/**
 * The canonical eligibility predicate, buckets, ordering, classification,
 * and options count — the single source every board surface consumes.
 */
class QueueLineEligibilityTest extends QueueLineTestCase
{
    // ── Inclusion ────────────────────────────────────────────────────────

    public function test_pending_truck_and_store_items_are_included(): void
    {
        $truck = $this->makeRow();
        $inStore = $this->makeRow(null, [
            'delivery_transport_mode' => 'Store',
            'pickup_transport_mode'   => 'Store',
        ]);

        $ids = $this->boardIds();
        $this->assertContains($truck->id, $ids);
        $this->assertContains($inStore->id, $ids);
    }

    public function test_payment_state_never_affects_eligibility(): void
    {
        // Unpaid (no payment rows at all)
        $unpaid = $this->makeRow();

        // Paid
        $paidRow = $this->makeRow();
        $paidRow->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(), 'amount' => 100,
            'status' => OrderPaymentStatus::Paid->value,
        ]);

        // Pending (e.g. POD awaiting collection)
        $pendingRow = $this->makeRow();
        $pendingRow->order->payments()->create([
            'payment_method' => OrderPaymentMethod::COD->value,
            'payment_datetime' => now(), 'amount' => 100,
            'status' => OrderPaymentStatus::Pending->value,
        ]);

        $ids = $this->boardIds();
        $this->assertContains($unpaid->id, $ids);
        $this->assertContains($paidRow->id, $ids);
        $this->assertContains($pendingRow->id, $ids);
    }

    public function test_overdue_today_and_tomorrow_are_included_but_later_dates_are_not(): void
    {
        $overdue = $this->makeRow(null, ['delivery_date' => now()->subDays(3)->format('Y-m-d')]);
        $today = $this->makeRow();
        $tomorrow = $this->makeRow(null, ['delivery_date' => now()->addDay()->format('Y-m-d')]);
        $dayAfter = $this->makeRow(null, ['delivery_date' => now()->addDays(2)->format('Y-m-d')]);

        $ids = $this->boardIds();
        $this->assertContains($overdue->id, $ids);
        $this->assertContains($today->id, $ids);
        $this->assertContains($tomorrow->id, $ids);
        $this->assertNotContains($dayAfter->id, $ids);
    }

    // ── Exclusion ────────────────────────────────────────────────────────

    public function test_non_rental_and_null_date_rows_are_excluded(): void
    {
        $sale = $this->makeRow(null, [
            'product_data' => ['product_type' => 'Sales', 'product_option_items' => []],
        ]);
        $noDate = $this->makeRow(null, ['delivery_date' => null]);

        $ids = $this->boardIds();
        $this->assertNotContains($sale->id, $ids);
        $this->assertNotContains($noDate->id, $ids);
    }

    public function test_non_pending_delivery_statuses_are_excluded(): void
    {
        $completed = $this->makeRow(null, ['delivery_status' => 'Completed']);
        $reschedule = $this->makeRow(null, ['delivery_status' => 'Reschedule']);
        $closed = $this->makeRow(null, ['delivery_status' => 'Close as Completed']);

        $ids = $this->boardIds();
        $this->assertNotContains($completed->id, $ids);
        $this->assertNotContains($reschedule->id, $ids);
        $this->assertNotContains($closed->id, $ids);
    }

    public function test_soft_deleted_order_is_excluded(): void
    {
        $row = $this->makeRow();
        $row->order->delete(); // soft delete — the whereHas('order') gate

        $this->assertNotContains($row->id, $this->boardIds());
    }

    public function test_return_leg_activity_never_creates_queue_items(): void
    {
        // Outbound done, return still pending — a RETURN staging row, not an
        // outbound one. Queue Line reads only the delivery leg.
        $returnPending = $this->makeRow(null, [
            'delivery_status' => 'Completed',
            'is_delivered'    => true,
            'pickup_status'   => 'Pending',
            'pickup_date'     => now()->format('Y-m-d'),
        ]);

        $this->assertNotContains($returnPending->id, $this->boardIds());
    }

    // ── Buckets & ordering ───────────────────────────────────────────────

    public function test_buckets_classify_by_scheduled_delivery_date(): void
    {
        $overdue = $this->makeRow(null, ['delivery_date' => now()->subDay()->format('Y-m-d')]);
        $today = $this->makeRow();
        $tomorrow = $this->makeRow(null, ['delivery_date' => now()->addDay()->format('Y-m-d')]);

        $this->assertSame(QueueLineEligibility::BUCKET_OVERDUE, QueueLineEligibility::bucketFor($overdue));
        $this->assertSame(QueueLineEligibility::BUCKET_TODAY, QueueLineEligibility::bucketFor($today));
        $this->assertSame(QueueLineEligibility::BUCKET_TOMORROW, QueueLineEligibility::bucketFor($tomorrow));
    }

    public function test_dispatch_delivery_date_override_is_ignored(): void
    {
        // Dispatch-only override must not affect Queue Line placement.
        $row = $this->makeRow(null, [
            'delivery_date'          => now()->format('Y-m-d'),
            'dispatch_delivery_date' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $this->assertContains($row->id, $this->boardIds());
        $this->assertSame(QueueLineEligibility::BUCKET_TODAY, QueueLineEligibility::bucketFor($row));
    }

    public function test_ordering_is_rush_then_bucket_then_date_time_id(): void
    {
        $tomorrow = $this->makeRow(null, ['delivery_date' => now()->addDay()->format('Y-m-d')]);
        $todayLate = $this->makeRow(null, ['delivery_time' => '15:00']);
        $todayEarly = $this->makeRow(null, ['delivery_time' => '08:00']);
        $todayNullTime = $this->makeRow(null, ['delivery_time' => null]);
        $overdue = $this->makeRow(null, ['delivery_date' => now()->subDay()->format('Y-m-d')]);
        $rushedTomorrow = $this->makeRow(null, ['delivery_date' => now()->addDay()->format('Y-m-d')]);
        QueueLineService::rush($rushedTomorrow, $this->admin);

        $sorted = QueueLineEligibility::sortItems(
            QueueLineEligibility::boardQuery()->get()
        )->pluck('id')->all();

        // RUSH beats every date bucket; overdue beats today; time asc with
        // nulls last; tomorrow trails.
        $this->assertSame([
            $rushedTomorrow->id,
            $overdue->id,
            $todayEarly->id,
            $todayLate->id,
            $todayNullTime->id,
            $tomorrow->id,
        ], $sorted);
    }

    // ── Assignment classification ────────────────────────────────────────

    public function test_direct_alternate_unknown_and_unassigned_classification(): void
    {
        $direct = $this->makeRow();
        $this->softAssign($direct); // helper defaults to the ordered product

        $alternate = $this->makeRow();
        $otherProduct = \App\Models\ProductManagement\Product::create([
            'product_name' => 'Boom Lift 56ft', 'slug' => 'boom-56-' . uniqid(), 'product_type' => 'Rental',
        ]);
        $this->softAssign($alternate, $this->makeEquipment(['assigned_product_id' => $otherProduct->id]));

        $unknown = $this->makeRow();
        $this->softAssign($unknown, $this->makeEquipment(['assigned_product_id' => null]));

        $unassigned = $this->makeRow();

        $classify = fn ($row) => QueueLineEligibility::classifyAssignment($row->fresh(['softAssignment.equipment']));

        $this->assertSame(QueueLineEligibility::ASSIGNMENT_DIRECT, $classify($direct));
        $this->assertSame(QueueLineEligibility::ASSIGNMENT_ALTERNATE, $classify($alternate));
        $this->assertSame(QueueLineEligibility::ASSIGNMENT_UNKNOWN, $classify($unknown));
        $this->assertSame(QueueLineEligibility::ASSIGNMENT_UNASSIGNED, $classify($unassigned));
    }

    // ── Unit of work ─────────────────────────────────────────────────────

    public function test_quantity_counts_rental_periods_and_never_multiplies_cards(): void
    {
        $row = $this->makeRow(null, ['quantity' => 3]); // 3 rental periods, ONE machine

        $ids = $this->boardIds();
        $this->assertSame(1, collect($ids)->filter(fn ($id) => $id === $row->id)->count());
        $this->assertCount(1, $ids);
    }

    public function test_multiple_items_on_one_order_are_independent_rows(): void
    {
        $order = $this->makeOrder();
        $a = $this->makeRow($order);
        $b = $this->makeRow($order);

        QueueLineService::removeForever($a, $this->admin);

        $ids = $this->boardIds();
        $this->assertNotContains($a->id, $ids);
        $this->assertContains($b->id, $ids);
    }

    // ── Options count ────────────────────────────────────────────────────

    public function test_options_count_includes_zero_dollar_options(): void
    {
        $none = $this->makeRow();
        $mixed = $this->makeRow(null, [], [
            ['unique_id' => 'opt-1', 'name' => 'Smooth Bucket', 'price' => 0, 'charged' => '1 Time Max', 'comment' => null],
            ['unique_id' => 'opt-2', 'name' => 'Auger', 'price' => 75, 'charged' => '1 Time Max', 'comment' => null],
            ['unique_id' => 'opt-3', 'name' => 'Forks', 'price' => '0', 'charged' => 'Unlimited', 'comment' => null],
        ]);

        $this->assertSame(0, QueueLineEligibility::optionsCount($none));
        $this->assertSame(3, QueueLineEligibility::optionsCount($mixed));
    }

    public function test_options_count_survives_null_product_data_options(): void
    {
        $row = $this->makeRow(null, [
            'product_data' => ['product_type' => 'Rental'], // no options key at all
        ]);

        $this->assertSame(0, QueueLineEligibility::optionsCount($row));
    }

    public function test_delivery_type_labels(): void
    {
        $truck = $this->makeRow();
        $inStore = $this->makeRow(null, ['delivery_transport_mode' => 'Store']);

        $this->assertSame('Delivery Truck', QueueLineEligibility::deliveryTypeLabel($truck));
        $this->assertSame('Delivery In Store', QueueLineEligibility::deliveryTypeLabel($inStore));
    }

    // ── Store filter ─────────────────────────────────────────────────────

    public function test_store_filter_uses_delivery_store_id(): void
    {
        $north = $this->makeRow();
        $south = $this->makeRow(null, ['delivery_store_id' => $this->storeSouth->id]);

        $all = $this->boardIds();
        $this->assertContains($north->id, $all);
        $this->assertContains($south->id, $all);

        $northOnly = $this->boardIds($this->storeNorth->id);
        $this->assertContains($north->id, $northOnly);
        $this->assertNotContains($south->id, $northOnly);
    }
}
