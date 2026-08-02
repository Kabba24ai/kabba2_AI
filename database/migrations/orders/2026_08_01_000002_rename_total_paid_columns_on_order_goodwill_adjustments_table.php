<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Replace `total_paid_before` / `total_paid_after` with a single, honest
 * `payments_accepted` column.
 *
 * The original pair implied that a Goodwill Adjustment moves money — that
 * there is a "before" and an "after" amount collected. There is not. Goodwill
 * is never a payment method and never creates tender; it reduces the pre-tax
 * product cost so the revised grand total equals what was ALREADY collected.
 * The two columns were therefore always written with the identical value,
 * which is exactly the kind of field a future reader mistakes for evidence
 * that a payment was recorded.
 *
 * `payments_accepted` states the one fact that matters: the cumulative settled
 * payments, re-read under the row lock, that management accepted as payment in
 * full at the moment of adjustment. What genuinely does change — the order's
 * derived payment status — is still recorded, in payment_status_before /
 * payment_status_after.
 *
 * The Goodwill feature has never been released; this table holds no production
 * rows. The rename is expressed as a forward migration rather than an edit to
 * the create migration (78020401) so committed history is never rewritten.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_goodwill_adjustments', function (Blueprint $table) {
            $table->renameColumn('total_paid_before', 'payments_accepted');
        });

        Schema::table('order_goodwill_adjustments', function (Blueprint $table) {
            $table->dropColumn('total_paid_after');
        });
    }

    public function down(): void
    {
        Schema::table('order_goodwill_adjustments', function (Blueprint $table) {
            $table->renameColumn('payments_accepted', 'total_paid_before');
        });

        Schema::table('order_goodwill_adjustments', function (Blueprint $table) {
            $table->decimal('total_paid_after', 10, 2)->default(0)->after('total_paid_before');
        });
    }
};
