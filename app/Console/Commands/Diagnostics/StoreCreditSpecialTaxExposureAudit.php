<?php

namespace App\Console\Commands\Diagnostics;

use App\Services\Orders\OrderPaymentSummary;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Exposure audit — special tax over-collected on pre-tax-discounted orders.
 *
 * STRICTLY READ-ONLY. Every operation is a SELECT. No create/save/update/
 * delete, no migration, no queue dispatch, no cache or config write, no HTTP
 * call. Safe against production; running it twice produces identical output.
 *
 * THE DEFECT. `OrderDiscountTarget::recompute()` reduces the merchandise basis
 * and recomputes ordinary tax, but preserves everything else in the grand
 * total untouched:
 *
 *     $otherComponents = $origGrand - $subtotal - $baseTax;
 *
 * Special tax is BASIS-DERIVED (`$itemSubTotal × special_taxes/100`, per
 * CartHelper::buildCartItem()). When the basis shrinks, special tax must
 * shrink with it. It does not, so the customer is charged special tax on
 * merchandise value they were not charged for.
 *
 * SOURCES OF TRUTH. Per instruction, this reads ONLY:
 *   - each line's frozen `order_products.product_data` snapshot, for the
 *     special tax and added fees actually charged at checkout;
 *   - stored adjustment values on the order (`pretax_discount_total`,
 *     `subtotal`, `tax_amount_before_discount`, `grand_total_before_discount`).
 *
 * It NEVER consults the `products` table, `settings`, or any current tax
 * configuration — today's configuration says nothing about what an order was
 * charged last month.
 *
 * THE ARITHMETIC. A pre-tax discount reduces the whole merchandise population
 * proportionally, by
 *
 *     f = (subtotal − pretax_discount_total) ÷ subtotal
 *
 * Special tax is linear in the basis, so the correct figure is the frozen
 * special tax scaled by f. Everything is computed in integer cents.
 */
class StoreCreditSpecialTaxExposureAudit extends Command
{
    protected $signature = 'diagnostics:store-credit-special-tax-exposure
                            {--show-ids : List every affected order}
                            {--csv= : Also write the per-order detail to this path}';

    protected $description = 'Read-only exposure audit: special tax over-collected on pre-tax-discounted orders (writes nothing)';

    public function handle(): int
    {
        $this->info('Scanning discounted orders — read-only, nothing will be written.');
        $this->newLine();

        $rows = [];
        $examined = 0;
        $withSpecialTax = 0;

        DB::table('orders')
            ->select('id', 'order_number', 'order_date', 'customer_id', 'subtotal',
                'tax_amount', 'discount_amount', 'grand_total', 'pretax_discount_total',
                'tax_amount_before_discount', 'grand_total_before_discount', 'invoice_id')
            ->whereNotNull('pretax_discount_total')
            ->where('pretax_discount_total', '>', 0)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->chunk(200, function ($orders) use (&$rows, &$examined, &$withSpecialTax) {
                $ids = $orders->pluck('id')->all();

                $lines = DB::table('order_products')
                    ->select('id', 'order_id', 'sub_total', 'product_data')
                    ->whereIn('order_id', $ids)
                    ->get()
                    ->groupBy('order_id');

                foreach ($orders as $order) {
                    $examined++;

                    // Frozen special tax + added fees, from the checkout snapshot only.
                    $specialCents = 0;
                    $feesCents = 0;
                    $snapshotReadable = true;

                    foreach ($lines->get($order->id, collect()) as $line) {
                        $frozen = self::decode($line->product_data);

                        if ($frozen === null) {
                            $snapshotReadable = false;

                            continue;
                        }

                        $specialCents += self::cents($frozen['special_tax'] ?? 0);
                        $feesCents    += self::cents($frozen['added_fees'] ?? 0);
                    }

                    if ($specialCents <= 0) {
                        continue;   // no special tax charged → no exposure
                    }

                    $withSpecialTax++;

                    $subtotalCents = self::cents($order->subtotal);
                    $discountCents = self::cents($order->pretax_discount_total);

                    if ($subtotalCents <= 0) {
                        continue;
                    }

                    // Corrected = frozen special tax scaled by the surviving
                    // basis fraction. Integer cents, banker-free round-half-up.
                    $remainingCents = max(0, $subtotalCents - $discountCents);
                    $correctedCents = (int) round($specialCents * $remainingCents / $subtotalCents);
                    $overCents      = $specialCents - $correctedCents;

                    if ($overCents <= 0) {
                        continue;
                    }

                    $rows[] = [
                        'order_id'        => (int) $order->id,
                        'order_number'    => $order->order_number,
                        'order_date'      => $order->order_date,
                        'store_credit'    => $discountCents / 100,
                        'stored_special'  => $specialCents / 100,
                        'corrected_special' => $correctedCents / 100,
                        'over'            => $overCents / 100,
                        'added_fees'      => $feesCents / 100,
                        'snapshot_ok'     => $snapshotReadable,
                    ] + self::orderState((int) $order->id, $order);
                }
            });

        return $this->report($rows, $examined, $withSpecialTax);
    }

