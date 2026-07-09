<?php

namespace App\Models\Warranty;

use App\Enums\Warranty\WarrantyCaseEventType;
use App\Enums\Warranty\WarrantyPath;
use App\Enums\Warranty\WarrantyQueue;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Service\ServiceTicket;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A Warranty Case manages the administrative relationship with the
 * manufacturer: intake, diagnostic fee, OEM submission and decisions,
 * reimbursement. The linked Service Ticket performs the technical work.
 * The Warranty Module authorizes work; the Service Module performs work.
 */
class WarrantyCase extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'queue' => 'new_intake',
    ];

    protected $fillable = [
        'case_number',
        'path',
        'queue',
        'customer_id',
        'equipment_id',
        'manufacturer',
        'model',
        'serial_number',
        'engine_serial_number',
        'has_hour_meter',
        'hours',
        'purchase_date',
        'selling_dealer',
        'warranty_registration_number',
        'complaint',
        'internal_notes',
        'diagnostic_fee_amount',
        'diagnostic_fee_taxable',
        'service_ticket_id',
        'created_by',
    ];

    protected $casts = [
        'path'                        => WarrantyPath::class,
        'queue'                       => WarrantyQueue::class,
        'has_hour_meter'              => 'boolean',
        'diagnostic_fee_taxable'      => 'boolean',
        'diagnostic_fee_amount'       => 'decimal:2',
        'purchase_date'               => 'date',
        'diagnostic_fee_collected_at' => 'datetime',
        'awaiting_diagnosis_at'       => 'datetime',
        'ready_to_submit_at'          => 'datetime',
        'waiting_on_manufacturer_at'  => 'datetime',
        'awaiting_customer_decision_at' => 'datetime',
        'approved_for_repair_at'      => 'datetime',
        'awaiting_reimbursement_at'   => 'datetime',
        'closed_at'                   => 'datetime',
        'queue_entered_at'            => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(function (self $case) {
            $dirty = false;
            if (!$case->case_number) {
                $case->case_number = 'WC-' . str_pad((string) $case->id, 5, '0', STR_PAD_LEFT);
                $dirty = true;
            }
            if (!$case->queue_entered_at) {
                $case->queue_entered_at = now();
                $dirty = true;
            }
            if ($dirty) {
                $case->saveQuietly();
            }

            WarrantyCaseEvent::record(
                $case->id,
                WarrantyCaseEventType::Created,
                new: $case->queue->label(),
                notes: $case->serviceTicket?->ticket_number
                    ? 'Linked Service Ticket ' . $case->serviceTicket->ticket_number
                    : null,
            );
        });
    }

    // ── Relations ────────────────────────────────────────────────────

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function serviceTicket()
    {
        return $this->belongsTo(ServiceTicket::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function feeWaivedBy()
    {
        return $this->belongsTo(User::class, 'diagnostic_fee_waived_by');
    }

    public function events()
    {
        return $this->hasMany(WarrantyCaseEvent::class)->latest()->latest('id');
    }

    // ── Diagnostic fee ───────────────────────────────────────────────

    public function feeApplies(): bool
    {
        return $this->path === WarrantyPath::External;
    }

    public function feeSettled(): bool
    {
        return !$this->feeApplies()
            || $this->diagnostic_fee_collected_at !== null
            || $this->diagnostic_fee_waived_by !== null;
    }

    public function feeStatusLabel(): string
    {
        if (!$this->feeApplies()) {
            return 'N/A — internal';
        }
        if ($this->diagnostic_fee_collected_at) {
            return 'Collected' . ($this->diagnostic_fee_amount ? ' · $' . number_format((float) $this->diagnostic_fee_amount, 2) : '');
        }
        if ($this->diagnostic_fee_waived_by) {
            return 'Waived';
        }

        return 'Unpaid' . ($this->diagnostic_fee_amount ? ' · $' . number_format((float) $this->diagnostic_fee_amount, 2) : '');
    }

    public function feeStatusColor(): string
    {
        if (!$this->feeApplies()) {
            return 'bg-gray-100 text-gray-500 border border-gray-200';
        }
        if ($this->diagnostic_fee_collected_at) {
            return 'bg-green-100 text-green-700 border border-green-200';
        }
        if ($this->diagnostic_fee_waived_by) {
            return 'bg-amber-100 text-amber-700 border border-amber-200';
        }

        return 'bg-red-100 text-red-700 border border-red-200';
    }

    // ── Queue engine ─────────────────────────────────────────────────

    /** Human-readable reasons a queue move is not allowed yet. */
    public function transitionBlockers(WarrantyQueue $to): array
    {
        $blockers = [];

        // Intake cannot complete for external customers until the
        // diagnostic fee is collected or a manager has waived it.
        if ($to === WarrantyQueue::AwaitingDiagnosis && !$this->feeSettled()) {
            $blockers[] = 'Collect (or waive) the diagnostic fee before completing intake.';
        }

        return $blockers;
    }

    /**
     * Move the case to a new queue: validates the move, stamps the
     * milestone timestamp, resets the days-in-queue clock, and records
     * a timeline event.
     */
    public function transitionTo(WarrantyQueue $to, ?string $notes = null): bool
    {
        if (!in_array($to, $this->queue->allowedNext(), true) || $this->transitionBlockers($to) !== []) {
            return false;
        }

        $from = $this->queue;

        $this->queue = $to;
        $this->queue_entered_at = now();
        if ($column = $to->timestampColumn()) {
            $this->{$column} = now();
        }
        $this->save();

        WarrantyCaseEvent::record(
            $this->id,
            WarrantyCaseEventType::QueueChanged,
            old: $from->label(),
            new: $to->label(),
            notes: $notes,
        );

        return true;
    }

    /** The next required action for the case's current queue. */
    public function nextRequiredAction(): string
    {
        if ($this->queue === WarrantyQueue::NewIntake && !$this->feeSettled()) {
            return 'Collect Diagnostic Fee';
        }

        return match ($this->queue) {
            WarrantyQueue::NewIntake                => 'Complete Intake',
            WarrantyQueue::AwaitingDiagnosis        => 'Complete Diagnosis',
            WarrantyQueue::ReadyToSubmit            => 'Submit to Manufacturer',
            WarrantyQueue::WaitingOnManufacturer    => 'Record OEM Decision',
            WarrantyQueue::AwaitingCustomerDecision => 'Record Customer Decision',
            WarrantyQueue::ApprovedForRepair        => 'Repair on ' . ($this->serviceTicket?->ticket_number ?? 'linked ticket'),
            WarrantyQueue::AwaitingReimbursement    => 'Track Reimbursement',
            WarrantyQueue::Closed                   => '—',
        };
    }

    public function daysInQueue(): int
    {
        return (int) ($this->queue_entered_at ?? $this->created_at)->diffInDays(now());
    }
}
