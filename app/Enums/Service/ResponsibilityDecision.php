<?php

namespace App\Enums\Service;

enum ResponsibilityDecision: string
{
    case Pending         = 'pending';
    case CustomerPay     = 'customer_pay';
    case OemWarranty     = 'oem_warranty';
    case InternalExpense = 'internal_company_expense';
    case Goodwill        = 'goodwill';
    case NoProblemFound  = 'no_problem_found';
    case NotRepairable   = 'not_repairable';

    public function label(): string
    {
        return match ($this) {
            self::Pending         => 'Pending',
            self::CustomerPay     => 'Customer Pay',
            self::OemWarranty     => 'OEM Warranty',
            self::InternalExpense => 'Internal Expense',
            self::Goodwill        => 'Goodwill',
            self::NoProblemFound  => 'No Problem Found',
            self::NotRepairable   => 'Not Repairable',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending         => 'bg-gray-100 text-gray-500',
            self::CustomerPay     => 'bg-orange-100 text-orange-700',
            self::OemWarranty     => 'bg-indigo-100 text-indigo-700',
            self::InternalExpense => 'bg-gray-100 text-gray-600',
            self::Goodwill        => 'bg-teal-100 text-teal-700',
            self::NoProblemFound  => 'bg-green-100 text-green-700',
            self::NotRepairable   => 'bg-red-100 text-red-700',
        };
    }

    /**
     * The financial responsibility a decision maps onto. No Problem Found and
     * Not Repairable have no payer path — they leave financial responsibility
     * untouched (the ticket can be closed; escalation is a later phase).
     */
    public function financialResponsibility(): ?FinancialResponsibility
    {
        return match ($this) {
            self::CustomerPay     => FinancialResponsibility::CustomerPay,
            self::OemWarranty     => FinancialResponsibility::OemWarranty,
            self::InternalExpense => FinancialResponsibility::InternalExpense,
            self::Goodwill        => FinancialResponsibility::Goodwill,
            default               => null,
        };
    }
}
