<?php

namespace App\Models\WaitList;

use App\Enums\WaitList\WaitListAlertDisposition;
use App\Enums\WaitList\WaitListAlertStatus;
use App\Enums\WaitList\WaitListMatchType;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A wait list match fired when equipment was returned/checked in: a
 * potentially suitable unit has returned and should be evaluated as an
 * opportunity to satisfy the customer's request — never a promise of
 * availability. Alerts are internal-only, never expire, and remain
 * viewable in history after being resolved.
 *
 * Lifecycle: Contact Needed (unacknowledged) → In Progress (acknowledged,
 * e.g. after Contacted — No Answer) → Resolved (dismissed) with a
 * disposition recording the outcome. Resolving one match never closes the
 * customer's overall request except Customer No Longer Needs Equipment.
 */
class EquipmentWaitListAlert extends Model
{
    protected $table = 'equipment_wait_list_alerts';

    /** In-memory default matching the DB default, so lifecycle checks work on unsaved models. */
    protected $attributes = ['status' => 'unacknowledged'];

    protected $fillable = [
        'equipment_wait_list_id',
        'equipment_id',
        'match_type',
        'matched_category_id',
        'matched_product_id',
        'equipment_status_at_match',
        'status',
        'disposition',
        'acknowledged_by',
        'acknowledged_at',
        'dismissed_by',
        'dismissed_at',
        'first_push_sent_at',
        'second_push_sent_at',
        'push_deferred_until',
    ];

    protected $casts = [
        'match_type'          => WaitListMatchType::class,
        'status'              => WaitListAlertStatus::class,
        'disposition'         => WaitListAlertDisposition::class,
        'acknowledged_at'     => 'datetime',
        'dismissed_at'        => 'datetime',
        'first_push_sent_at'  => 'datetime',
        'second_push_sent_at' => 'datetime',
        'push_deferred_until' => 'datetime',
    ];

    public function waitList()
    {
        return $this->belongsTo(EquipmentWaitList::class, 'equipment_wait_list_id');
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function matchedCategory()
    {
        return $this->belongsTo(ProductCategory::class, 'matched_category_id');
    }

    /** The returned unit's product, snapshotted at match time. */
    public function matchedProduct()
    {
        return $this->belongsTo(Product::class, 'matched_product_id');
    }

    public function acknowledgedBy()
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    /**
     * Actionable contact opportunities: everything not yet resolved.
     * Contact Needed AND In Progress both still represent live work.
     */
    public function scopeOpen(Builder $q): Builder
    {
        return $q->whereIn('status', [
            WaitListAlertStatus::Unacknowledged->value,
            WaitListAlertStatus::Acknowledged->value,
        ]);
    }

    /** Record who acknowledged and when; alert stays viewable in history. */
    public function acknowledge(?int $userId = null): void
    {
        if ($this->status !== WaitListAlertStatus::Unacknowledged) {
            return;
        }

        $this->update([
            'status'          => WaitListAlertStatus::Acknowledged,
            'acknowledged_by' => $userId ?? auth()->id(),
            'acknowledged_at' => now(),
        ]);

        $this->waitList?->markAcknowledged();
    }

    /**
     * Record the outcome of working this match. Keep Waiting, Customer
     * Declined, and Match Unavailable resolve only THIS opportunity — the
     * customer's request stays active. Customer No Longer Needs Equipment
     * cancels the request itself. Customer Accepted resolves the alert and
     * hands off to the existing manual Convert workflow (staff creates the
     * order and links it; nothing is reserved or promised automatically).
     */
    public function dispose(WaitListAlertDisposition $disposition, ?int $userId = null): void
    {
        if ($this->status === WaitListAlertStatus::Dismissed) {
            return; // already resolved
        }

        // Atomic: the status change, communication-history entry, and any
        // parent-record transition land together or not at all — no partial
        // dispositions.
        \Illuminate\Support\Facades\DB::transaction(function () use ($disposition, $userId) {
            $this->applyDisposition($disposition, $userId ?? auth()->id());
        });
    }

    private function applyDisposition(WaitListAlertDisposition $disposition, ?int $userId): void
    {
        if ($disposition->resolvesAlert()) {
            $this->update([
                'status'       => WaitListAlertStatus::Dismissed,
                'disposition'  => $disposition,
                'dismissed_by' => $userId,
                'dismissed_at' => now(),
            ]);
        } else {
            // Contacted — No Answer: stays actionable for another attempt
            $this->update([
                'status'          => WaitListAlertStatus::Acknowledged,
                'disposition'     => $disposition,
                'acknowledged_by' => $this->acknowledged_by ?? $userId,
                'acknowledged_at' => $this->acknowledged_at ?? now(),
            ]);
        }

        // Every disposition leaves a trace in the record's communication history
        $this->waitList?->communications()->create([
            'user_id' => $userId,
            'type'    => $disposition->communicationType(),
            'note'    => 'Match alert #' . $this->id . ' — ' . $disposition->label()
                . ($this->equipment ? ' (' . $this->equipment->equipment_name . ')' : ''),
        ]);

        if ($disposition->closesWaitList()) {
            $this->waitList?->cancel();
        } elseif ($disposition === WaitListAlertDisposition::CustomerAccepted) {
            $this->waitList?->markAcknowledged();
        }
    }

    /** @deprecated superseded by dispose(); kept for the legacy dismiss route. */
    public function dismiss(?int $userId = null): void
    {
        if ($this->status === WaitListAlertStatus::Dismissed) {
            return;
        }

        $this->update([
            'status'       => WaitListAlertStatus::Dismissed,
            'dismissed_by' => $userId ?? auth()->id(),
            'dismissed_at' => now(),
        ]);
    }
}
