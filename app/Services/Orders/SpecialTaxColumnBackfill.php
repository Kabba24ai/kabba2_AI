<?php

namespace App\Services\Orders;

use Illuminate\Support\Facades\DB;

/**
 * Reconstructs current special-tax and added-fee columns from each line's
 * frozen `product_data` snapshot.
 *
 * WHY THIS EXISTS. Until now, special tax and added fees had no columns
 * anywhere. `CartHelper::buildCartItem()` computed them at checkout, folded
 * them into `orders.grand_total`, and wrote them ONLY into the per-line
 * `product_data` JSON. `orders.tax_amount` never included them. That made
 * `HistoricalTaxBasisResolver` read basis and tax from mutable columns but
 * these two from frozen JSON — so any adjustment that moved the columns and
 * the grand total left the JSON behind and broke reconciliation forever after.
 *
 * This class fills the new columns from that JSON, once. It is deliberately
 * split into {@see self::analyze()} (read-only, produces the audit) and
 * {@see self::apply()} (writes), so the exact same reconstruction that the
 * migration performs can be inspected before it runs and verified after.
 *
 * THE ZERO RULE. A missing, malformed, or key-absent snapshot must NEVER be
 * recorded as a trustworthy `0.00` while the order's own arithmetic says
 * otherwise. Every order is checked against the residual it must explain:
 *
 *     residual = grand_total - (subtotal + tax_amount - discount_amount)
 *
 * That residual IS the special tax plus added fees, by construction of
 * `CartHelper::buildCart()`. A row is backfilled only when the reconstructed
 * components equal it to the cent, or when both are zero and there is
 * therefore nothing to get wrong. Anything else is left at the column default
 * and reported as an exception — never quietly zeroed.
 *
 * Skipped orders are not silently degraded: they already fail
 * `HistoricalTaxBasisResolver` today (`FrozenDataUnavailable` or
 * `UnreconciledGrandTotal`), and because the resolver's reconciliation is
 * exact, a default-zero column leaves them failing for the same reason after
 * the backfill as before it. The audit exists so the size of that population
 * is a known number rather than an assumption.
 *
 * READ-ONLY GUARANTEE: `product_data` is never written, reparsed into, or
 * normalized. It remains the immutable original checkout snapshot.
 */
class SpecialTaxColumnBackfill
{
    /** Reconstructed cleanly from complete frozen JSON; components reconcile exactly. */
    public const RECONSTRUCTED = 'reconstructed';

    /** No components to reconstruct and none owed — a plain order. Written as explicit zeros. */
    public const NO_COMPONENTS = 'no_components';

    /**
     * Order carries no lines AND owes nothing. Nothing to write at line level.
     *
     * The zero residual is what licenses the zero — NOT the absence of lines.
     * "No lines" does not independently prove "no special tax or fees"; it only
     * proves there is no line to attribute them to. Known extension children
     * happen to be constrained that way today, but that is a property of how
     * `Extension\StoreController` builds them, not a law of the schema, and a
     * future line-less shape could carry a component.
     */
    public const LINELESS = 'lineless';

    /**
     * Order carries no lines but DOES owe an unexplained residual. NOT written.
     *
     * Deliberately its own category rather than folded into
     * MISSING_JSON_UNEXPLAINED: there is no snapshot here to be missing or
     * malformed. The order simply asserts a component that nothing in its
     * structure can account for, which is a different shape needing a
     * different investigation.
     */
    public const LINELESS_UNEXPLAINED = 'lineless_with_nonzero_residual';

    /** At least one line's product_data is NULL or unparseable, AND the order owes an unexplained residual. NOT written. */
    public const MISSING_JSON_UNEXPLAINED = 'missing_json_unexplained';

    /** JSON parsed on every line, but reconstructed components disagree with the residual. NOT written. */
    public const UNRECONCILED = 'unreconciled';

    /**
     * Read-only analysis. Returns the audit for every order, categorized.
     *
     * @return array{
     *   orders_examined:int, lines_examined:int,
     *   counts:array<string,int>,
     *   writable:array<int, array{order_id:int, special_tax_cents:int, added_fees_cents:int, lines:array<int,array{id:int, special_tax_cents:int, added_fees_cents:int}>}>,
     *   exceptions:array<string, list<array{order_id:int, residual_cents:int, components_cents:int}>>
     * }
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
            ->select('id', 'subtotal', 'tax_amount', 'discount_amount', 'grand_total')
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

                    $residualCents = self::toCents($order->grand_total)
                        - (self::toCents($order->subtotal)
                            + self::toCents($order->tax_amount)
                            - self::toCents($order->discount_amount));

                    if ($lines->isEmpty()) {
                        // A line-less order may be written as zero ONLY when its
                        // unexplained residual is also exactly zero. The absence
                        // of lines is not itself proof that no component was
                        // charged — it only means there is no line to attribute
                        // one to. An order asserting a residual it has no
                        // structure to explain is reported, never zeroed.
                        if ($residualCents === 0) {
                            $counts[self::LINELESS]++;
                            $writable[] = [
                                'order_id'          => (int) $order->id,
                                'special_tax_cents' => 0,
                                'added_fees_cents'  => 0,
                                'lines'             => [],
                            ];
                        } else {
                            $counts[self::LINELESS_UNEXPLAINED]++;
                            $exceptions[self::LINELESS_UNEXPLAINED][] = [
                                'order_id'        => (int) $order->id,
                                'residual_cents'  => $residualCents,
                                'components_cents' => 0,
                            ];
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

                        $special = self::toCents($frozen['special_tax'] ?? 0);
                        $fees    = self::toCents($frozen['added_fees'] ?? 0);

                        $componentsCents += $special + $fees;
                        $lineValues[] = ['id' => (int) $line->id, 'special_tax_cents' => $special, 'added_fees_cents' => $fees];
                    }

                    // The one safe way to write a zero into a row whose snapshot
                    // could not be read: the order demonstrably owes nothing.
                    if (! $jsonComplete) {
                        if ($residualCents === 0 && $componentsCents === 0) {
                            $counts[self::NO_COMPONENTS]++;
                            $writable[] = self::writableRow($order, $lineValues, 0, 0);
                        } else {
                            $counts[self::MISSING_JSON_UNEXPLAINED]++;
                            $exceptions[self::MISSING_JSON_UNEXPLAINED][] = [
                                'order_id'         => (int) $order->id,
                                'residual_cents'   => $residualCents,
                                'components_cents' => $componentsCents,
                            ];
                        }

                        continue;
                    }

                    if ($componentsCents !== $residualCents) {
                        $counts[self::UNRECONCILED]++;
                        $exceptions[self::UNRECONCILED][] = [
                            'order_id'         => (int) $order->id,
                            'residual_cents'   => $residualCents,
                            'components_cents' => $componentsCents,
                        ];

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
     * backfilled LINE columns, never recomputed from JSON a second time, so
     * the order can never disagree with the sum of its own lines.
     *
     * @param  array<string,mixed>|null  $analysis  Reuse a prior analyze() result, or null to run one.
     * @return array<string,mixed> the analysis that was applied
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

    private static function toCents($value): int
    {
        return (int) round(((float) $value) * 100);
    }
}
