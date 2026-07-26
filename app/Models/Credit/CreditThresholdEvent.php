<?php

namespace App\Models\Credit;

use App\Enums\Credit\CreditReviewTaskOutcome;
use App\Enums\Credit\CreditThresholdReviewStatus;
use App\Enums\Credit\CreditThresholdSourceType;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Models\Tasks\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Immutable record of one qualifying credit-threshold crossing. See the
 * migration for the idempotency + decoupling contract. Nothing here recomputes
 * a balance — every financial field is a stored snapshot of the committed
 * posting.
 */
class CreditThresholdEvent extends Model
{
    protected $table = 'credit_threshold_events';

    protected $fillable = [
        'idempotency_key',
        'customer_id',
        'customer_name_snapshot',
        'triggering_order_id',
        'triggering_account_row_id',
        'source_type',
        'source_detail',
        'credit_limit_at_time',
        'balance_before',
        'exposure_added',
        'balance_after',
        'amount_over_limit',
        'responsible_user_id',
        'responsible_context',
        'occurred_at',
        'task_id',
        'task_outcome',
        'review_status',
    ];

    protected $casts = [
        'source_type'          => CreditThresholdSourceType::class,
        'task_outcome'         => CreditReviewTaskOutcome::class,
        'review_status'        => CreditThresholdReviewStatus::class,
        'credit_limit_at_time' => 'decimal:2',
        'balance_before'       => 'decimal:2',
        'exposure_added'       => 'decimal:2',
        'balance_after'        => 'decimal:2',
        'amount_over_limit'    => 'decimal:2',
        'occurred_at'          => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────────

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'triggering_order_id');
    }

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function responsibleUser()
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    // ── Scopes ─────────────────────────────────────────────────────────

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('review_status', CreditThresholdReviewStatus::Open->value);
    }

    public function scopeForCustomer(Builder $query, int $customerId): Builder
    {
        return $query->where('customer_id', $customerId);
    }

    /** Events whose task side-effect needs attention (unassigned / failed). */
    public function scopeNeedsTaskAttention(Builder $query): Builder
    {
        return $query->whereIn('task_outcome', [
            CreditReviewTaskOutcome::DeferredNoAdmin->value,
            CreditReviewTaskOutcome::Failed->value,
        ]);
    }
}
