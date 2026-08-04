<?php

namespace App\Models\GiftCards;

use App\Enums\GiftCards\GiftCardIssuanceClass;
use App\Enums\GiftCards\GiftCardStatus;
use App\Helpers\ModelHelper;
use App\Models\Customers\Customer;
use App\Models\Stores\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A gift card.
 *
 * ── THE BALANCE ON THIS MODEL IS A CACHE ──────────────────────────────────
 *
 * `cached_balance` is a projection of `gift_card_transactions`. It is here so a
 * list screen can render without an aggregate per row. It must NEVER authorise
 * a debit: {@see App\Services\GiftCards\GiftCardService} sums the ledger under
 * a row lock for that. `availableBalance()` below reads the ledger, and is the
 * method any correctness-sensitive caller should use.
 *
 * The distinction matters because the two can diverge — a crash between the
 * ledger append and the cache write, a raw SQL update, a restored backup — and
 * a card whose cache reads high is a card that can be spent twice.
 */
class GiftCard extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'unique_id',
        'card_number',
        'lookup_token',
        'pin_hash',
        'issuance_class',
        'grant_reason_code',
        'grant_reason_category',
        'grant_note',
        'original_value',
        'cached_balance',
        'currency',
        'status',
        'purchaser_customer_id',
        'recipient_customer_id',
        'recipient_name',
        'recipient_email',
        'sender_name',
        'message',
        'issued_by_store_id',
        'template_version',
        'replaced_by_gift_card_id',
        'activated_at',
        'fully_redeemed_at',
        'suspended_at',
        'suspension_reason',
        'suspended_by',
        'reinstated_at',
        'reinstatement_reason',
        'reinstated_by',
        'cancelled_at',
        'created_by_id',
        'created_by_type',
        'updated_by_id',
        'updated_by_type',
    ];

    protected $casts = [
        'issuance_class' => GiftCardIssuanceClass::class,
        'status' => GiftCardStatus::class,
        'original_value' => 'decimal:2',
        'cached_balance' => 'decimal:2',
        'activated_at' => 'datetime',
        'fully_redeemed_at' => 'datetime',
        'suspended_at' => 'datetime',
        'reinstated_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Columns that describe an issuance that has already happened.
     *
     * The face value of an issued instrument is not editable — a correction is
     * a ledger transaction. Changing `original_value` after the fact would
     * rewrite history and, because of the CHECK constraint binding
     * `cached_balance <= original_value`, could also make a legitimately-held
     * balance unrepresentable.
     */
    private const IMMUTABLE_AFTER_ACTIVATION = [
        'card_number',
        'original_value',
        'issuance_class',
        'activated_at',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $model) {
            $model->unique_id ??= ModelHelper::generateUniqueID($model, 'GC');
            $model->created_at ??= now();
        });

        static::updating(function (self $model) {
            $model->assertIssuanceNotRewritten();
        });
    }

    // ── Relations ─────────────────────────────────────────────────────────

    public function transactions(): HasMany
    {
        return $this->hasMany(GiftCardTransaction::class)->orderBy('id');
    }

    public function purchaser(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'purchaser_customer_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'recipient_customer_id');
    }

    public function issuedByStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'issued_by_store_id');
    }

    public function replacedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaced_by_gift_card_id');
    }

    public function createdBy()
    {
        return $this->morphTo();
    }

    public function updatedBy()
    {
        return $this->morphTo();
    }

    // ── Balance ───────────────────────────────────────────────────────────

    /**
     * The authoritative balance, summed from the ledger.
     *
     * Use this — not `cached_balance` — anywhere the answer decides whether
     * value may be spent. Callers inside a redemption must additionally hold
     * the row lock; see GiftCardService::redeem().
     */
    public function availableBalance(): float
    {
        return round((float) $this->transactions()->sum('amount'), 2);
    }

    /** Has the cached projection drifted from the ledger? */
    public function cacheIsStale(): bool
    {
        return $this->cents($this->cached_balance) !== $this->cents($this->availableBalance());
    }

    // ── State ─────────────────────────────────────────────────────────────

    /**
     * A necessary condition for redemption, never a sufficient one. The
     * balance and the requested amount are checked separately, under a lock.
     */
    public function isRedeemable(): bool
    {
        return $this->status instanceof GiftCardStatus && $this->status->isRedeemable();
    }

    public function isPurchased(): bool
    {
        return $this->issuance_class === GiftCardIssuanceClass::Purchased;
    }

    public function isGranted(): bool
    {
        return $this->issuance_class === GiftCardIssuanceClass::Granted;
    }

    /**
     * Masked for display where the full number should not appear — the public
     * balance page, a shared screen. Keeps the last four so a holder can still
     * identify their own card.
     */
    public function maskedNumber(): string
    {
        $number = (string) $this->card_number;

        return strlen($number) <= 4
            ? $number
            : str_repeat('•', max(0, strlen($number) - 4)).substr($number, -4);
    }

    // ── Guards ────────────────────────────────────────────────────────────

    private function assertIssuanceNotRewritten(): void
    {
        if ($this->activated_at === null && $this->getOriginal('activated_at') === null) {
            return;
        }

        foreach (self::IMMUTABLE_AFTER_ACTIVATION as $column) {
            if (! $this->isDirty($column)) {
                continue;
            }

            throw new \LogicException(
                "GiftCard::{$column} cannot be changed after activation. "
                .'The face value and identity of an issued instrument are fixed; '
                .'correct it with a gift_card_transactions row instead.'
            );
        }
    }

    private function cents($value): int
    {
        return (int) round(((float) $value) * 100);
    }
}
