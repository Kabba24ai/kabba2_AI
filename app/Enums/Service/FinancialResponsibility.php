<?php

namespace App\Enums\Service;

enum FinancialResponsibility: string
{
    // Diagnostic-first default — responsibility is unknown until diagnosis.
    case Pending         = 'pending';
    case CustomerPay     = 'customer_pay';
    case OemWarranty     = 'oem_warranty';
    case InternalExpense = 'internal_company_expense';
    case Goodwill        = 'goodwill';
    case Insurance       = 'insurance_future';

    public function label(): string
    {
        return match ($this) {
            self::Pending         => 'Pending Diagnosis',
            self::CustomerPay     => 'Customer Pay',
            self::OemWarranty     => 'OEM Warranty',
            self::InternalExpense => 'Internal Expense',
            self::Goodwill        => 'Goodwill',
            self::Insurance       => 'Insurance',
        };
    }
}
