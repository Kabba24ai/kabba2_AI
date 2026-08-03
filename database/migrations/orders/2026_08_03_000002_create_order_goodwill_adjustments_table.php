<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Why a discretionary concession was granted — never how much it was worth.
 *
 * ── THIS TABLE IS NOT A FINANCIAL SOURCE OF TRUTH ─────────────────────────
 *
 * The money lives in `product_discounts`, `orders`, `order_products` and
 * `order_product_discount_allocations`, all written by the shared pre-tax
 * adjustment engine. This table records the BUSINESS DECISION that caused the
 * engine to be invoked: who authorized it, under which reason, against which
 * collected payment, and what the order looked like on either side of it.
 *
 * Consequently the concession amount, the discounted product value, the
 * recalculated taxes and the per-line shares are deliberately ABSENT. They are
 * reached through `product_discount_id`. A figure stored twice is a figure that
 * can disagree with itself, and on a financial record that disagreement is
 * indistinguishable from fraud.
 *
 * ── WHAT IS GENUINELY OWNED HERE ──────────────────────────────────────────
 *
 * The two snapshots are not duplicates of anything. `product_discounts` records
 * product value and ordinary tax only — it has no concept of special tax, added
 * fees, grand total, or how much the customer had actually paid. Those are the
 * figures an auditor needs to reconstruct the moment of the decision, and this
 * is the only table that holds them together.
 *
 * `accepted_payment_total` answers "how much had the customer paid when the
 * manager approved this?" — a question that becomes unanswerable later, because
 * subsequent refunds, voids or further payments move every live figure it could
 * otherwise be derived from.
 *
 * ── THE ONE-ACTIVE GUARANTEE ──────────────────────────────────────────────
 *
 * `active_order_id` holds the order id while the adjustment is applied and NULL
 * once reversed. The UNIQUE index on it makes "at most one active Goodwill
 * adjustment per order" a database guarantee rather than a convention — neither
 * MySQL nor MariaDB has partial indexes, and NULLs do not collide in a unique
 * index on either, so any
 * number of reversed rows may coexist with at most one live one. An application
 * check under a row lock can be bypassed by a path that forgets to take the
 * lock; this cannot.
 *
 * ── THE ROUNDING RESIDUAL ─────────────────────────────────────────────────
 *
 * A pre-tax concession moves the grand total by more than its own value,
 * because both basis-derived taxes move with it. The total therefore steps down
 * by one to three cents per cent of concession, and an exact landing on the
 * collected amount is not always reachable. The unavoidable remainder is stored
 * EXPLICITLY rather than absorbed into the concession, and the CHECK constraint
 * below caps it at two cents in either direction. Anything larger is not
 * rounding — it is a calculation that did not converge, and it must fail loudly
 * at the storage layer even if every application guard above it were removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_goodwill_adjustments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            // The financial adjustment this decision produced. RESTRICT, not
            // cascade: `product_discounts` is an append-only ledger, so a
            // deletion there would mean the ledger contract had already been
            // violated. Refusing it here surfaces that rather than quietly
            // erasing the authorization record along with it.
            $table->foreignId('product_discount_id')
                ->constrained('product_discounts')
                ->restrictOnDelete();

            $table->foreignId('reversal_product_discount_id')
                ->nullable()
                ->constrained('product_discounts')
                ->nullOnDelete();

            $table->string('status')->default('applied'); // applied | reversed

            // order_id while applied, NULL once reversed. See the header.
            $table->unsignedBigInteger('active_order_id')->nullable();
            $table->unique('active_order_id', 'oga_one_active_per_order');

            // App\Enums\Goodwill\GoodwillReason — the stable code, never a
            // display string. Labels change with the copy deck; codes do not,
            // and a report grouped on a label silently re-groups when marketing
            // rewords a dropdown.
            $table->string('reason_code');

            // App\Enums\Goodwill\GoodwillReasonCategory — denormalized from the
            // code so "Service Recovery vs Business Courtesy" is a plain indexed
            // WHERE rather than a mapping every consumer has to re-implement.
            // The model derives it on every save; it is never set by hand.
            $table->string('reason_category');

            $table->text('note')->nullable(); // mandatory when reason_code = OTHER

            // The manager who authorized. Distinct from the operator who
            // executed: authority to take a payment must never imply authority
            // to reduce revenue.
            $table->foreignId('approved_by')
                ->constrained('users')
                ->restrictOnDelete();

            // DATETIME, NOT TIMESTAMP — see the note above the timestamps below.
            $table->dateTime('approved_at');

            $table->foreignId('applied_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Settled payments at the moment of approval — an immutable audit
            // snapshot, not a live figure and not a second source of truth.
            $table->decimal('accepted_payment_total', 12, 2);

            // Signed: collected minus revised grand total. Zero on an exact
            // close. Bounded by the CHECK constraint added below.
            $table->decimal('rounding_residual', 12, 2)->default(0);

            // The real payment that preceded the concession. Nullable and
            // convenience-only — null when several settled payments exist,
            // exactly the convention `order_payments.parent_order_payment_id`
            // documents for the same single-scalar limitation.
            $table->foreignId('payment_id')
                ->nullable()
                ->constrained('order_payments')
                ->nullOnDelete();

            // Which payment rows made up accepted_payment_total. Identity only;
            // no amount is ever read back from here for calculation.
            $table->json('settled_payments_snapshot')->nullable();

            // Order-level position either side of the adjustment: subtotal,
            // pretax_discount_total, tax_amount, special_tax_amount,
            // added_fees_amount, grand_total, total_paid, balance_due.
            $table->json('before_snapshot');
            $table->json('after_snapshot');

            $table->string('idempotency_key')->unique();

            // ── WHY DATETIME AND NOT TIMESTAMP ─────────────────────────────
            //
            // Production runs MariaDB 10.5, where
            // `explicit_defaults_for_timestamp` defaults to OFF and the legacy
            // TIMESTAMP rules therefore apply:
            //
            //   - the FIRST `TIMESTAMP NOT NULL` column declared without an
            //     explicit default silently acquires
            //     `DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`;
            //   - every later one acquires `DEFAULT '0000-00-00 00:00:00'`.
            //
            // On this table that would have made `approved_at` rewrite itself
            // to NOW() on **every UPDATE of the row** — including the update
            // that records a reversal. An audit record whose "when was this
            // authorized" moves each time the row is touched is worse than
            // useless: it looks authoritative and is not. The model's
            // immutability guard could not prevent it, because the database
            // would be doing it underneath the application.
            //
            // It could not be caught locally either: MySQL 8+ ships
            // `explicit_defaults_for_timestamp = ON`, which disables the whole
            // behaviour, so the schema was correct on the development engine
            // and would have been wrong in production only.
            //
            // DATETIME has no auto-initialisation, no auto-update, no timezone
            // conversion and no 2038 ceiling. For a value the application
            // supplies and nothing may rewrite, it is the correct type on both
            // engines.
            //
            // NO DEFAULT and NO useCurrent() anywhere: every one of these is
            // written explicitly by the service, and a database-supplied value
            // would mask a caller that forgot to set one.
            $table->dateTime('applied_at');
            $table->dateTime('reversed_at')->nullable();
            $table->foreignId('reversed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('reversal_reason')->nullable();

            $table->timestamps();

            $table->index(['order_id', 'status'], 'oga_order_status_idx');
            $table->index(['reason_category', 'approved_at'], 'oga_category_date_idx');
            $table->index('reason_code', 'oga_reason_idx');
            $table->index('product_discount_id', 'oga_discount_idx');
        });

        // Storage-layer backstop for the two-cent bound. The model refuses
        // first, with an operator-readable message; this refuses even if the
        // model is bypassed by a raw insert, a seeder or a future service that
        // does not know the rule exists.
        DB::statement(
            'ALTER TABLE order_goodwill_adjustments
             ADD CONSTRAINT oga_rounding_residual_bounded
             CHECK (rounding_residual >= -0.02 AND rounding_residual <= 0.02)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('order_goodwill_adjustments');
    }
};
