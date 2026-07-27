<?php

namespace App\Services\Reports\PaymentReconciliation;

/**
 * The canonical column contract for the Payment Reconciliation export.
 *
 * The export begins with the Authorize.Net transaction-download columns in
 * their EXACT original names and order (see the supplied authoritative export
 * Transaction_2026-07-26_204824.txt), so a Kabba export can be diffed against a
 * real Authorize.Net download with no manual column renaming. Kabba internal
 * columns and reconciliation columns are appended after, in that order.
 *
 * This class is the single source of truth for column identity/order; the
 * export service, CSV writer, and tests all read from here.
 */
final class ReconciliationColumns
{
    /**
     * The 45 Authorize.Net columns, verbatim and in original order.
     * DO NOT rename or reorder — parity with the gateway download is the point.
     */
    public const AUTHNET = [
        'Response Code',
        'Authorization Code',
        'Address Verification Status',
        'Transaction ID',
        'Submit Date/Time',
        'Card Number',
        'Expiration Date',
        'Invoice Number',
        'Invoice Description',
        'Total Amount',
        'Method',
        'Action Code',
        'Customer ID',
        'Customer First Name',
        'Customer Last Name',
        'Company',
        'Address',
        'City',
        'State',
        'ZIP',
        'Country',
        'Phone',
        'Fax',
        'Email',
        'Ship - To First Name',
        'Ship - To Last Name',
        'Ship - To Company',
        'Ship - To Address',
        'Ship - To City',
        'Ship - To State',
        'Ship - To ZIP',
        'Ship - To Country',
        'L2 - Tax',
        'L2 - Duty',
        'L2 - Freight',
        'L2 - Tax Exempt',
        'L2 - Purchase Order Number',
        'Routing Number',
        'Bank Account Number',
        'Order Number',
        'Available Card Balance',
        'Approved Amount',
        'Market Type',
        'Product',
        'Settlement Amount',
    ];

    /** Kabba internal columns appended after the Authorize.Net columns. */
    public const KABBA = [
        'Kabba Source Table',
        'Kabba Source Record ID',
        'Kabba Order ID',
        'Kabba Order Number',
        'Kabba Parent Order Number',
        'Kabba Reference Order Number',
        'Kabba Is Extension',
        'Kabba Order Date',
        'Kabba Payment Date',
        'Kabba Recorded Date',
        'Kabba Deleted Status',
        'Kabba Payment Status',
        'Kabba Payment Method',
        'Kabba Subtotal',
        'Kabba Header Tax',
        'Kabba Line Tax',
        'Kabba Grand Total',
        'Kabba Payment Amount',
        'Kabba Refund Principal',
        'Kabba Refund Tax',
        'Kabba Signed Amount',
        'Kabba Allocated Tax',
        'Kabba Derived Pre-Tax Amount',
        'Kabba Billing Charge Base',
        'Kabba Billing Charge Tax',
        'Kabba Billing Charge Total',
        'Kabba Tax Rate',
        'Kabba Tax Exempt',
    ];

    /**
     * Reconciliation columns appended last. The two derived, single-value
     * operator fields lead the block: Reconciliation Status (one primary status
     * per row) and Match Confidence (0–100%). The detailed diagnostic columns
     * that follow are unchanged.
     */
    public const RECONCILIATION = [
        'Reconciliation Status',
        'Match Confidence',
        'Match Status',
        'Match Method',
        'Amount Difference',
        'Date Difference Days',
        'Transaction ID Match',
        'Invoice Number Match',
        'Customer ID Match',
        'Customer Name Match',
        'Email Match',
        'Payment Amount Match',
        'Transaction Type Match',
        'Duplicate Gateway Transaction ID',
        'Multiple Kabba Records',
        'Missing Gateway Transaction',
        'Missing Kabba Transaction',
        'Grand Total Plus Tax Signature',
        'Payment Exceeds Order Total',
        'Payment Below Order Total',
        'Deleted Extension Transaction',
        'Parent Child Link Conflict',
        'Tax Difference',
        'Refund Difference',
        'Manual Review Required',
        'Reconciliation Notes',
    ];

    /** The full ordered header row: AuthNet, then Kabba, then reconciliation. */
    public static function all(): array
    {
        return array_merge(self::AUTHNET, self::KABBA, self::RECONCILIATION);
    }

    /** Number of leading Authorize.Net columns (for parity assertions). */
    public static function authnetCount(): int
    {
        return count(self::AUTHNET);
    }
}
