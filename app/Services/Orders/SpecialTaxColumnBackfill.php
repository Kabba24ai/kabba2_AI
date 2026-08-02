<?php

namespace App\Services\Orders;

use Illuminate\Support\Facades\DB;

/**
 * Reconstructs current special-tax and added-fee columns from each line's
 * frozen `product_data` snapshot.
 *
 * WHY THIS EXISTS. Special tax and added fees have never had columns.
 * `CartHelper::buildCartItem()` computes them at checkout, folds them into
 * `orders.grand_total`, and persists them ONLY inside the per-line
 * `product_data` JSON. `orders.tax_amount` never contains them.
 *
 * That is not merely untidy — it makes a live defect unfixable.
 * `OrderDiscountTarget::recompute()` reduces the merchandise basis and
 * recomputes ordinary tax, then preserves everything else untouched:
 *
 *     $otherComponents = $origGrand - $subtotal - $baseTax;
 *
 * Special tax is basis-derived, so it must shrink with the basis. It does not,
 * and it CANNOT be made to, because `$otherComponents` is a single lumped
 * residual holding special tax + added fees + delivery − coupon. The engine
 * cannot reduce one while preserving another when it cannot tell them apart.
 * These columns are what make the distinction possible.
 *
 * THE RESIDUAL, POST-DISCOUNT. `recompute()` writes
 *
 *     grand_total = (subtotal − pretax_discount_total) + tax_amount + other
 *
 * so the components an order must explain are
 *
 *     residual = grand_total − (subtotal − pretax_discount_total
 *                               + tax_amount − discount_amount)
 *
 * which IS special tax plus added fees. On an undiscounted order
 * `pretax_discount_total` is zero and this reduces to the familiar form.
 * Using `subtotal` alone here would be wrong on every discounted order —
 * gross subtotal against a reduced grand total.
 *
 * THE ZERO RULE. A missing, malformed, or key-absent snapshot must NEVER be
 * recorded as a trustworthy `0.00` while the order's own arithmetic says
 * otherwise. A row is backfilled only when the reconstructed components equal
 * the residual to the cent, or when both are zero and there is therefore
 * nothing to get wrong. Anything else keeps the column default and is reported
 * as a named exception — never quietly zeroed.
 *
 * READ-ONLY GUARANTEE: `product_data` is never written, reparsed into, or
 * normalized by this class.
 */
class SpecialTaxColumnBackfill
{
    /** Reconstructed cleanly from complete frozen JSON; components reconcile exactly. */
    public const RECONSTRUCTED = 'reconstructed';

    /** No components to reconstruct and none owed. Written as explicit zeros. */
    public const NO_COMPONENTS = 'no_components';

    /**
     * Order carries no lines AND owes nothing. Nothing to write at line level.
     *
     * The ZERO RESIDUAL licenses the zero, not the absence of lines. "No lines"
     * does not prove "no special tax or fees" — it only proves there is no line
     * to attribute them to. Extension children happen to be built that way
     * today by `Extension\StoreController`, but that is a property of that
     * controller, not a law of the schema.
     */
    public const LINELESS = 'lineless';

    /** A line's product_data is unreadable AND the order owes an unexplained residual. NOT written. */
    public const MISSING_JSON_UNEXPLAINED = 'missing_json_unexplained';

    /** JSON parsed on every line, but reconstructed components disagree with the residual. NOT written. */
    public const UNRECONCILED = 'unreconciled';

    /** No lines, but a nonzero residual. Its own category — there is no snapshot here to be missing. */
    public const LINELESS_UNEXPLAINED = 'lineless_with_nonzero_residual';

