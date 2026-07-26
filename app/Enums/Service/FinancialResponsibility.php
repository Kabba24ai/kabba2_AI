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
    // Damage Waiver is its OWN financial identity — never folded into Internal
    // Expense or Goodwill. Current workflow mirrors Internal Expense (no
    // customer invoice, management approval, internal repair processing), but
    // the disposition is tracked separately so reporting can compare Damage
    // Waiver revenue against Damage Waiver repair cost as a standalone KPI.
    case DamageWaiver    = 'damage_waiver';
    case Insurance       = 'insurance_future';

    public function label(): string
    {
        return match ($this) {
            self::Pending         => 'Pending Diagnosis',
            self::CustomerPay     => 'Customer Pay',
            self::OemWarranty     => 'OEM Warranty',
            self::InternalExpense => 'Internal Expense',
            self::Goodwill        => 'Goodwill',
            self::DamageWaiver    => 'Damage Waiver',
            self::Insurance       => 'Insurance',
        };
    }
}
