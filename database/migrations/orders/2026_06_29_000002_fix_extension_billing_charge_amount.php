<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Extension BillingCharge records created before this fix stored
        // amount = grand_total (subtotal + tax). The view adds tax_amount on
        // top, producing a doubled tax display (e.g. $1,302.73 + $115.73 = $1,418.46).
        //
        // The correct value is amount = subtotal (pre-tax base).
        // The original base_amount is preserved in the metadata JSON column
        // by Extension\StoreController, so we restore it from there rather
        // than deriving it as (amount - tax_amount), which would be wrong for
        // any charges where amount was subsequently adjusted.
        //
        // Only corrects records where tax was applied (tax_amount > 0) and
        // metadata->base_amount is present (all records created by StoreController).
        DB::statement("
            UPDATE billing_charges
            SET amount = CAST(JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.base_amount')) AS DECIMAL(10,2))
            WHERE billing_charge_type = 'extension'
            AND tax_amount > 0
            AND JSON_EXTRACT(metadata, '$.base_amount') IS NOT NULL
        ");
    }

    public function down(): void
    {
        // Restore amount = base_amount + tax_amount (the previous incorrect state).
        DB::statement("
            UPDATE billing_charges
            SET amount = CAST(JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.base_amount')) AS DECIMAL(10,2))
                       + tax_amount
            WHERE billing_charge_type = 'extension'
            AND tax_amount > 0
            AND JSON_EXTRACT(metadata, '$.base_amount') IS NOT NULL
        ");
    }
};
