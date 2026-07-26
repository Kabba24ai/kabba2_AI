<?php

namespace App\Models\Service;

use App\Enums\Service\ApprovalStatus;
use App\Enums\Service\ApprovalType;
use App\Enums\Service\DiagnosticStatus;
use App\Enums\Service\FinancialResponsibility;
use App\Enums\Service\FinancialStatus;
use App\Enums\Service\RepairStatus;
use App\Enums\Service\ServiceLocation;
use App\Enums\Service\ServicePriority;
use App\Enums\Service\ServiceTicketEventType;
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

    /** In-memory defaults matching the DB column defaults, so lifecycle
     *  helpers work on unsaved/unrefreshed models. */
    protected $attributes = [
        'diagnostic_status'       => 'not_started',
        'responsibility_decision' => 'pending',
        'approval_status'         => 'not_required',
    ];

    protected $fillable = [
        'ticket_number',
        // ST-1 canonical intake dedupe key (ServiceTicketIntakeService).
        'idempotency_key',
        'service_type',
        'service_location',
        'service_store_id',
        'priority',
        'board_position',
        'repair_status',
        'financial_responsibility',
        'financial_status',
        'description',
        'customer_complaint',
        'technician_diagnosis',
        'root_cause',
        'repair_summary',
        'equipment_id',
        'order_equipment_id',
        'equipment_override',
        'equipment_override_reason',
        'equipment_override_by',
        'equipment_override_at',
        'order_id',
        'customer_id',
        'rental_date',
        'blocked_reason',
        'expected_action_date',
        'diagnostic_status',
        'diagnostic_started_at',
        'diagnostic_completed_at',
        'diagnostic_fee_required',
        'diagnostic_fee_amount',
        'diagnostic_fee_paid',
        'diagnostic_fee_creditable',
        'diagnostic_fee_credited',
        'warranty_possible',
        'customer_damage_possible',
        'recommended_repair',
        'estimated_labor_hours',
        'estimated_parts_total',
        'estimated_repair_total',
        'responsibility_decision',
        'responsibility_decision_id',
        'responsibility_decided_at',
        'responsibility_decided_by',
        'approval_status',
        'approval_type',
        'estimate_sent_at',
        'estimate_approved_at',
        'estimate_declined_at',
        'approved_by_customer_name',
        'approved_by_user_id',
        'approval_notes',
        'repair_authorized',
        'repair_authorized_at',
        'repair_authorized_by',
        'repair_authorization_notes',
        'authorization_override',
        'authorization_override_reason',
        'authorization_override_by',
        'authorization_override_at',
        'parts_deposit_required',
        'parts_deposit_amount',
        'parts_deposit_paid',
        'parts_deposit_paid_at',
        'parts_deposit_payment_reference',
        'parts_deposit_creditable',
        'parts_deposit_applied_to_final_invoice',
        'deposit_override',
        'deposit_override_reason',
        'deposit_override_by',
        'deposit_override_at',
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
        'board_position'           => 'integer',
        'repair_status'            => RepairStatus::class,
        'financial_responsibility' => FinancialResponsibility::class,
        'financial_status'         => FinancialStatus::class,
        'rental_date'              => 'date',
        'expected_action_date'     => 'date',
        'opened_at'                => 'datetime',
        'completed_at'             => 'datetime',
        'closed_at'                => 'datetime',
        'diagnostic_status'        => DiagnosticStatus::class,
        'diagnostic_started_at'    => 'datetime',
        'diagnostic_completed_at'  => 'datetime',
        'diagnostic_fee_required'  => 'boolean',
        'diagnostic_fee_amount'    => 'decimal:2',
        'diagnostic_fee_paid'      => 'boolean',
        'diagnostic_fee_creditable' => 'boolean',
        'diagnostic_fee_credited'  => 'boolean',
        'warranty_possible'        => 'boolean',
        'customer_damage_possible' => 'boolean',
        'estimated_labor_hours'    => 'decimal:2',
        'estimated_parts_total'    => 'decimal:2',
        'estimated_repair_total'   => 'decimal:2',
        // Legacy string key snapshot — retained during the staged retirement of
        // the enum. The canonical decision is the responsibility_decision_id FK
        // → ServiceResponsibilityDecision; read it via responsibilityDecision().
        'responsibility_decided_at' => 'datetime',
        'approval_status'          => ApprovalStatus::class,
        'approval_type'            => ApprovalType::class,
        'estimate_sent_at'         => 'datetime',
        'estimate_approved_at'     => 'datetime',
        'estimate_declined_at'     => 'datetime',
        'repair_authorized'        => 'boolean',
        'repair_authorized_at'     => 'datetime',
        'authorization_override'   => 'boolean',
        'authorization_override_at' => 'datetime',
        'equipment_override'       => 'boolean',
        'equipment_override_at'    => 'datetime',
        'parts_deposit_required'   => 'boolean',
        'parts_deposit_amount'     => 'decimal:2',
        'parts_deposit_paid'       => 'boolean',
        'parts_deposit_paid_at'    => 'datetime',
        'parts_deposit_creditable' => 'boolean',
        'parts_deposit_applied_to_final_invoice' => 'boolean',
        'deposit_override'         => 'boolean',
        'deposit_override_at'      => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(function (self $ticket) {
            if (!$ticket->ticket_number) {
                $ticket->ticket_number = 'SVC-' . str_pad((string) $ticket->id, 5, '0', STR_PAD_LEFT);
                $ticket->saveQuietly();
            }

            ServiceTicketEvent::record($ticket->id, ServiceTicketEventType::TicketCreated);
        });

        // Timeline: status changes are caught here so every code path
        // (transitions, edit form, direct saves) is logged automatically.
        static::updated(function (self $ticket) {
            if ($ticket->wasChanged('repair_status')) {
                $old = $ticket->getOriginal('repair_status');
                $new = $ticket->repair_status;

                $type = match (true) {
                    $new === RepairStatus::Completed  => ServiceTicketEventType::Completed,
                    $new === RepairStatus::Closed     => ServiceTicketEventType::Closed,
                    $new === RepairStatus::InProgress => ServiceTicketEventType::RepairStarted,
                    $new === RepairStatus::Open
                        && !in_array($old?->value, RepairStatus::notFinished(), true) => ServiceTicketEventType::Reopened,
                    default => ServiceTicketEventType::StatusChanged,
                };

                ServiceTicketEvent::record($ticket->id, $type, $old?->label(), $new->label());
            }

            if ($ticket->wasChanged('financial_status')) {
                ServiceTicketEvent::record(
                    $ticket->id,
                    ServiceTicketEventType::FinancialStatusChanged,
                    $ticket->getOriginal('financial_status')?->label(),
                    $ticket->financial_status->label(),
                );
            }

            if ($ticket->wasChanged('diagnostic_status')) {
                if ($ticket->diagnostic_status === DiagnosticStatus::InProgress) {
                    ServiceTicketEvent::record($ticket->id, ServiceTicketEventType::DiagnosticStarted);
                } elseif ($ticket->diagnostic_status === DiagnosticStatus::Completed) {
                    ServiceTicketEvent::record($ticket->id, ServiceTicketEventType::DiagnosticCompleted);
                }
            }

            if ($ticket->wasChanged('responsibility_decision_id')) {
                $oldDecisionId = $ticket->getOriginal('responsibility_decision_id');
                ServiceTicketEvent::record(
                    $ticket->id,
                    ServiceTicketEventType::ResponsibilityDecisionChanged,
                    $oldDecisionId ? ServiceResponsibilityDecision::find($oldDecisionId)?->name : 'Pending',
                    $ticket->responsibilityDecision?->name ?? 'Pending',
                );
            }

            if ($ticket->wasChanged('approval_status')) {
                $approvalEvent = match ($ticket->approval_status) {
                    ApprovalStatus::EstimateSent => ServiceTicketEventType::EstimateSent,
                    ApprovalStatus::Approved     => ServiceTicketEventType::EstimateApproved,
                    ApprovalStatus::Declined     => ServiceTicketEventType::EstimateDeclined,
                    default                      => null,
                };
                if ($approvalEvent) {
                    ServiceTicketEvent::record(
                        $ticket->id,
                        $approvalEvent,
                        $ticket->getOriginal('approval_status')?->label(),
                        $ticket->approval_status->label(),
                        $ticket->approval_type?->label(),
                    );
                }
            }

            if ($ticket->wasChanged('authorization_override') && $ticket->authorization_override) {
                ServiceTicketEvent::record(
                    $ticket->id,
                    ServiceTicketEventType::AuthorizationOverride,
                    notes: $ticket->authorization_override_reason,
                );
            }

            if ($ticket->wasChanged('repair_authorized')) {
                // An override logs its own event — don't double-log the grant.
                $grantedViaOverride = $ticket->repair_authorized && $ticket->wasChanged('authorization_override');
                if (!$grantedViaOverride) {
                    ServiceTicketEvent::record(
                        $ticket->id,
                        $ticket->repair_authorized
                            ? ServiceTicketEventType::RepairAuthorized
                            : ServiceTicketEventType::RepairAuthorizationRevoked,
                        notes: $ticket->repair_authorization_notes,
                    );
                }
            }

            if ($ticket->wasChanged('parts_deposit_required') && $ticket->parts_deposit_required) {
                ServiceTicketEvent::record(
                    $ticket->id,
                    ServiceTicketEventType::PartsDepositRequired,
                    notes: $ticket->parts_deposit_amount !== null
                        ? 'Deposit of $' . number_format((float) $ticket->parts_deposit_amount, 2) . ' required before ordering parts'
                        : 'Parts deposit required before ordering parts',
                );
            }

            if ($ticket->wasChanged('parts_deposit_paid') && $ticket->parts_deposit_paid) {
                ServiceTicketEvent::record(
                    $ticket->id,
                    ServiceTicketEventType::PartsDepositPaid,
                    notes: trim(sprintf(
                        '%s%s',
                        $ticket->parts_deposit_amount !== null ? '$' . number_format((float) $ticket->parts_deposit_amount, 2) . ' ' : '',
                        $ticket->parts_deposit_payment_reference ? '(ref: ' . $ticket->parts_deposit_payment_reference . ')' : '',
                    )) ?: null,
                );
            }

            if ($ticket->wasChanged('deposit_override') && $ticket->deposit_override) {
                ServiceTicketEvent::record(
                    $ticket->id,
                    ServiceTicketEventType::PartsDepositOverridden,
                    notes: $ticket->deposit_override_reason,
                );
            }

            $feeFields = [
                'diagnostic_fee_required', 'diagnostic_fee_amount', 'diagnostic_fee_paid',
                'diagnostic_fee_creditable', 'diagnostic_fee_credited',
            ];
            if (count(array_intersect(array_keys($ticket->getChanges()), $feeFields)) > 0) {
                ServiceTicketEvent::record(
                    $ticket->id,
                    ServiceTicketEventType::DiagnosticFeeUpdated,
                    notes: sprintf(
                        'Fee %s%s — %s%s%s',
                        $ticket->diagnostic_fee_required ? 'required' : 'not required',
                        $ticket->diagnostic_fee_amount !== null ? ' ($' . number_format((float) $ticket->diagnostic_fee_amount, 2) . ')' : '',
                        $ticket->diagnostic_fee_paid ? 'paid' : 'unpaid',
                        $ticket->diagnostic_fee_creditable ? ', creditable' : '',
                        $ticket->diagnostic_fee_credited ? ', credited' : '',
                    ),
                );
            }
        });
    }

    // ── Relationships ──────────────────────────────────────────────

    /** HRM employees assigned to this ticket — users table is the source of truth. */
    public function personnel()
    {
        return $this->belongsToMany(User::class, 'service_ticket_personnel', 'service_ticket_id', 'employee_id')
            ->withPivot('is_team_leader')
            ->withTimestamps();
    }

    /** The assigned employee marked as crew lead for this ticket, if any. */
    public function teamLeader(): ?User
    {
        return $this->personnel->first(fn (User $user) => (bool) $user->pivot->is_team_leader);
    }

    public function resourceDispatches()
    {
        return $this->hasMany(ServiceTicketResourceDispatch::class);
    }

    public function laborEntries()
    {
        return $this->hasMany(ServiceTicketLaborEntry::class);
    }

    public function chargeLines()
    {
        return $this->hasMany(ServiceTicketChargeLine::class);
    }

    public function partsUsed()
    {
        return $this->hasMany(ServiceTicketPart::class);
    }

    public function media()
    {
        return $this->hasMany(ServiceTicketMedia::class);
    }

    public function diagnosticSteps()
    {
        return $this->hasMany(ServiceTicketDiagnosticStep::class)->orderBy('created_at')->orderBy('id');
    }

    public function notes()
    {
        return $this->hasMany(ServiceTicketNote::class)->latest()->latest('id');
    }

    public function events()
    {
        return $this->hasMany(ServiceTicketEvent::class);
    }

    public function settlements()
    {
        return $this->hasMany(ServiceTicketSettlement::class);
    }

    /** The one active settlement (duplicate protection checks this). */
    public function activeSettlement(): ?ServiceTicketSettlement
    {
        return $this->settlements()->where('status', ServiceTicketSettlement::STATUS_CREATED)->first();
    }

    /**
     * Sync assigned personnel and log timeline events for the delta.
     * Controllers must use this instead of personnel()->sync() directly —
     * pivot syncs fire no model events, so logging happens here.
     */
    public function syncPersonnel(array $userIds, ?int $teamLeaderId = null): void
    {
        // Callers that don't manage the crew lead (e.g. the edit form) keep
        // the current leader as long as they remain on the ticket.
        if ($teamLeaderId === null) {
            $teamLeaderId = $this->personnel()->wherePivot('is_team_leader', true)->first()?->id;
        }

        $payload = collect($userIds)
            ->mapWithKeys(fn ($id) => [(int) $id => ['is_team_leader' => (int) $id === (int) $teamLeaderId]])
            ->all();

        $changes = $this->personnel()->sync($payload);

        $touched = array_merge($changes['attached'], $changes['detached']);
        if (empty($touched)) {
            return;
        }

        $names = User::whereIn('id', $touched)->get(['id', 'first_name', 'last_name']);

        foreach ($changes['attached'] as $id) {
            $leadSuffix = (int) $id === (int) $teamLeaderId ? ' (Team Leader)' : '';
            ServiceTicketEvent::record($this->id, ServiceTicketEventType::PersonnelAdded,
                notes: 'Assigned: ' . ($names->firstWhere('id', $id)?->full_name ?? "user #{$id}") . $leadSuffix);
        }
        foreach ($changes['detached'] as $id) {
            ServiceTicketEvent::record($this->id, ServiceTicketEventType::PersonnelRemoved,
                notes: 'Unassigned: ' . ($names->firstWhere('id', $id)?->full_name ?? "user #{$id}"));
        }
    }

    /** Structured complaints selected at intake — one row per complaint. */
    public function complaints()
    {
        return $this->hasMany(ServiceTicketComplaint::class, 'service_ticket_id');
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    /**
     * Equipment originally assigned to the rental order — populated only when
     * an Equipment ID Override was applied at intake. equipment() stays the
     * unit actually being serviced; the order itself is never modified.
     */
    public function orderEquipment()
    {
        return $this->belongsTo(Equipment::class, 'order_equipment_id');
    }

    public function equipmentOverrideBy()
    {
        return $this->belongsTo(User::class, 'equipment_override_by');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /** Store where the equipment will be repaired (rental-order intake path). */
    public function serviceStore()
    {
        return $this->belongsTo(\App\Models\Stores\Store::class, 'service_store_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function responsibilityDecidedBy()
    {
        return $this->belongsTo(User::class, 'responsibility_decided_by');
    }

    /** The chosen Responsibility Decision master record (null = Pending/undecided). */
    public function responsibilityDecision()
    {
        return $this->belongsTo(ServiceResponsibilityDecision::class, 'responsibility_decision_id');
    }

    /**
     * Companion Field Service mission (present only for FieldServiceCall
     * tickets). The board resolves a field card's specialized workbench
     * through this relation.
     */
    public function fieldServiceTicket()
    {
        return $this->hasOne(\App\Models\FieldService\FieldServiceTicket::class, 'service_ticket_id');
    }

    /** Has a responsibility decision been made yet? (Pending = no decision.) */
    public function isResponsibilityDecided(): bool
    {
        return $this->responsibility_decision_id !== null;
    }

    public function approvedByUser()
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function repairAuthorizedBy()
    {
        return $this->belongsTo(User::class, 'repair_authorized_by');
    }

    public function depositOverrideBy()
    {
        return $this->belongsTo(User::class, 'deposit_override_by');
    }

    public function authorizationOverrideBy()
    {
        return $this->belongsTo(User::class, 'authorization_override_by');
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

        if ($status === RepairStatus::Completed) {
            $this->applyCompletionFinancialStatus();
        }

        $this->save();
    }

    // ── Diagnostic-first lifecycle (Phase 2D) ─────────────────────

    /** Begin diagnosis; nudges an Open ticket into Diagnosing. */
    public function startDiagnostic(): void
    {
        if ($this->diagnostic_status === DiagnosticStatus::InProgress) {
            return;
        }

        $this->diagnostic_status     = DiagnosticStatus::InProgress;
        $this->diagnostic_started_at ??= now();

        if ($this->repair_status === RepairStatus::Open) {
            $this->repair_status = RepairStatus::Diagnosing;
        }

        $this->save();
    }

    public function completeDiagnostic(): void
    {
        if ($this->diagnostic_status === DiagnosticStatus::Completed) {
            return;
        }

        $this->diagnostic_status       = DiagnosticStatus::Completed;
        $this->diagnostic_started_at ??= now();
        $this->diagnostic_completed_at = now();

        $this->save();
    }

    /**
     * Record the responsibility decision made after diagnosis and map it onto
     * financial_responsibility via the master record's financial_path. A
     * decision with no financial_path (No Problem Found / Not Repairable) has
     * no payer path and leaves financial responsibility untouched.
     *
     * The canonical decision is the FK; the legacy string column is kept in
     * sync as a historical key snapshot during the staged enum retirement.
     */
    public function decideResponsibility(ServiceResponsibilityDecision $decision): void
    {
        $this->responsibility_decision_id = $decision->id;
        $this->responsibility_decision    = $decision->key; // legacy key snapshot
        $this->responsibility_decided_at  = now();
        $this->responsibility_decided_by  = auth()->id();

        if ($mapped = $decision->financialResponsibility()) {
            $this->financial_responsibility = $mapped;
        }

        // Every payer path needs its approval before repair authorization;
        // decisions with no approval_type (No Problem Found / Not Repairable)
        // need none.
        if ($requiredApproval = $decision->approvalTypeEnum()) {
            $this->approval_type   = $requiredApproval;
            $this->approval_status = $this->approval_status->satisfied() && $this->approval_status !== ApprovalStatus::NotRequired
                ? $this->approval_status
                : ApprovalStatus::Pending;
        } else {
            $this->approval_type   = null;
            $this->approval_status = ApprovalStatus::NotRequired;
        }

        $this->save();
    }

    // ── Approval & repair funding gate (Phase 2E) ─────────────────

    public function sendEstimate(): void
    {
        $this->approval_status  = ApprovalStatus::EstimateSent;
        $this->approval_type  ??= $this->responsibilityDecision?->approvalTypeEnum();
        $this->estimate_sent_at = now();
        $this->save();
    }

    public function approveEstimate(?string $customerName = null, ?string $notes = null): void
    {
        $this->approval_status            = ApprovalStatus::Approved;
        $this->approval_type            ??= $this->responsibilityDecision?->approvalTypeEnum();
        $this->estimate_approved_at       = now();
        $this->approved_by_customer_name  = $customerName ?? $this->approved_by_customer_name;
        $this->approved_by_user_id        = auth()->id();
        $this->approval_notes             = $notes ?? $this->approval_notes;
        $this->save();
    }

    public function declineEstimate(?string $notes = null): void
    {
        $this->approval_status      = ApprovalStatus::Declined;
        $this->estimate_declined_at = now();
        $this->approval_notes       = $notes ?? $this->approval_notes;
        $this->save();
    }

    /** Revoking approval always pulls repair authorization (and any override) with it. */
    public function revokeApproval(?string $notes = null): void
    {
        $this->approval_status = ApprovalStatus::Revoked;
        $this->approval_notes  = $notes ?? $this->approval_notes;
        if ($this->repair_authorized) {
            $this->repair_authorized           = false;
            $this->authorization_override      = false;
            $this->repair_authorization_notes  = 'Approval revoked';
        }
        $this->save();
    }

    /** Is the parts-deposit gate satisfied? */
    public function depositSatisfied(): bool
    {
        return !$this->parts_deposit_required || $this->parts_deposit_paid || $this->deposit_override;
    }

    /**
     * The full repair authorization rule: diagnosis complete, responsibility
     * decided, required approval approved, deposit paid or overridden.
     */
    public function canAuthorizeRepair(): bool
    {
        return $this->authorizationBlockers()->isEmpty();
    }

    /** Human-readable list of everything still blocking repair authorization. */
    public function authorizationBlockers(): \Illuminate\Support\Collection
    {
        $blockers = collect();

        if (!$this->diagnostic_status->allowsResponsibilityDecision()) {
            $blockers->push('Diagnostic not completed');
        }
        if (!$this->isResponsibilityDecided()) {
            $blockers->push('Responsibility decision pending');
        }
        if (!$this->approval_status->satisfied()) {
            $blockers->push('Waiting on ' . ($this->approval_type?->waitingOnLabel() ?? 'Approval'));
        }
        if (!$this->depositSatisfied()) {
            $blockers->push('Waiting on Parts Deposit');
        }

        return $blockers;
    }

    public function authorizeRepair(?string $notes = null): bool
    {
        if (!$this->canAuthorizeRepair()) {
            return false;
        }

        $this->repair_authorized          = true;
        $this->repair_authorized_at       = now();
        $this->repair_authorized_by       = auth()->id();
        $this->repair_authorization_notes = $notes ?? $this->repair_authorization_notes;
        $this->save();

        return true;
    }

    public function revokeRepairAuthorization(?string $notes = null): void
    {
        if (!$this->repair_authorized) {
            return;
        }

        $this->repair_authorized          = false;
        $this->authorization_override     = false;
        $this->repair_authorization_notes = $notes ?? $this->repair_authorization_notes;
        $this->save();
    }

    public function overrideDeposit(string $reason): void
    {
        $this->deposit_override        = true;
        $this->deposit_override_reason = $reason;
        $this->deposit_override_by     = auth()->id();
        $this->deposit_override_at     = now();
        $this->save();
    }

    // ── Authorization enforcement (Phase 2E.5) ────────────────────

    /**
     * The enforced gate: repair execution is allowed when the gates are
     * satisfied or the repair has been explicitly authorized/overridden.
     */
    public function repairExecutionAllowed(): bool
    {
        return $this->repair_authorized || $this->canAuthorizeRepair();
    }

    /** Can this ticket move to the given repair status right now? */
    public function canTransitionTo(RepairStatus $status): bool
    {
        return !$status->requiresAuthorization() || $this->repairExecutionAllowed();
    }

    /**
     * User-facing rejection message for a blocked transition, built from the
     * live blocker list.
     */
    public function blockedTransitionMessage(): string
    {
        return 'Repair cannot begin. Outstanding requirements: '
            . $this->authorizationBlockers()->map(fn ($b) => '• ' . $b)->implode(' ');
    }

    /** Record that someone attempted repair work on an unauthorized ticket. */
    public function recordBlockedTransition(RepairStatus $attempted): void
    {
        ServiceTicketEvent::record(
            $this->id,
            ServiceTicketEventType::RepairStartBlocked,
            $this->repair_status->label(),
            $attempted->label(),
            'Blocked — ' . $this->authorizationBlockers()->implode('; '),
        );
    }

    /**
     * Manager override: force the authorization gate open with a recorded
     * reason. Immediately satisfies the authorization requirement.
     */
    public function overrideAuthorization(string $reason): void
    {
        $this->authorization_override        = true;
        $this->authorization_override_reason = $reason;
        $this->authorization_override_by     = auth()->id();
        $this->authorization_override_at     = now();

        $this->repair_authorized    = true;
        $this->repair_authorized_at = now();
        $this->repair_authorized_by = auth()->id();

        $this->save();
    }

    // ── Customer settlement (Phase 3A) ────────────────────────────

    /**
     * Gate for Create Customer Charge: repair completed, customer pay,
     * authorized to proceed, related order present, and no active settlement.
     */
    public function canCreateCustomerCharge(): bool
    {
        return $this->chargeCreationBlockers()->isEmpty();
    }

    /** Everything still preventing customer charge creation. */
    public function chargeCreationBlockers(): \Illuminate\Support\Collection
    {
        $blockers = collect();

        if (!in_array($this->repair_status, [RepairStatus::Completed, RepairStatus::Closed], true)) {
            $blockers->push('Repair not completed');
        }
        // Only a Customer Pay disposition reaches customer billing. This reads
        // the mapped financial_responsibility (set from the master's
        // financial_path) rather than the decision itself — one source of truth.
        if ($this->financial_responsibility !== FinancialResponsibility::CustomerPay) {
            $blockers->push('Responsibility is not Customer Pay');
        }
        if (!$this->repairExecutionAllowed()) {
            $blockers->push('Repair not authorized');
        }
        if ($this->order_id === null || $this->customer_id === null) {
            $blockers->push('No related order/customer to receive the charge');
        }
        if ($this->activeSettlement()) {
            $blockers->push('A customer settlement already exists for this ticket');
        }

        return $blockers;
    }

    /** Timeline note that the settlement math changed before charge creation. */
    public function recordSettlementUpdated(string $what): void
    {
        if ($this->activeSettlement()) {
            return; // charge already created — line edits no longer feed a preview
        }

        ServiceTicketEvent::record(
            $this->id,
            ServiceTicketEventType::SettlementUpdated,
            notes: 'Settlement recalculated — ' . $what,
        );
    }

    // ── Equipment protection hooks (consumed by the Equipment module later;
    //    Service does not modify equipment state itself) ───────────

    /** Does this ticket currently hold its equipment out of service? */
    public function holdsEquipment(): bool
    {
        return $this->equipment_id !== null
            && in_array($this->repair_status->value, RepairStatus::notFinished(), true)
            && $this->repair_status !== RepairStatus::ReadyForPickup;
    }

    /**
     * Hook for the Equipment module: is any service ticket holding this
     * equipment out of Available / Ready for Rent / Ready for Pickup?
     */
    public static function hasActiveHoldForEquipment(int $equipmentId): bool
    {
        return self::where('equipment_id', $equipmentId)
            ->whereIn('repair_status', array_diff(RepairStatus::notFinished(), [RepairStatus::ReadyForPickup->value]))
            ->exists();
    }

    /** One-line workbench state: [label, badge classes]. */
    /**
     * Workbench stage engine: derives the nine lifecycle stages purely from
     * existing ticket state (no new workflow rules). Each stage is
     * ['key', 'label', 'state' (complete|current|pending), 'meta' lines].
     * The current stage = the first stage that isn't complete.
     */
    public function workflowStages(): array
    {
        $fmt = fn ($ts, $format = 'M j, g:i A') => $ts?->format($format);

        $repairComplete = in_array($this->repair_status, [RepairStatus::ReadyForPickup, RepairStatus::Completed, RepairStatus::Closed], true);
        $repairMeta = match (true) {
            $this->repair_status === RepairStatus::InProgress => ['In Progress'],
            $this->is_blocked => ['Waiting on ' . $this->repair_status->waitingOnLabel()],
            $repairComplete => array_filter(['Complete', $fmt($this->completed_at, 'M j, g:i A')]),
            default => [],
        };

        // Settlement applies while the decision is still open (could become
        // Customer Pay) or when it resolved to Customer Pay. Keyed off the
        // decided-ness helper + mapped financial_responsibility — no enum.
        $decided = $this->isResponsibilityDecided();
        $settlementApplicable = !$decided
            || $this->financial_responsibility === FinancialResponsibility::CustomerPay;
        $settlement = $this->activeSettlement();
        $responsibilityLabel = $this->responsibilityDecision?->name;

        $stages = [
            ['key' => 'intake', 'label' => 'Intake',
                'complete' => true,
                'meta' => array_filter(['Complete', $fmt($this->created_at)])],
            ['key' => 'diagnostic', 'label' => 'Diagnostic',
                'complete' => $this->diagnostic_status->allowsResponsibilityDecision(),
                'meta' => array_filter([
                    $this->diagnostic_status->label(),
                    $fmt($this->diagnostic_completed_at ?? $this->diagnostic_started_at),
                ])],
            ['key' => 'responsibility', 'label' => 'Responsibility',
                'complete' => $decided,
                'meta' => $decided
                    ? array_filter([$responsibilityLabel, $fmt($this->responsibility_decided_at)])
                    : ['Pending']],
            // Approval and deposit only arm after the responsibility decision —
            // until then they read Pending rather than a misleading green check
            ['key' => 'approval', 'label' => 'Approval',
                'complete' => $decided && $this->approval_status->satisfied(),
                'meta' => $decided
                    ? array_filter([
                        $this->approval_status->label(),
                        $fmt($this->estimate_approved_at ?? $this->estimate_declined_at ?? $this->estimate_sent_at),
                    ])
                    : ['Pending']],
            ['key' => 'deposit', 'label' => 'Parts Deposit',
                'complete' => $decided && $this->depositSatisfied(),
                'meta' => match (true) {
                    !$this->parts_deposit_required => [$decided ? 'Not Required' : 'Pending'],
                    (bool) $this->parts_deposit_paid => array_filter(['Paid', $fmt($this->parts_deposit_paid_at)]),
                    (bool) $this->deposit_override => ['Overridden'],
                    default => ['Awaiting Payment'],
                }],
            ['key' => 'authorized', 'label' => 'Authorized',
                'complete' => (bool) $this->repair_authorized,
                'meta' => $this->repair_authorized
                    ? array_filter([$this->authorization_override ? 'Override' : 'Authorized', $fmt($this->repair_authorized_at)])
                    : ['Pending']],
            ['key' => 'repair', 'label' => 'Repair',
                'complete' => $repairComplete,
                'meta' => $repairMeta ?: ['Pending']],
            ['key' => 'settlement', 'label' => 'Settlement',
                'complete' => $settlement !== null || !$settlementApplicable,
                'meta' => match (true) {
                    $settlement !== null => array_filter(['Charge Created', $fmt($settlement->created_at)]),
                    !$settlementApplicable => ['Not Applicable'],
                    default => ['Pending'],
                }],
            ['key' => 'close', 'label' => 'Close Ticket',
                'complete' => $this->repair_status === RepairStatus::Closed,
                'meta' => match (true) {
                    $this->repair_status === RepairStatus::Closed => array_filter(['Closed', $fmt($this->closed_at)]),
                    $this->repair_status === RepairStatus::ReadyForPickup => ['Ready for Pickup'],
                    default => ['Pending'],
                }],
        ];

        $currentAssigned = false;
        foreach ($stages as $i => $stage) {
            if ($stage['complete']) {
                $stages[$i]['state'] = 'complete';
            } elseif (!$currentAssigned) {
                $stages[$i]['state'] = 'current';
                $currentAssigned = true;
            } else {
                $stages[$i]['state'] = 'pending';
            }
            unset($stages[$i]['complete']);
        }

        // Fully closed ticket: everything is complete, nothing is current
        return $stages;
    }

    /** Key of the stage the workbench should focus on right now. */
    public function currentStageKey(): string
    {
        foreach ($this->workflowStages() as $stage) {
            if ($stage['state'] === 'current') {
                return $stage['key'];
            }
        }

        return 'close';
    }

    public function workbenchState(): array
    {
        if ($this->repair_authorized) {
            return ['Repair Authorized', 'bg-green-100 text-green-700 border border-green-200'];
        }
        if ($this->diagnostic_status === DiagnosticStatus::NotStarted) {
            return ['Ready for Diagnosis', 'bg-sky-100 text-sky-700 border border-sky-200'];
        }
        if ($this->diagnostic_status === DiagnosticStatus::InProgress) {
            return ['Diagnostic In Progress', 'bg-sky-100 text-sky-700 border border-sky-200'];
        }
        if (!$this->isResponsibilityDecided()) {
            return ['Awaiting Responsibility Decision', 'bg-gray-100 text-gray-600 border border-gray-200'];
        }
        if (!$this->approval_status->satisfied()) {
            return ['Waiting on ' . ($this->approval_type?->waitingOnLabel() ?? 'Approval'), 'bg-amber-100 text-amber-700 border border-amber-200'];
        }
        if (!$this->depositSatisfied()) {
            return ['Waiting on Parts Deposit', 'bg-yellow-100 text-yellow-700 border border-yellow-200'];
        }

        return ['Repair Not Authorized', 'bg-gray-100 text-gray-600 border border-gray-200'];
    }

    /**
     * Phase 2B automation: a completed customer-pay ticket with something to
     * bill becomes ready_to_bill. Billing-prep only — never creates charges,
     * and never touches tickets already in the charge/payment pipeline.
     * Warranty, internal, and goodwill tickets keep their financial status.
     */
    protected function applyCompletionFinancialStatus(): void
    {
        if ($this->financial_responsibility !== FinancialResponsibility::CustomerPay) {
            return;
        }

        $locked = [
            FinancialStatus::ChargeCreated,
            FinancialStatus::PartiallyPaid,
            FinancialStatus::Paid,
        ];

        if (!in_array($this->financial_status, $locked, true) && $this->billable_total > 0) {
            $this->financial_status = FinancialStatus::ReadyToBill;
        }
    }

    // ── Billing preparation totals (Phase 2B) ─────────────────────
    //
    // All ticket math lives here — Blade files and controllers must read
    // these, never re-derive amounts. Amounts are preparation-only; nothing
    // here creates customer charges or payments.

    /** Default billable flag for new labor/charge lines on this ticket. */
    public function defaultBillable(): bool
    {
        return match ($this->financial_responsibility) {
            // Warranty labor is "billable" toward the OEM claim, not the customer.
            FinancialResponsibility::CustomerPay, FinancialResponsibility::OemWarranty => true,
            default => false,
        };
    }

    /** Sum of all labor entry totals (billable or not). */
    public function getLaborTotalAttribute(): float
    {
        return round((float) $this->laborEntries->sum('labor_total'), 2);
    }

    /** Sum of all charge line totals (billable or not). */
    public function getChargeLineTotalAttribute(): float
    {
        return round((float) $this->chargeLines->sum('line_total'), 2);
    }

    /** Billable labor + billable charge lines. */
    public function getBillableTotalAttribute(): float
    {
        return round(
            (float) $this->laborEntries->where('billable', true)->sum('labor_total')
            + (float) $this->chargeLines->where('billable', true)->sum('line_total'),
            2
        );
    }

    /** Non-billable labor + non-billable charge lines. */
    public function getNonBillableTotalAttribute(): float
    {
        return round($this->labor_total + $this->charge_line_total - $this->billable_total, 2);
    }

    /** Billable amounts on an OEM-warranty ticket = the reimbursable claim. */
    public function getWarrantyClaimTotalAttribute(): float
    {
        return $this->financial_responsibility === FinancialResponsibility::OemWarranty
            ? $this->billable_total
            : 0.0;
    }

    /**
     * Cost the company absorbs: everything on internal/goodwill tickets,
     * otherwise just the non-billable portion.
     */
    public function getInternalCostTotalAttribute(): float
    {
        return match ($this->financial_responsibility) {
            // Damage Waiver mirrors Internal Expense's cost treatment (company
            // absorbs the repair) but stays a distinct financial identity — its
            // costs are captured here yet reportable separately by financial_path.
            FinancialResponsibility::InternalExpense,
            FinancialResponsibility::Goodwill,
            FinancialResponsibility::DamageWaiver => round($this->labor_total + $this->charge_line_total, 2),
            default => $this->non_billable_total,
        };
    }

    /** quantity × unit_cost across all parts — company cost. */
    public function getPartsCostTotalAttribute(): float
    {
        return round((float) $this->partsUsed->sum(fn ($p) => $p->cost_total ?? 0), 2);
    }

    /** quantity × customer_price across billable parts — customer-facing value. */
    public function getPartsCustomerTotalAttribute(): float
    {
        return round((float) $this->partsUsed->where('billable', true)->sum(fn ($p) => $p->customer_total ?? 0), 2);
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
