<?php

namespace App\Enums\Service;

enum ServiceTicketEventType: string
{
    case TicketCreated          = 'ticket_created';
    case TicketUpdated          = 'ticket_updated';
    case StatusChanged          = 'status_changed';
    case FinancialStatusChanged = 'financial_status_changed';
    case PersonnelAdded         = 'personnel_added';
    case PersonnelRemoved       = 'personnel_removed';
    case LaborAdded             = 'labor_added';
    case LaborRemoved           = 'labor_removed';
    case ChargeLineAdded        = 'charge_line_added';
    case ChargeLineRemoved      = 'charge_line_removed';
    case PartAdded              = 'part_added';
    case PartRemoved            = 'part_removed';
    case MediaUploaded          = 'media_uploaded';
    case MediaRemoved           = 'media_removed';
    case Completed              = 'completed';
    case Closed                 = 'closed';
    case Reopened               = 'reopened';
    case DiagnosticStarted      = 'diagnostic_started';
    case DiagnosticCompleted    = 'diagnostic_completed';
    case ResponsibilityDecisionChanged = 'responsibility_decision_changed';
    case DiagnosticFeeUpdated   = 'diagnostic_fee_updated';
    case EstimateSent           = 'estimate_sent';
    case EstimateApproved       = 'estimate_approved';
    case EstimateDeclined       = 'estimate_declined';
    case RepairAuthorized       = 'repair_authorized';
    case RepairAuthorizationRevoked = 'repair_authorization_revoked';
    case PartsDepositRequired   = 'parts_deposit_required';
    case PartsDepositPaid       = 'parts_deposit_paid';
    case PartsDepositOverridden = 'parts_deposit_overridden';
    case AuthorizationOverride  = 'authorization_override';
    case RepairStartBlocked     = 'repair_start_blocked';
    case RepairStarted          = 'repair_started';
    case SettlementPreviewGenerated = 'settlement_preview_generated';
    case SettlementUpdated      = 'settlement_updated';
    case CustomerChargeCreated  = 'customer_charge_created';

    public function label(): string
    {
        return match ($this) {
            self::TicketCreated          => 'Ticket Created',
            self::TicketUpdated          => 'Ticket Updated',
            self::StatusChanged          => 'Status Changed',
            self::FinancialStatusChanged => 'Financial Status Changed',
            self::PersonnelAdded         => 'Personnel Added',
            self::PersonnelRemoved       => 'Personnel Removed',
            self::LaborAdded             => 'Labor Added',
            self::LaborRemoved           => 'Labor Removed',
            self::ChargeLineAdded        => 'Charge Line Added',
            self::ChargeLineRemoved      => 'Charge Line Removed',
            self::PartAdded              => 'Part Added',
            self::PartRemoved            => 'Part Removed',
            self::MediaUploaded          => 'Media Uploaded',
            self::MediaRemoved           => 'Media Removed',
            self::Completed              => 'Ticket Completed',
            self::Closed                 => 'Ticket Closed',
            self::Reopened               => 'Ticket Reopened',
            self::DiagnosticStarted      => 'Diagnostic Started',
            self::DiagnosticCompleted    => 'Diagnostic Completed',
            self::ResponsibilityDecisionChanged => 'Responsibility Decided',
            self::DiagnosticFeeUpdated   => 'Diagnostic Fee Updated',
            self::EstimateSent           => 'Estimate Sent',
            self::EstimateApproved       => 'Estimate Approved',
            self::EstimateDeclined       => 'Estimate Declined',
            self::RepairAuthorized       => 'Repair Authorized',
            self::RepairAuthorizationRevoked => 'Repair Authorization Revoked',
            self::PartsDepositRequired   => 'Parts Deposit Required',
            self::PartsDepositPaid       => 'Parts Deposit Paid',
            self::PartsDepositOverridden => 'Parts Deposit Overridden',
            self::AuthorizationOverride  => 'Authorization Override',
            self::RepairStartBlocked     => 'Repair Start Blocked',
            self::RepairStarted          => 'Repair Started',
            self::SettlementPreviewGenerated => 'Settlement Preview Generated',
            self::SettlementUpdated      => 'Settlement Updated',
            self::CustomerChargeCreated  => 'Customer Charge Created',
        };
    }

    /** Timeline dot color per event family. */
    public function color(): string
    {
        return match ($this) {
            self::TicketCreated                                       => 'bg-blue-500',
            self::Completed                                           => 'bg-green-500',
            self::Closed                                              => 'bg-gray-700',
            self::Reopened                                            => 'bg-blue-400',
            self::StatusChanged                                       => 'bg-sky-400',
            self::FinancialStatusChanged                              => 'bg-emerald-400',
            self::PersonnelAdded, self::PersonnelRemoved              => 'bg-indigo-400',
            self::LaborAdded, self::LaborRemoved                      => 'bg-purple-400',
            self::ChargeLineAdded, self::ChargeLineRemoved            => 'bg-teal-400',
            self::PartAdded, self::PartRemoved                        => 'bg-orange-400',
            self::MediaUploaded, self::MediaRemoved                   => 'bg-amber-400',
            self::DiagnosticStarted, self::DiagnosticCompleted        => 'bg-cyan-500',
            self::ResponsibilityDecisionChanged                       => 'bg-rose-400',
            self::DiagnosticFeeUpdated                                => 'bg-lime-500',
            self::EstimateSent, self::EstimateApproved, self::EstimateDeclined => 'bg-fuchsia-400',
            self::RepairAuthorized                                    => 'bg-green-600',
            self::RepairAuthorizationRevoked                          => 'bg-red-500',
            self::PartsDepositRequired, self::PartsDepositPaid,
            self::PartsDepositOverridden                              => 'bg-yellow-500',
            self::AuthorizationOverride                               => 'bg-purple-500',
            self::RepairStartBlocked                                  => 'bg-red-400',
            self::RepairStarted                                       => 'bg-green-500',
            self::SettlementPreviewGenerated, self::SettlementUpdated => 'bg-emerald-500',
            self::CustomerChargeCreated                               => 'bg-emerald-600',
            default                                                   => 'bg-gray-300',
        };
    }
}
