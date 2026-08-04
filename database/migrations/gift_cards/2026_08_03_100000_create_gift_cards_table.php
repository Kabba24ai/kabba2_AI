<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A gift card — who it is for, how it came to exist, and what it is worth.
 *
 * ── STORED VALUE IS TENDER, NOT A DISCOUNT ────────────────────────────────
 *
 * A gift card pays the price; it does not change it. It never touches
 * `subtotal`, the taxable basis, `tax_amount` or `grand_total` — those are
 * settled before any tender is applied. This is the distinction
 * {@see App\Enums\Discounts\DiscountType} already documents, and the reason
 * gift cards are deliberately NOT part of `product_discounts`: putting them
 * there would make them reduce the taxable basis, under-collecting tax the
 * business genuinely owes.
 *
 * Store Credit is the mirror image and lives in `customer_credits`. The two
 * must never be merged, despite the superficial similarity of "a balance a
 * customer can spend": one is a concession that lowers a bill, the other is
 * money already paid. `CustomerCreditService`'s docblock invites a future
 * `GiftCardService` to write `customer_credits` — that invitation is declined
 * here, both because of the tax difference above and because
 * `customer_credits.customer_id` is required while a gift card frequently has
 * no customer at all (bought for a recipient who has never rented anything).
 *
 * ── THE BALANCE COLUMN IS A CACHE, AND IS NAMED LIKE ONE ──────────────────
 *
 * `cached_balance` is a projection of `gift_card_transactions`, which is the
 * canonical ledger. It exists so a list screen can show 500 balances without
 * 500 aggregate queries. Every value-changing operation recomputes it from the
 * ledger inside the same locked transaction that appends the row. Nothing may
 * read it to authorise a debit — {@see App\Services\GiftCards\GiftCardService}
 * sums the ledger under a lock for that, because a cache that is wrong once is
 * a card that can be spent twice.
 *
 * ── PURCHASED AND GRANTED ARE DIFFERENT MONEY ─────────────────────────────
 *
 * `issuance_class` separates value a customer PAID FOR from value the business
 * GAVE AWAY. Both spend identically at the register and both leave the order
 * fully taxed. They are never the same in the accounts:
 *
 *   purchased → real cash was received; creates a liability the business owes
 *   granted   → no cash ever existed; merchant-funded promotional value
 *
 * Reporting must never sum them into one "gift cards outstanding" figure —
 * that would present money the business owes and money it chose to give away
 * as the same obligation.
 *
 * ── SINGLE-BRAND BY DECISION, EXTENSIBLE BY DESIGN ────────────────────────
 *
 * There is no `tenant_id`. This application is not multi-tenant: no tenancy
 * package, no tenant key on `orders`, `order_payments` or `customers`, and one
 * global Company Settings brand. A tenant column here would be a dead column on
 * a financial table that every future query would still have to honour.
 *
 * `issued_by_store_id` records the PHYSICAL LOCATION that issued the card,
 * which is real and useful for reporting. If genuine multi-tenancy ever
 * arrives, an ownership key is added alongside it and `card_number`'s unique
 * index becomes composite — the only structural change required, and the reason
 * the number is generated with a configurable prefix rather than a bare
 * sequence.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gift_cards', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->nullable()->unique();

            // The human-readable number, e.g. RNK-4820-9137. Prefix comes from
            // the `gift_card_prefix` Company Setting. Never a sequential id:
            // a card number is quoted over the phone and typed at a register,
            // and a guessable one is a redeemable one.
            $table->string('card_number', 32)->unique();

            // Opaque token for the QR / public balance page. Separate from the
            // card number so a QR code can be scanned by anyone holding the
            // card without exposing a value that, with a PIN, redeems it.
            $table->string('lookup_token', 64)->unique();

            // Hashed — a verification secret never needs to be read back.
            // Nullable: PIN enforcement is a policy decision, not a schema one.
            $table->string('pin_hash')->nullable();

            // purchased | granted. See the header.
            $table->string('issuance_class', 16);

            // Populated for granted cards only. Codes, never display strings:
            // labels get reworded and a report grouped on a label silently
            // re-groups when they do — the same reasoning as
            // `order_goodwill_adjustments.reason_code`.
            $table->string('grant_reason_code')->nullable();
            $table->string('grant_reason_category')->nullable();
            $table->text('grant_note')->nullable();

            // Face value at issuance. IMMUTABLE — a correction is a ledger
            // transaction, never an edit to this number.
            $table->decimal('original_value', 12, 2);

            // Cached projection of the ledger. See the header.
            $table->decimal('cached_balance', 12, 2)->default(0);

            $table->string('currency', 3)->default('USD');

            // draft | active | partially_redeemed | fully_redeemed
            // | suspended | cancelled | replaced
            $table->string('status', 32)->default('draft');

            // Both nullable, and usually different people. A card is frequently
            // bought for someone with no customer record at all, which is
            // precisely why this cannot live on `customer_credits`.
            $table->foreignId('purchaser_customer_id')->nullable()
                ->constrained('customers')->nullOnDelete();
            $table->foreignId('recipient_customer_id')->nullable()
                ->constrained('customers')->nullOnDelete();

            $table->string('recipient_name')->nullable();
            $table->string('recipient_email')->nullable();
            $table->string('sender_name')->nullable();
            $table->text('message')->nullable();

            // The physical location that issued it. See the header.
            $table->foreignId('issued_by_store_id')->nullable()
                ->constrained('stores')->nullOnDelete();

            // Which rendered design this card was issued under, so a later
            // branding change cannot silently restyle an already-issued card.
            $table->string('template_version', 32)->nullable();

            // Replacement chain, for a lost or damaged card. Self-referencing.
            $table->foreignId('replaced_by_gift_card_id')->nullable()
                ->constrained('gift_cards')->nullOnDelete();

            // DATETIME, never TIMESTAMP. Production runs MariaDB 10.5 with
            // `explicit_defaults_for_timestamp` OFF, where the first
            // `TIMESTAMP NOT NULL` without an explicit default silently gains
            // `ON UPDATE CURRENT_TIMESTAMP` and later ones default to the zero
            // date. `activated_at` rewriting itself on every row update would
            // make the issuance date of a financial instrument untrustworthy,
            // and it would reproduce only in production — MySQL 8 ships that
            // flag ON. The same reasoning as `order_goodwill_adjustments`.
            $table->dateTime('activated_at')->nullable();
            $table->dateTime('fully_redeemed_at')->nullable();
            $table->dateTime('suspended_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();

            // Deliberately absent: `expires_at`. Expiration is deferred, and a
            // nullable column nothing populates invites a future reader to
            // assume expiry is handled when no job, no rule and no test exists.

            $table->nullableMorphs('created_by');
            $table->nullableMorphs('updated_by');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['issuance_class', 'status'], 'gc_class_status_idx');
            $table->index('status', 'gc_status_idx');
            $table->index('purchaser_customer_id', 'gc_purchaser_idx');
            $table->index('recipient_customer_id', 'gc_recipient_idx');
            $table->index(['issued_by_store_id', 'activated_at'], 'gc_store_activated_idx');
        });

        // Storage-layer backstops. The service refuses first, with an
        // operator-readable message; these refuse even when the service is
        // bypassed by a seeder, a raw insert, or a future caller that does not
        // know the rules exist.
        DB::statement(
            "ALTER TABLE gift_cards
             ADD CONSTRAINT gc_issuance_class_valid
             CHECK (issuance_class IN ('purchased','granted'))"
        );

        // Face value is what was issued; it cannot be negative or zero. A $0
        // card is not a card.
        DB::statement(
            'ALTER TABLE gift_cards
             ADD CONSTRAINT gc_original_value_positive
             CHECK (original_value > 0)'
        );

        // A cached balance may never exceed the face value nor go negative.
        // This is the cheap, always-on check that a projection has not drifted
        // into an impossible state; the ledger remains the authority.
        DB::statement(
            'ALTER TABLE gift_cards
             ADD CONSTRAINT gc_cached_balance_within_bounds
             CHECK (cached_balance >= 0 AND cached_balance <= original_value)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_cards');
    }
};
