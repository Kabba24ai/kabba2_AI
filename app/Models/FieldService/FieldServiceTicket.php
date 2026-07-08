<?php

namespace App\Models\FieldService;

use App\Enums\FieldService\FieldMachineStatus;
use App\Enums\FieldService\FieldMissionStatus;
use App\Enums\FieldService\FieldOperationalExpectation;
use App\Enums\FieldService\FieldOperationalOutcome;
use App\Enums\FieldService\FieldRecoveryRisk;
use App\Enums\FieldService\FieldSafetyConcern;
use App\Enums\FieldService\FieldSiteAccess;
use App\Enums\FieldService\FieldTicketEventType;
use App\Enums\FieldService\FieldYesNoUnknown;
use App\Enums\Service\ServicePriority;
use App\Models\Customers\Customer;
use App\Models\Dispatch\DispatchAiTruck;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A Field Service Ticket is a mission: send a technician to a customer
 * site to assess and stabilize an incident. It is intentionally separate
 * from the Shop Service ticket, which manages an in-shop repair.
 */
class FieldServiceTicket extends Model
{
    use SoftDeletes;

    // In-memory defaults matching the schema — the created hook and views
    // read these before a DB round-trip.
    protected $attributes = [
        'mission_status'          => 'draft',
        'priority'                => 'normal',
        'safety_concern'          => 'unknown',
        'machine_status'          => 'unknown',
        'machine_stuck'           => 'unknown',
        'recovery_risk'           => 'unknown',
        'site_access'             => 'unknown',
        'operational_expectation' => 'unknown',
    ];

    protected $fillable = [
        'ticket_number',
        'mission_status',
        'order_id',
        'customer_id',
        'equipment_id',
        'serial_number',
        'job_site_address',
        'contact_name',
        'contact_phone',
        'reported_at',
        'problem_summary',
        'diagnostic_summary',
        'ai_session_reference',
        'photos_received',
        'video_received',
        'media_reviewed',
        'additional_media_required',
        'media_bypassed',
        'priority',
        'safety_concern',
        'machine_status',
        'machine_stuck',
        'recovery_risk',
        'site_access',
        'site_notes',
        'technician_id',
        'truck_id',
        'estimated_departure_at',
        'estimated_arrival_at',
        'suggested_tools',
        'suggested_parts',
        'special_instructions',
        'operational_expectation',
        'assessment_summary',
        'created_by',
    ];

    protected $casts = [
        'mission_status'            => FieldMissionStatus::class,
        'priority'                  => ServicePriority::class,
        'safety_concern'            => FieldSafetyConcern::class,
        'machine_status'            => FieldMachineStatus::class,
        'machine_stuck'             => FieldYesNoUnknown::class,
        'recovery_risk'             => FieldRecoveryRisk::class,
        'site_access'               => FieldSiteAccess::class,
        'operational_expectation'   => FieldOperationalExpectation::class,
        'operational_outcome'       => FieldOperationalOutcome::class,
        'reported_at'               => 'datetime',
        'estimated_departure_at'    => 'datetime',
        'estimated_arrival_at'      => 'datetime',
        'ready_at'                  => 'datetime',
        'assigned_at'               => 'datetime',
        'en_route_at'               => 'datetime',
        'on_site_at'                => 'datetime',
        'assessment_started_at'     => 'datetime',
        'assessment_completed_at'   => 'datetime',
        'operational_decision_at'   => 'datetime',
        'completed_at'              => 'datetime',
        'cancelled_at'              => 'datetime',
        'photos_received'           => 'boolean',
        'video_received'            => 'boolean',
        'media_reviewed'            => 'boolean',
        'additional_media_required' => 'boolean',
        'media_bypassed'            => 'boolean',
    ];

    protected static function booted(): void
    {
        static::created(function (self $ticket) {
            if (!$ticket->ticket_number) {
                $ticket->ticket_number = 'FLD-' . str_pad((string) $ticket->id, 5, '0', STR_PAD_LEFT);
                $ticket->saveQuietly();
            }

            FieldServiceTicketEvent::record(
                $ticket->id,
                FieldTicketEventType::Created,
                new: $ticket->mission_status->label(),
            );
        });
    }

    // ── Relations ────────────────────────────────────────────────────

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function truck()
    {
        return $this->belongsTo(DispatchAiTruck::class, 'truck_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function events()
    {
        return $this->hasMany(FieldServiceTicketEvent::class)->latest()->latest('id');
    }

    public function notes()
    {
        return $this->hasMany(FieldServiceTicketNote::class)->latest()->latest('id');
    }

    // ── Mission status engine ────────────────────────────────────────

    public function canTransitionTo(FieldMissionStatus $to): bool
    {
        return in_array($to, $this->mission_status->allowedNext(), true)
            && $this->transitionBlockers($to) === [];
    }

    /** Human-readable reasons a transition is not allowed yet. */
    public function transitionBlockers(FieldMissionStatus $to): array
    {
        $blockers = [];

        if ($to === FieldMissionStatus::Assigned && !$this->technician_id) {
            $blockers[] = 'Assign a technician before confirming the assignment.';
        }

        // The Operational Decision is mandatory — the mission cannot close
        // until exactly one operational outcome has been recorded.
        if ($to === FieldMissionStatus::Completed && !$this->operational_outcome) {
            $blockers[] = 'Record the Operational Decision before completing the mission.';
        }

        return $blockers;
    }

    /**
     * Record the Operational Decision — the branching point from assessment
     * to execution. Permanent: once recorded it cannot be changed.
     */
    public function recordOperationalDecision(FieldOperationalOutcome $outcome): bool
    {
        if ($this->mission_status !== FieldMissionStatus::OperationalDecision || $this->operational_outcome) {
            return false;
        }

        $this->operational_outcome = $outcome;
        $this->save();

        FieldServiceTicketEvent::record(
            $this->id,
            FieldTicketEventType::OperationalDecision,
            new: $outcome->label(),
        );

        return true;
    }

    /**
     * Move the mission to a new status: validates the transition, stamps
     * the milestone timestamp, and records a timeline event.
     */
    public function transitionTo(FieldMissionStatus $to, ?string $notes = null): bool
    {
        if (!in_array($to, $this->mission_status->allowedNext(), true) || $this->transitionBlockers($to) !== []) {
            return false;
        }

        $from = $this->mission_status;

        $this->mission_status = $to;
        if ($column = $to->timestampColumn()) {
            $this->{$column} = now();
        }
        $this->save();

        FieldServiceTicketEvent::record(
            $this->id,
            FieldTicketEventType::StatusChanged,
            old: $from->label(),
            new: $to->label(),
            notes: $notes,
        );

        return true;
    }

    /** The single next forward step from the current status (null when terminal). */
    public function nextMissionStatus(): ?FieldMissionStatus
    {
        foreach ($this->mission_status->allowedNext() as $status) {
            if ($status !== FieldMissionStatus::Cancelled) {
                return $status;
            }
        }

        return null;
    }

    /** True when the dispatch-assessment answers flag a risk worth surfacing. */
    public function hasSafetyOrSiteAlert(): bool
    {
        return $this->safety_concern === FieldSafetyConcern::Significant
            || $this->machine_stuck === FieldYesNoUnknown::Yes
            || $this->recovery_risk === FieldRecoveryRisk::Likely
            || $this->site_access === FieldSiteAccess::Difficult;
    }
}