    /**
     * Read-only analysis. Returns the audit for every order, categorized.
     *
     * @return array<string,mixed>
     */
    public static function analyze(): array
    {
        $counts = array_fill_keys([
            self::RECONSTRUCTED, self::NO_COMPONENTS, self::LINELESS,
            self::MISSING_JSON_UNEXPLAINED, self::UNRECONCILED, self::LINELESS_UNEXPLAINED,
        ], 0);

        $writable = [];
        $exceptions = [
            self::MISSING_JSON_UNEXPLAINED => [],
            self::UNRECONCILED             => [],
            self::LINELESS_UNEXPLAINED     => [],
        ];

        $ordersExamined = 0;
        $linesExamined  = 0;

        DB::table('orders')
            ->select('id', 'subtotal', 'tax_amount', 'discount_amount', 'grand_total', 'pretax_discount_total')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->chunk(500, function ($orders) use (
                &$counts, &$writable, &$exceptions, &$ordersExamined, &$linesExamined
            ) {
                $orderIds = $orders->pluck('id')->all();

                $linesByOrder = DB::table('order_products')
                    ->select('id', 'order_id', 'product_data')
                    ->whereIn('order_id', $orderIds)
                    ->orderBy('id')
                    ->get()
                    ->groupBy('order_id');

                foreach ($orders as $order) {
                    $ordersExamined++;

                    $lines = $linesByOrder->get($order->id, collect());
                    $linesExamined += $lines->count();

                    $residualCents = self::residualCents($order);

                    if ($lines->isEmpty()) {
                        if ($residualCents === 0) {
                            $counts[self::LINELESS]++;
                            $writable[] = [
                                'order_id' => (int) $order->id,
                                'special_tax_cents' => 0,
                                'added_fees_cents' => 0,
                                'lines' => [],
                            ];
                        } else {
                            $counts[self::LINELESS_UNEXPLAINED]++;
                            $exceptions[self::LINELESS_UNEXPLAINED][] = self::exceptionRow($order, $residualCents, 0);
                        }

                        continue;
                    }

                    $jsonComplete    = true;
                    $componentsCents = 0;
                    $lineValues      = [];

                    foreach ($lines as $line) {
                        $frozen = self::decode($line->product_data);

                        if ($frozen === null) {
                            $jsonComplete = false;
                            $lineValues[] = ['id' => (int) $line->id, 'special_tax_cents' => 0, 'added_fees_cents' => 0];

                            continue;
                        }

                        $special = self::cents($frozen['special_tax'] ?? 0);
                        $fees    = self::cents($frozen['added_fees'] ?? 0);

                        $componentsCents += $special + $fees;
                        $lineValues[] = ['id' => (int) $line->id, 'special_tax_cents' => $special, 'added_fees_cents' => $fees];
                    }

                    if (! $jsonComplete) {
                        if ($residualCents === 0 && $componentsCents === 0) {
                            $counts[self::NO_COMPONENTS]++;
                            $writable[] = self::writableRow($order, $lineValues, 0, 0);
                        } else {
                            $counts[self::MISSING_JSON_UNEXPLAINED]++;
                            $exceptions[self::MISSING_JSON_UNEXPLAINED][] = self::exceptionRow($order, $residualCents, $componentsCents);
                        }

                        continue;
                    }

                    if ($componentsCents !== $residualCents) {
                        $counts[self::UNRECONCILED]++;
                        $exceptions[self::UNRECONCILED][] = self::exceptionRow($order, $residualCents, $componentsCents);

                        continue;
                    }

                    $counts[$componentsCents === 0 ? self::NO_COMPONENTS : self::RECONSTRUCTED]++;

                    $writable[] = self::writableRow(
                        $order,
                        $lineValues,
                        array_sum(array_column($lineValues, 'special_tax_cents')),
                        array_sum(array_column($lineValues, 'added_fees_cents')),
                    );
                }
            });

        return [
            'orders_examined' => $ordersExamined,
            'lines_examined'  => $linesExamined,
            'counts'          => $counts,
            'writable'        => $writable,
            'exceptions'      => $exceptions,
        ];
    }

    /**
     * Write the analyzed values. Order-level aggregates come from the
     * backfilled LINE columns, never recomputed from JSON a second time, so an
     * order can never disagree with the sum of its own lines.
     *
     * @param  array<string,mixed>|null  $analysis
     * @return array<string,mixed>
     */
    public static function apply(?array $analysis = null): array
    {
        $analysis ??= self::analyze();

        foreach (array_chunk($analysis['writable'], 200) as $chunk) {
            DB::transaction(function () use ($chunk) {
                foreach ($chunk as $row) {
                    foreach ($row['lines'] as $line) {
                        DB::table('order_products')
                            ->where('id', $line['id'])
                            ->update([
                                'special_tax' => $line['special_tax_cents'] / 100,
                                'added_fees'  => $line['added_fees_cents'] / 100,
                            ]);
                    }

                    DB::table('orders')
                        ->where('id', $row['order_id'])
                        ->update([
                            'special_tax_amount' => $row['special_tax_cents'] / 100,
                            'added_fees_amount'  => $row['added_fees_cents'] / 100,
                        ]);
                }
            });
        }

        return $analysis;
    }

    /**
     * The components this order must explain, in integer cents.
     *
     * Accounts for `pretax_discount_total`, which `OrderDiscountTarget` holds
     * OUTSIDE `discount_amount` while having already reduced `tax_amount` and
     * `grand_total`. Ignoring it would compare a gross subtotal against a
     * discounted grand total and reject every discounted order.
     */
    private static function residualCents($order): int
    {
        return self::cents($order->grand_total)
            - (self::cents($order->subtotal)
                - self::cents($order->pretax_discount_total ?? 0)
                + self::cents($order->tax_amount)
                - self::cents($order->discount_amount));
    }

    /** @return array<string,int> */
    private static function exceptionRow($order, int $residual, int $components): array
    {
        return [
            'order_id'         => (int) $order->id,
            'residual_cents'   => $residual,
            'components_cents' => $components,
        ];
    }

    /** @param array<int,array{id:int, special_tax_cents:int, added_fees_cents:int}> $lineValues */
    private static function writableRow($order, array $lineValues, int $special, int $fees): array
    {
        return [
            'order_id'          => (int) $order->id,
            'special_tax_cents' => $special,
            'added_fees_cents'  => $fees,
            'lines'             => $lineValues,
        ];
    }

    /**
     * @return array<string,mixed>|null null when the snapshot is absent or is
     *         not an object. `product_data` is a MySQL JSON column, so
     *         syntactically broken text cannot be stored — the reachable
     *         unreadable shapes are NULL, empty, and valid-but-not-an-object.
     */
    private static function decode($raw): ?array
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        if (is_array($raw)) {
            return $raw;
        }

        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    private static function cents($value): int
    {
        return (int) round(((float) $value) * 100);
    }
}