    /**
     * Payment, refund, invoice, AR and receipt state — everything that decides
     * whether an order can simply be corrected or needs its own workflow.
     *
     * @return array<string,mixed>
     */
    private static function orderState(int $orderId, $order): array
    {
        $model = \App\Models\Orders\Order::find($orderId);
        $summary = $model ? OrderPaymentSummary::for($model) : null;

        $paid = $model ? (float) $model->total_paid : 0.0;
        $isPaid = $model ? (bool) $model->is_paid : false;

        $refunds = DB::table('order_payments')
            ->where('order_id', $orderId)
            ->whereIn('status', ['Refunded', 'Partial Refund'])
            ->count();

        $arRows = DB::table('customer_accounts')
            ->where('order_id', $orderId)
            ->whereNull('deleted_at')
            ->count();

        $invoiced = $order->invoice_id !== null
            || DB::table('customer_accounts')
                ->where('order_id', $orderId)
                ->whereNotNull('invoice_id')
                ->whereNull('deleted_at')
                ->exists();

        $receipt = DB::table('receipts')->where('order_id', $orderId)->orderByDesc('id')->first();
        $receiptState = $receipt === null
            ? 'none'
            : (($receipt->is_email_status ?? 'unsend') === 'send' || $receipt->mail_send_at !== null
                ? 'issued (emailed)'
                : 'created (issue state unknown)');

        // Correctability. An order that has been reported to someone outside
        // itself, or already settled, cannot simply have its total reduced.
        $blockers = [];

        if ($arRows > 0)   { $blockers[] = 'AR posted'; }
        if ($invoiced)     { $blockers[] = 'invoiced'; }
        if ($refunds > 0)  { $blockers[] = 'refunded'; }
        if ($receipt !== null) { $blockers[] = 'receipt exists'; }
        if ($isPaid)       { $blockers[] = 'paid in full (credit would be owed)'; }

        return [
            'payment_status' => $summary
                ? \App\Services\PaymentDescriptionPresenter::orderStatusLabel($summary)
                : 'unknown',
            'total_paid'    => $paid,
            'refunds'       => $refunds,
            'ar_rows'       => $arRows,
            'invoiced'      => $invoiced ? 'yes' : 'no',
            'receipt_state' => $receiptState,
            'correctable'   => $blockers === [] ? 'SAFE' : 'WORKFLOW',
            'blockers'      => $blockers === [] ? '—' : implode(', ', $blockers),
        ];
    }

    /** @param array<int,array<string,mixed>> $rows */
    private function report(array $rows, int $examined, int $withSpecialTax): int
    {
        $this->line("Discounted orders examined:        {$examined}");
        $this->line("…carrying frozen special tax:      {$withSpecialTax}");
        $this->line('AFFECTED (over-collected):         '.count($rows));
        $this->newLine();

        if ($rows === []) {
            $this->info('No exposure. No discounted order over-collected special tax.');

            return self::SUCCESS;
        }

        $aggregate = array_sum(array_column($rows, 'over'));
        $safe = count(array_filter($rows, fn ($r) => $r['correctable'] === 'SAFE'));
        $workflow = count($rows) - $safe;
        $unreadable = count(array_filter($rows, fn ($r) => ! $r['snapshot_ok']));

        $this->error(sprintf('TOTAL OVER-COLLECTED: $%s across %d order(s)', number_format($aggregate, 2), count($rows)));
        $this->newLine();
        $this->table(['Disposition', 'Orders'], [
            ['Safely correctable in place', $safe],
            ['Requires a separate workflow', $workflow],
            ['Partly unreadable snapshot (figure is a floor, not exact)', $unreadable],
        ]);

        if ($this->option('show-ids')) {
            $this->newLine();
            $this->table(
                ['Order', 'Date', 'Store Credit', 'Special (stored)', 'Special (correct)', 'Over', 'Payment', 'Refunds', 'AR', 'Inv', 'Receipt', 'Verdict', 'Blockers'],
                array_map(fn ($r) => [
                    $r['order_number'] ?? $r['order_id'],
                    $r['order_date'],
                    number_format($r['store_credit'], 2),
                    number_format($r['stored_special'], 2),
                    number_format($r['corrected_special'], 2),
                    number_format($r['over'], 2),
                    $r['payment_status'],
                    $r['refunds'],
                    $r['ar_rows'],
                    $r['invoiced'],
                    $r['receipt_state'],
                    $r['correctable'],
                    $r['blockers'],
                ], $rows)
            );
        } else {
            $this->newLine();
            $this->line('Re-run with --show-ids for the per-order detail.');
        }

        if ($path = $this->option('csv')) {
            $this->writeCsv($path, $rows);
            $this->info("Per-order detail written to {$path}");
        }

        $this->newLine();
        $this->warn('This is an exposure measurement only. NOTHING has been changed.');
        $this->line('Historical orders are not to be rewritten automatically. Remediation of the');
        $this->line('WORKFLOW rows in particular involves money already reported to a customer —');
        $this->line('a receipt, an invoice, or a settled payment — and is a business decision.');

        return self::SUCCESS;
    }

    /** @param array<int,array<string,mixed>> $rows */
    private function writeCsv(string $path, array $rows): void
    {
        $fh = fopen($path, 'w');
        fputcsv($fh, array_keys($rows[0]));

        foreach ($rows as $row) {
            fputcsv($fh, array_map(fn ($v) => is_bool($v) ? ($v ? 'yes' : 'no') : $v, $row));
        }

        fclose($fh);
    }

    /** @return array<string,mixed>|null */
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
