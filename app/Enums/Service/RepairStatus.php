<?php

namespace App\Enums\Service;

enum RepairStatus: string
{
    case Open                      = 'open';
    case Diagnosing                = 'diagnosing';
    case WaitingOnParts            = 'waiting_on_parts';
    case WaitingOnCustomerApproval = 'waiting_on_customer_approval';
    case WaitingOnWarrantyApproval = 'waiting_on_warranty_approval';
    case InProgress                = 'in_progress';
    case ReadyForPickup            = 'ready_for_pickup';
    case Completed                 = 'completed';
    case Closed                    = 'closed';
    case Cancelled                 = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Open                      => 'Open',
            self::Diagnosing                => 'Diagnosing',
            self::WaitingOnParts            => 'Waiting on Parts',
            self::WaitingOnCustomerApproval => 'Waiting on Customer Approval',
            self::WaitingOnWarrantyApproval => 'Waiting on Warranty Approval',
            self::InProgress                => 'In Progress',
            self::ReadyForPickup            => 'Ready for Pickup',
            self::Completed                 => 'Completed',
            self::Closed                    => 'Closed',
            self::Cancelled                 => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open                      => 'bg-blue-100 text-blue-700',
            self::Diagnosing                => 'bg-sky-100 text-sky-700',
            self::WaitingOnParts            => 'bg-amber-100 text-amber-700',
            self::WaitingOnCustomerApproval => 'bg-yellow-100 text-yellow-700',
            self::WaitingOnWarrantyApproval => 'bg-indigo-100 text-indigo-700',
            self::InProgress                => 'bg-green-100 text-green-700',
            self::ReadyForPickup            => 'bg-teal-100 text-teal-700',
            self::Completed                 => 'bg-gray-100 text-gray-600',
            self::Closed                    => 'bg-gray-100 text-gray-500',
            self::Cancelled                 => 'bg-gray-100 text-gray-400',
        };
    }

    /** Statuses that mean the ticket cannot be acted on right now. */
    public static function blocked(): array
    {
        return [
            self::WaitingOnParts->value,
            self::WaitingOnCustomerApproval->value,
            self::WaitingOnWarrantyApproval->value,
        ];
    }

    /** Statuses that mean work can proceed now (Active Work Queue). */
    public static function active(): array
    {
        return [
            self::Open->value,
            self::Diagnosing->value,
            self::InProgress->value,
            self::ReadyForPickup->value,
        ];
    }

    /** Anything not finished — open in the broadest sense (KPI: Open Tickets). */
    public static function notFinished(): array
    {
        return array_merge(self::active(), self::blocked());
    }

    /**
     * Statuses that represent repair work beginning or finishing — entering
     * them requires the repair authorization gate to be satisfied.
     */
    public function requiresAuthorization(): bool
    {
        return in_array($this, [
            self::InProgress,
            self::ReadyForPickup,
            self::Completed,
            self::Closed,
        ], true);
    }

    /** Human label for what a blocked ticket is waiting on. */
    public function waitingOnLabel(): string
    {
        return match ($this) {
            self::WaitingOnParts            => 'Parts',
            self::WaitingOnCustomerApproval => 'Customer Approval',
            self::WaitingOnWarrantyApproval => 'Warranty Approval',
            default                         => '—',
        };
    }
}
