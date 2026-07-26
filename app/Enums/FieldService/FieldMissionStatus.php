<?php

namespace App\Enums\FieldService;

use App\Enums\Service\RepairStatus;

/**
 * Mission status for a Field Service Ticket. Field Service manages an
 * in-field mission (dispatch → arrival → assessment), not an in-shop
 * repair — this is deliberately NOT the Shop Service RepairStatus.
 */
enum FieldMissionStatus: string
{
    case Draft               = 'draft';
    case ReadyForDispatch    = 'ready_for_dispatch';
    case Assigned            = 'assigned';
    case EnRoute             = 'en_route';
    case OnSite              = 'on_site';
    case FieldAssessment     = 'field_assessment';
    case AssessmentComplete  = 'assessment_complete';
    case OperationalDecision = 'operational_decision';
    case Completed           = 'completed';
    case Cancelled           = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft               => 'Draft',
            self::ReadyForDispatch    => 'Ready for Dispatch',
            self::Assigned            => 'Assigned',
            self::EnRoute             => 'En Route',
            self::OnSite              => 'On Site',
            self::FieldAssessment     => 'Field Assessment',
            self::AssessmentComplete  => 'Assessment Complete',
            self::OperationalDecision => 'Operational Decision',
            self::Completed           => 'Completed',
            self::Cancelled           => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft               => 'bg-gray-100 text-gray-600 border border-gray-200',
            self::ReadyForDispatch    => 'bg-amber-100 text-amber-700 border border-amber-200',
            self::Assigned            => 'bg-blue-100 text-blue-700 border border-blue-200',
            self::EnRoute             => 'bg-indigo-100 text-indigo-700 border border-indigo-200',
            self::OnSite              => 'bg-violet-100 text-violet-700 border border-violet-200',
            self::FieldAssessment     => 'bg-cyan-100 text-cyan-700 border border-cyan-200',
            self::AssessmentComplete  => 'bg-teal-100 text-teal-700 border border-teal-200',
            self::OperationalDecision => 'bg-purple-100 text-purple-700 border border-purple-200',
            self::Completed           => 'bg-green-100 text-green-700 border border-green-200',
            self::Cancelled           => 'bg-red-100 text-red-700 border border-red-200',
        };
    }

    /** Ordered dispatch-flow statuses (terminal states excluded). */
    public static function missionFlow(): array
    {
        return [
            self::Draft,
            self::ReadyForDispatch,
            self::Assigned,
            self::EnRoute,
            self::OnSite,
            self::FieldAssessment,
            self::AssessmentComplete,
            self::OperationalDecision,
        ];
    }

    /** Statuses this one may transition to. */
    public function allowedNext(): array
    {
        return match ($this) {
            self::Draft               => [self::ReadyForDispatch, self::Cancelled],
            self::ReadyForDispatch    => [self::Assigned, self::Cancelled],
            self::Assigned            => [self::EnRoute, self::Cancelled],
            self::EnRoute             => [self::OnSite, self::Cancelled],
            self::OnSite              => [self::FieldAssessment, self::Cancelled],
            self::FieldAssessment     => [self::AssessmentComplete, self::Cancelled],
            // Assessment Complete flows straight into the mandatory decision
            self::AssessmentComplete  => [self::OperationalDecision, self::Cancelled],
            // Completion is additionally gated on a recorded outcome — see
            // FieldServiceTicket::transitionBlockers().
            self::OperationalDecision => [self::Completed, self::Cancelled],
            self::Completed           => [],
            self::Cancelled           => [],
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::Completed || $this === self::Cancelled;
    }

    /**
     * Coarse mapping onto the shop RepairStatus for the companion service
     * ticket that carries this mission onto the Operations Board. The mission
     * keeps its own detailed status; the companion only needs enough state for
     * the board to show it (open → in progress) and to drop it when the
     * mission ends (completed/cancelled leave the board via notFinished()).
     */
    public function toRepairStatus(): RepairStatus
    {
        return match ($this) {
            self::Draft, self::ReadyForDispatch, self::Assigned => RepairStatus::Open,
            self::EnRoute, self::OnSite, self::FieldAssessment,
            self::AssessmentComplete, self::OperationalDecision  => RepairStatus::InProgress,
            self::Completed                                      => RepairStatus::Completed,
            self::Cancelled                                      => RepairStatus::Cancelled,
        };
    }

    /** Timestamp column stamped when this status is entered (null = none). */
    public function timestampColumn(): ?string
    {
        return match ($this) {
            self::ReadyForDispatch    => 'ready_at',
            self::Assigned            => 'assigned_at',
            self::EnRoute             => 'en_route_at',
            self::OnSite              => 'on_site_at',
            self::FieldAssessment     => 'assessment_started_at',
            self::AssessmentComplete  => 'assessment_completed_at',
            self::OperationalDecision => 'operational_decision_at',
            self::Completed           => 'completed_at',
            self::Cancelled           => 'cancelled_at',
            default                   => null,
        };
    }
}
