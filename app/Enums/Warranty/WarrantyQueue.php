<?php

namespace App\Enums\Warranty;

/**
 * Operational queues for a Warranty Case. A case doesn't have a "status" —
 * it sits in a queue that says what it is waiting for and who owes the
 * next move. Red (denied) and orange (partial) are OEM-decision outcome
 * badges, not queues.
 */
enum WarrantyQueue: string
{
    case NewIntake                = 'new_intake';
    case AwaitingDiagnosis        = 'awaiting_diagnosis';
    case ReadyToSubmit            = 'ready_to_submit';
    case WaitingOnManufacturer    = 'waiting_on_manufacturer';
    case AwaitingCustomerDecision = 'awaiting_customer_decision';
    case ApprovedForRepair        = 'approved_for_repair';
    case AwaitingReimbursement    = 'awaiting_reimbursement';
    case Closed                   = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::NewIntake                => 'New Intake',
            self::AwaitingDiagnosis        => 'Awaiting Diagnosis',
            self::ReadyToSubmit            => 'Ready to Submit',
            self::WaitingOnManufacturer    => 'Waiting on Manufacturer',
            self::AwaitingCustomerDecision => 'Awaiting Customer Decision',
            self::ApprovedForRepair        => 'Approved for Repair',
            self::AwaitingReimbursement    => 'Awaiting Reimbursement',
            self::Closed                   => 'Closed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NewIntake                => 'bg-gray-100 text-gray-600 border border-gray-200',
            self::AwaitingDiagnosis        => 'bg-amber-100 text-amber-700 border border-amber-200',
            self::ReadyToSubmit            => 'bg-blue-100 text-blue-700 border border-blue-200',
            self::WaitingOnManufacturer    => 'bg-purple-100 text-purple-700 border border-purple-200',
            self::AwaitingCustomerDecision => 'bg-teal-100 text-teal-700 border border-teal-200',
            self::ApprovedForRepair        => 'bg-green-100 text-green-700 border border-green-200',
            self::AwaitingReimbursement    => 'bg-indigo-100 text-indigo-700 border border-indigo-200',
            self::Closed                   => 'bg-slate-100 text-slate-600 border border-slate-200',
        };
    }

    /** Accent color for KPI card top borders. */
    public function accent(): string
    {
        return match ($this) {
            self::NewIntake                => 'border-t-gray-400',
            self::AwaitingDiagnosis        => 'border-t-amber-400',
            self::ReadyToSubmit            => 'border-t-blue-500',
            self::WaitingOnManufacturer    => 'border-t-purple-500',
            self::AwaitingCustomerDecision => 'border-t-teal-500',
            self::ApprovedForRepair        => 'border-t-green-500',
            self::AwaitingReimbursement    => 'border-t-indigo-500',
            self::Closed                   => 'border-t-slate-400',
        };
    }

    /** The happy path, in ribbon order. */
    public static function happyPath(): array
    {
        return [
            self::NewIntake,
            self::AwaitingDiagnosis,
            self::ReadyToSubmit,
            self::WaitingOnManufacturer,
            self::AwaitingCustomerDecision,
            self::ApprovedForRepair,
            self::AwaitingReimbursement,
            self::Closed,
        ];
    }

    /** Queues a case may move to from this one. */
    public function allowedNext(): array
    {
        return match ($this) {
            self::NewIntake                => [self::AwaitingDiagnosis, self::Closed],
            self::AwaitingDiagnosis        => [self::ReadyToSubmit, self::Closed],
            self::ReadyToSubmit            => [self::WaitingOnManufacturer, self::Closed],
            // Full approval skips the customer decision; partial approval
            // and paid-repair offers route through it (Phase 3).
            self::WaitingOnManufacturer    => [self::AwaitingCustomerDecision, self::ApprovedForRepair, self::Closed],
            self::AwaitingCustomerDecision => [self::ApprovedForRepair, self::Closed],
            self::ApprovedForRepair        => [self::AwaitingReimbursement, self::Closed],
            self::AwaitingReimbursement    => [self::Closed],
            self::Closed                   => [],
        };
    }

    /** Timestamp column stamped when this queue is entered (null = creation). */
    public function timestampColumn(): ?string
    {
        return match ($this) {
            self::AwaitingDiagnosis        => 'awaiting_diagnosis_at',
            self::ReadyToSubmit            => 'ready_to_submit_at',
            self::WaitingOnManufacturer    => 'waiting_on_manufacturer_at',
            self::AwaitingCustomerDecision => 'awaiting_customer_decision_at',
            self::ApprovedForRepair        => 'approved_for_repair_at',
            self::AwaitingReimbursement    => 'awaiting_reimbursement_at',
            self::Closed                   => 'closed_at',
            default                        => null,
        };
    }

    /** What the case is waiting for while in this queue. */
    public function waitingOn(): string
    {
        return match ($this) {
            self::NewIntake                => 'Office — complete intake',
            self::AwaitingDiagnosis        => 'Technician (linked Service Ticket)',
            self::ReadyToSubmit            => 'Warranty admin — submit to manufacturer',
            self::WaitingOnManufacturer    => 'Manufacturer decision',
            self::AwaitingCustomerDecision => 'Customer decision',
            self::ApprovedForRepair        => 'Shop — repair on linked Service Ticket',
            self::AwaitingReimbursement    => 'Manufacturer accounting',
            self::Closed                   => 'Nothing — case closed',
        };
    }
}
