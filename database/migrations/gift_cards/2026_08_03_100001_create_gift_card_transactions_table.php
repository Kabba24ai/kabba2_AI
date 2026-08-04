<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The gift-card ledger. Append-only, and the ONLY authority on a balance.
 *
 * ── WHY A LEDGER AND NOT A COLUMN ─────────────────────────────────────────
 *
 * `gift_cards.cached_balance` is a projection maintained for display. This
 * table is the truth. A balance held only in a column has no history: it cannot
 * say when value was spent, against which order, by whom, or whether a refund
 * restored it — and when it is wrong there is nothing to reconcile it against.
 * The same reasoning `CustomerCreditService` applies to Store Credit, whose
 * balance is always computed live and never separately cached.
 *
 * Rows are NEVER updated and NEVER deleted. A mistake is corrected by appending
 * its opposite, with `reverses_transaction_id` pointing at the row being undone
 * — the discipline `product_discounts` and `customer_credits` already follow,
 * and the reason this table has no `updated_at`.
 *
 * ── BALANCE_BEFORE / BALANCE_AFTER ARE EVIDENCE, NOT INPUT ────────────────
 *
 * They are written by the service inside the locked transaction that appends
 * the row, and are never read back to compute anything — a debit is authorised
 * by summing `amount` under a lock, not by trusting the last row's
 * `balance_after`. They exist so an auditor can walk the ledger and see the
 * running total the service believed at each step; a chain that fails to
 * reconcile is then visible rather than merely wrong.
 *
 * ── FUNDING LIVES HERE, AND IS NOT A GIFTCARD PAYMENT ─────────────────────
 *
 * `funding_*` records how a PURCHASED card was paid for: the real tender —
 * Cash, Card, Cheque — and the processor transaction where one exists. It is
 * never `GiftCard`; that method means redemption. This is what makes the
 * funding cash reconcilable to an Authorize.Net settlement while remaining
 * outside sales revenue, and it is the source of reconciliation Stream E.
 *
 * A gift card sale is deliberately NOT an order: it has no `order_products`,
 * so `CollectedRevenueQuery::qualifyingPayments()` cannot see it and it can
 * never be counted as taxable product revenue. That exclusion is structural,
 * not a predicate someone has to remember to write.
 *
 * ── GRANTED VALUE IS NOT LIABILITY ────────────────────────────────────────
 *
 * `issuance_granted` rows carry no funding and no cash. They are merchant-
 * funded promotional value and must be reported apart from purchased-card
 * liability. Redemption is classified by the CARD's `issuance_class`, so the
 * two can always be told apart downstream even though they spend identically.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gift_card_transactions', function (Blueprint $table) {
            $table->id();

            // RESTRICT: this is an append-only financial ledger. If a card row
            // is ever deleted, the contract has already been broken — refusing
            // here surfaces that rather than quietly erasing the money trail.
            $table->foreignId('gift_card_id')
                ->constrained('gift_cards')
                ->restrictOnDelete();

            // issuance_purchased | issuance_granted | redemption
            // | redemption_reversal | refund_to_card | adjustment_increase
            // | adjustment_decrease | cancellation | replacement_transfer
            $table->string('type', 32);

            // SIGNED. Positive adds value, negative removes it. The balance is
            // SUM(amount) — no type-to-direction mapping to keep in step, and
            // no way for a new type to be added with the wrong sign convention.
            $table->decimal('amount', 12, 2);

            // Evidence, not input. See the header.
            $table->decimal('balance_before', 12, 2);
            $table->decimal('balance_after', 12, 2);

            // Redemption context. Both nullable — issuance has neither.
            $table->foreignId('order_id')->nullable()
                ->constrained('orders')->nullOnDelete();

            // The ordinary OrderPayment (method `GiftCard`) this redemption
            // produced. The link that lets reporting identify which payment
            // rows are non-cash tender, and which card class funded them.
            $table->foreignId('order_payment_id')->nullable()
                ->constrained('order_payments')->nullOnDelete();

            // ── Funding, for purchased issuance only ──────────────────────
            //
            // The REAL tender. Never `GiftCard`; enforced by CHECK below.
            $table->string('funding_payment_method', 32)->nullable();

            // Gateway transaction, when the funding went through a processor.
            // What makes Stream E reconcilable to an Authorize.Net settlement.
            $table->string('funding_transaction_id')->nullable();

            // Net of voids/refunds of the funding itself: a funding that was
            // charged back is not cash the business kept, and Stream E must
            // not claim it. Held separately from `amount` because the card's
            // value and the cash behind it are different questions.
            $table->decimal('funding_cash_amount', 12, 2)->nullable();
            $table->dateTime('funding_voided_at')->nullable();

            // The row this one undoes. Self-referencing; no row is ever edited.
            $table->foreignId('reverses_transaction_id')->nullable()
                ->constrained('gift_card_transactions')->nullOnDelete();

            // Duplicate protection. A retried request — a double-clicked
            // button, a replayed webhook, a queue redelivery — returns the
            // existing row instead of moving value twice. UNIQUE, so the
            // guarantee is the database's rather than a check-then-write race.
            $table->string('idempotency_key')->unique();

            $table->string('reason')->nullable();
            $table->text('note')->nullable();

            $table->nullableMorphs('created_by');

            // DATETIME, never TIMESTAMP — MariaDB 10.5 with
            // `explicit_defaults_for_timestamp` OFF would auto-update the
            // first TIMESTAMP column on every row write. Ledger rows are never
            // updated, but the guarantee must not depend on that holding.
            $table->dateTime('created_at');

            // No `updated_at`. Append-only rows are not updated, and offering
            // the column invites the exact mutation this table forbids.

            $table->index(['gift_card_id', 'id'], 'gct_card_seq_idx');
            $table->index(['type', 'created_at'], 'gct_type_date_idx');
            $table->index('order_id', 'gct_order_idx');
            $table->index('order_payment_id', 'gct_payment_idx');

            // Stream E's access path: funding rows in a reporting window.
            $table->index(['funding_payment_method', 'created_at'], 'gct_funding_date_idx');
        });

        DB::statement(
            "ALTER TABLE gift_card_transactions
             ADD CONSTRAINT gct_type_valid
             CHECK (type IN (
                'issuance_purchased','issuance_granted','redemption',
                'redemption_reversal','refund_to_card','adjustment_increase',
                'adjustment_decrease','cancellation','replacement_transfer'
             ))"
        );

        // A zero-amount ledger row records nothing and would silently pass
        // every balance assertion while looking like activity.
        DB::statement(
            'ALTER TABLE gift_card_transactions
             ADD CONSTRAINT gct_amount_non_zero
             CHECK (amount <> 0)'
        );

        // A balance can never go negative, whatever appended the row.
        DB::statement(
            'ALTER TABLE gift_card_transactions
             ADD CONSTRAINT gct_balance_after_non_negative
             CHECK (balance_after >= 0)'
        );

        // `GiftCard` is a REDEMPTION method. Funding a gift card with a gift
        // card would create value from nothing and make Stream E circular.
        DB::statement(
            "ALTER TABLE gift_card_transactions
             ADD CONSTRAINT gct_funding_method_is_real_tender
             CHECK (funding_payment_method IS NULL OR funding_payment_method <> 'GiftCard')"
        );

        // Granted value is never cash. Enforced at the storage layer because
        // the whole promotional-vs-liability separation downstream depends on
        // it, and a single mis-set column would silently merge them.
        DB::statement(
            "ALTER TABLE gift_card_transactions
             ADD CONSTRAINT gct_granted_has_no_funding
             CHECK (type <> 'issuance_granted'
                    OR (funding_payment_method IS NULL AND funding_cash_amount IS NULL))"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_card_transactions');
    }
};
