<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Snapshot of special tax at the ORIGINAL, undiscounted basis.
 *
 * Completes the `*_before_discount` trio that `OrderDiscountTarget` already
 * uses for ordinary tax and grand total. Without it, correcting special tax on
 * a pre-tax discount is impossible to do idempotently: the corrected value
 * would be written back over the only record of what the original was, so a
 * second apply, or a reverse, would scale an already-scaled figure and
 * compound the error.
 *
 * `captureOriginalSnapshotOnce()` writes it on the first adjustment, exactly
 * as it already does for `tax_amount_before_discount` and
 * `grand_total_before_discount`. Every recompute derives from the snapshot,
 * never from current state, which is what makes stacking and reversal exact.
 *
 * BACKFILL. Orders that ALREADY carry a pre-tax discount have a captured tax
 * and grand-total snapshot but no special-tax one. Their `special_tax_amount`
 * (populated by the previous migration from frozen `product_data`) is still
 * the ORIGINAL figure, because the defect this release fixes is precisely that
 * the engine never reduced it. Copying it across is therefore exact, not an
 * approximation.
 *
 * ══ ORDERING DEPENDENCY — THIS MIGRATION MUST RUN BEFORE THE CORRECTED
 *    RECOMPUTE TOUCHES ANY PRE-EXISTING DISCOUNTED ORDER ══
 *
 * The copy above is only valid while `special_tax_amount` still holds the
 * ORIGINAL figure. After the correction ships, a recompute reduces it. So:
 *
 *   - Deploying this migration together with the corrected `OrderDiscountTarget`
 *     is SAFE, because the migration runs first.
 *   - Running it LATER — on a database where the corrected code has already
 *     re-priced a pre-existing discounted order — is UNSAFE. It would copy an
 *     already-reduced figure in as the "original", and every subsequent
 *     recompute would scale that reduced value again, compounding the error
 *     downward on each adjustment.
 *
 * The hazard is specific to orders discounted BEFORE this release, because
 * `captureOriginalSnapshotOnce()` only fires when `grand_total_before_discount`
 * is null. Those orders already have a non-null snapshot, so capture never
 * fires for them and this backfill is their only source of a special-tax
 * original. Orders discounted after the release capture all three together and
 * are unaffected.
 *
 * The `whereNull('special_tax_before_discount')` guard makes a re-run
 * idempotent, but it cannot detect a value that was already reduced. If this
 * migration is ever found to have run out of order, the correct recovery is to
 * restore the original from each line's frozen `product_data`, not to re-run
 * it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('special_tax_before_discount', 10, 2)
                ->nullable()
                ->after('tax_amount_before_discount');
        });

        // Only orders that already have a snapshot need one; a null snapshot
        // on an undiscounted order is correct and is captured on first use.
        DB::table('orders')
            ->whereNotNull('grand_total_before_discount')
            ->whereNull('special_tax_before_discount')
            ->update(['special_tax_before_discount' => DB::raw('special_tax_amount')]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('special_tax_before_discount');
        });
    }
};
