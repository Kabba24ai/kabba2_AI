<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CORRECTION — Wait List selects equipment UNITS, not products.
 *
 * The unified workflow shipped selecting catalog products; the actual
 * business rule selects individual equipment inventory units (Equipment
 * IDs). equipment_wait_list_items (record ↔ equipment unit) is restored as
 * the canonical selection store; equipment_wait_list_products is retained
 * ONLY as an audit trail and as the matching fallback for records created
 * during the product-era window.
 *
 * Corrective rules (no schema changes needed — the unit pivot always
 * existed and legacy unit selections were never deleted):
 *
 *  1. LEGACY SPECIFIC-EQUIPMENT records: their original exact unit rows in
 *     equipment_wait_list_items are intact. The DERIVED product rows the
 *     unification migration added are deleted — a request for KUB-ME-1 must
 *     never match KUB-ME-7 just because both are Kub U35s.
 *
 *  2. LEGACY CATEGORY records: the original semantic was genuinely "any
 *     unit in this category" (the pre-unification matcher matched the whole
 *     category), so each is expanded to every equipment unit currently in
 *     its category, frozen as a snapshot in equipment_wait_list_items.
 *     Their derived product rows are deleted. A category with no units
 *     keeps the legacy category-matching fallback and is logged.
 *
 *  3. UNIFIED (PRODUCT-ERA) records — created through the product-based
 *     form between the two deployments: product selections do NOT reveal
 *     which individual units the employee intended, so they are NOT
 *     expanded to units. Their product rows are preserved (audit + the
 *     matcher's product fallback keeps honoring their recorded intent) and
 *     each is logged for manual correction by staff.
 *
 * Idempotent and re-run safe: unit inserts use insertOrIgnore against the
 * unique (wait_list, equipment) pair, expansion only applies to records
 * with no unit rows, and deletions target only derived legacy product rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $summary = [
            'legacy_specific_product_rows_removed' => 0,
            'legacy_category_records_expanded' => 0,
            'legacy_category_unit_rows_created' => 0,
            'legacy_category_product_rows_removed' => 0,
            'legacy_category_without_units' => [],
            'product_era_records_needing_manual_correction' => [],
        ];

        // 1. Legacy specific-equipment: exact units already intact — remove
        //    the incorrectly derived product rows so units are the only truth.
        $summary['legacy_specific_product_rows_removed'] = DB::table('equipment_wait_list_products')
            ->whereIn('equipment_wait_list_id', DB::table('equipment_wait_lists')
                ->where('request_type', 'specific_equipment')->pluck('id'))
            ->delete();

        // 2. Legacy category: expand to a frozen unit snapshot.
        $categoryRecords = DB::table('equipment_wait_lists')
            ->where('request_type', 'category')
            ->whereNotNull('product_category_id')
            ->get(['id', 'product_category_id']);

        foreach ($categoryRecords as $record) {
            if (DB::table('equipment_wait_list_items')->where('equipment_wait_list_id', $record->id)->exists()) {
                continue; // already expanded (re-run safety)
            }

            $unitIds = DB::table('equipment')
                ->where('product_category_id', $record->product_category_id)
                ->where('not_for_rent', 0)
                ->whereNull('deleted_at')
                ->pluck('id');

            if ($unitIds->isEmpty()) {
                $summary['legacy_category_without_units'][] = "wait_list #{$record->id}";
                continue; // stays on the category-matching fallback
            }

            foreach ($unitIds as $unitId) {
                DB::table('equipment_wait_list_items')->insertOrIgnore([
                    'equipment_wait_list_id' => $record->id,
                    'equipment_id'           => $unitId,
                    'created_at'             => $now,
                    'updated_at'             => $now,
                ]);
            }

            $summary['legacy_category_records_expanded']++;
            $summary['legacy_category_unit_rows_created'] += $unitIds->count();
        }

        $summary['legacy_category_product_rows_removed'] = DB::table('equipment_wait_list_products')
            ->whereIn('equipment_wait_list_id', $categoryRecords->pluck('id'))
            ->delete();

        // 3. Product-era unified records: never guess units — log for staff.
        $summary['product_era_records_needing_manual_correction'] = DB::table('equipment_wait_lists')
            ->where('request_type', 'unified')
            ->whereIn('id', DB::table('equipment_wait_list_products')->pluck('equipment_wait_list_id'))
            ->whereNotIn('id', DB::table('equipment_wait_list_items')->pluck('equipment_wait_list_id'))
            ->pluck('id')
            ->map(fn ($id) => "wait_list #{$id}")
            ->all();

        Log::info('Wait list unit-selection correction applied', $summary);

        if ($summary['product_era_records_needing_manual_correction'] !== []) {
            Log::warning('Wait list records created under the product-based form need manual unit selection', [
                'records' => $summary['product_era_records_needing_manual_correction'],
            ]);
        }
    }

    public function down(): void
    {
        // Intentionally left empty — reversing would destroy the restored
        // unit selections and the frozen category snapshots.
    }
};
