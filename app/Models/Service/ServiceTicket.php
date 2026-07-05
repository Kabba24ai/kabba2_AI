<?php

namespace App\Models\Service;

use App\Enums\Service\FinancialResponsibility;
use App\Enums\Service\FinancialStatus;
use App\Enums\Service\RepairStatus;
use App\Enums\Service\ServiceLocation;
use App\Enums\Service\ServicePriority;
use App\Enums\Service\ServiceType;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceTicket extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ticket_number',
        'service_type',
        'service_location',
        'priority',
        'repair_status',
        'financial_responsibility',
        'financial_status',
        'description',
        'customer_complaint',
        'technician_diagnosis',
        'root_cause',
        'repair_summary',
        'internal_notes',
        'equipment_id',
        'order_id',
        'customer_id',
        'rental_date',
        'blocked_reason',
        'expected_action_date',
        'opened_at',
        'completed_at',
        'closed_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'service_type'             => ServiceType::class,
        'service_location'         => ServiceLocation::class,
        'priority'                 => ServicePriority::class,
        'repair_status'            => RepairStatus::class,
        'financial_responsibility' => FinancialResponsibility::class,
        'financial_status'         => FinancialStatus::class,
        'rental_date'              => 'date',
        'expected_action_date'     => 'date',
        'opened_at'                => 'datetime',
        'completed_at'             => 'datetime',
        'closed_at'                => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(function (self $ticket) {
            if (!$ticket->ticket_number) {
                $ticket->ticket_number = 'SVC-' . str_pad((string) $ticket->id, 5, '0', STR_PAD_LEFT);
                $ticket->saveQuietly();
            }
        });
    }

    // ── Relationships ──────────────────────────────────────────────

    /** HRM employees assigned to this ticket — users table is the source of truth. */
    public function personnel()
    {
        return $this->belongsToMany(User::class, 'service_ticket_personnel', 'service_ticket_id', 'employee_id')
            ->withTimestamps();
    }

    public function resourceDispatches()
    {
        return $this->hasMany(ServiceTicketResourceDispatch::class);
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes ─────────────────────────────────────────────────────

    /** Anything not completed/closed/cancelled. */
    public function scopeOpen(Builder $q): Builder
    {
        return $q->whereIn('repair_status', RepairStatus::notFinished());
    }

    /** Work that can be acted on now. */
    public function scopeActiveQueue(Builder $q): Builder
    {
        return $q->whereIn('repair_status', RepairStatus::active());
    }

    /** Work waiting on parts / approvals — cannot be acted on now. */
    public function scopeBlockedQueue(Builder $q): Builder
    {
        return $q->whereIn('repair_status', RepairStatus::blocked());
    }

    // ── Status transitions ─────────────────────────────────────────

    /**
     * Apply a repair-status change with the Phase 2A transition rules:
     * entering a blocked status stores the blocking context; returning to
     * an active status clears it (history preservation is a later phase);
     * completed/closed stamp their timestamps once and reopening keeps them.
     */
    public function transitionTo(RepairStatus $status, ?string $blockedReason = null, $expectedActionDate = null): void
    {
        $this->repair_status = $status;

        if (in_array($status->value, RepairStatus::blocked(), true)) {
            $this->blocked_reason       = $blockedReason ?? $this->blocked_reason;
            $this->expected_action_date = $expectedActionDate ?? $this->expected_action_date;
        } else {
            $this->blocked_reason       = null;
            $this->expected_action_date = null;
        }

        if ($status === RepairStatus::Completed && !$this->completed_at) {
            $this->completed_at = now();
        }
        if ($status === RepairStatus::Closed && !$this->closed_at) {
            $this->closed_at = now();
        }

        $this->save();
    }

    // ── Presentation helpers ───────────────────────────────────────

    /** Whole days since the ticket was opened. */
    public function getAgeDaysAttribute(): int
    {
        return (int) $this->opened_at?->startOfDay()->diffInDays(now()->startOfDay());
    }

    /** Is the ticket currently in a blocked status? */
    public function getIsBlockedAttribute(): bool
    {
        return in_array($this->repair_status->value, RepairStatus::blocked(), true);
    }
}
