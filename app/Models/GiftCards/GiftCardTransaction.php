<?php

namespace App\Models\GiftCards;

use App\Enums\GiftCards\GiftCardTransactionType;
use App\Enums\Orders\OrderPaymentMethod;
use App\Models\Orders\Order;
use App\Models\Orders\OrderPayment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One movement of gift-card value. Append-only.
 *
 * Rows are never updated and never deleted — the model refuses both below. A
 * mistake is corrected by appending its opposite with `reverses_transaction_id`
 * set, which is why the table has no `updated_at`: offering the column would
 * invite the mutation this ledger exists to forbid.
 */
class GiftCardTransaction extends Model
{
    /** Append-only: rows are written once, at creation. */
    public const UPDATED_AT = null;

    protected $fillable = [
        'gift_card_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'order_id',
        'order_payment_id',
        'funding_payment_method',
        'funding_transaction_id',
        'funding_cash_amount',
        'funding_voided_at',
        'reverses_transaction_id',
        'idempotency_key',
        'reason',
        'note',
        'created_by_id',
        'created_by_type',
        'created_at',
    ];

    protected $casts = [
        'type' => GiftCardTransactionType::class,
        'funding_payment_method' => OrderPaymentMethod::class,
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'funding_cash_amount' => 'decimal:2',
        'funding_voided_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $model) {
            $model->created_at ??= now();
        });

        static::updating(function () {
            throw new \LogicException(
                'gift_card_transactions is append-only. Correct a row by appending its '
                .'opposite with reverses_transaction_id set — never by editing it.'
            );
        });

        static::deleting(function () {
            throw new \LogicException(
                'gift_card_transactions is append-only and cannot be deleted. '
                .'A balance with no history cannot be reconciled or audited.'
            );
        });
    }

    // ── Relations ─────────────────────────────────────────────────────────

    public function giftCard(): BelongsTo
    {
        return $this->belongsTo(GiftCard::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** The ordinary `GiftCard`-method payment a redemption produced. */
    public function orderPayment(): BelongsTo
    {
        return $this->belongsTo(OrderPayment::class, 'order_payment_id');
    }

    public function reverses(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reverses_transaction_id');
    }

    public function createdBy()
    {
        return $this->morphTo();
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeRedemptions($query)
    {
        return $query->where('type', GiftCardTransactionType::Redemption->value);
    }

    /**
     * Funding rows that represent cash the business actually kept.
     *
     * Excludes voided funding: a charge that was reversed is not money the
     * business holds, and reconciliation Stream E must not claim it against a
     * settlement that will never arrive.
     */
    public function scopeSettledFunding($query)
    {
        return $query
            ->where('type', GiftCardTransactionType::IssuancePurchased->value)
            ->whereNull('funding_voided_at')
            ->whereNotNull('funding_cash_amount');
    }

    // ── Semantics ─────────────────────────────────────────────────────────

    /** Did real external cash enter the business because of this row? */
    public function bringsExternalCash(): bool
    {
        return $this->type instanceof GiftCardTransactionType
            && $this->type->bringsExternalCash()
            && $this->funding_voided_at === null;
    }

    /** Cash the business kept from this funding row. Zero for everything else. */
    public function settledFundingCash(): float
    {
        return $this->bringsExternalCash()
            ? round((float) $this->funding_cash_amount, 2)
            : 0.0;
    }
}
