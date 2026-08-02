<?php

namespace Tests\Concerns;

use App\Models\Orders\Order;
use Illuminate\Support\Facades\DB;

/**
 * Gives a fixture order the product line a real order always has.
 *
 * Needed since PaymentAllocationService moved onto
 * HistoricalTaxBasisResolver: the historical sales-tax rate is now
 * reconstructed from order_products rows instead of being derived as
 * orders.tax_amount / orders.subtotal (which counts tax-free lines in the
 * denominator and is wrong on any mixed order). An order with no lines has
 * no reconstructable basis, so refund tax allocation correctly refuses to
 * run against one.
 *
 * Several refund fixtures created orders with totals but no lines — a shape
 * checkout never produces. This trait makes those fixtures realistic. The
 * line mirrors the order's own subtotal and tax, so every pre-existing
 * expected value in those tests is unchanged.
 */
trait CreatesOrderFixtureLines
{
    protected function addFixtureLine(Order $order, ?float $subTotal = null, ?float $taxAmount = null): void
    {
        static $seq = 0;
        $seq++;

        $subTotal ??= (float) $order->subtotal;
        $taxAmount ??= (float) $order->tax_amount;

        $productId = DB::table('products')->where('unique_id', 'PRD-ALLOC-FIXTURE')->value('id');

        if (! $productId) {
            $productId = DB::table('products')->insertGetId([
                'unique_id'    => 'PRD-ALLOC-FIXTURE',
                'product_name' => 'Allocation Fixture',
                'slug'         => 'allocation-fixture',
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        $order->products()->create([
            'unique_id'    => 'ORD-ALLOC-FIX-'.$seq.'-'.$order->id,
            'product_id'   => $productId,
            'product_name' => 'Allocation Fixture',
            'price'        => $subTotal,
            'quantity'     => 1,
            'sub_total'    => $subTotal,
            'tax'          => $taxAmount,
            'total'        => $subTotal + $taxAmount,
            'product_data' => json_encode(['special_tax' => 0, 'added_fees' => 0]),
        ]);
    }
}
