<?php

namespace App\Models\WaitList;

use App\Enums\WaitList\WaitListAlertStatus;
use App\Enums\WaitList\WaitListMatchType;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\ProductManagement\ProductCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A wait list match fired when equipment was returned/checked in. Alerts are
 * internal-only, never expire, and remain viewable in history after being
 * acknowledged or dismissed.
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
        'equipment_status_at_match',
        'status',
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

    public function acknowledgedBy()
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function scopeOpen(Builder $q): Builder
    {
        return $q->where('status', WaitListAlertStatus::Unacknowledged->value);
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

    public function dismiss(?int $userId = null): void
    {
        if ($this->status !== WaitListAlertStatus::Unacknowledged) {
            return;
        }

        $this->update([
            'status'       => WaitListAlertStatus::Dismissed,
            'dismissed_by' => $userId ?? auth()->id(),
            'dismissed_at' => now(),
        ]);
    }
}
