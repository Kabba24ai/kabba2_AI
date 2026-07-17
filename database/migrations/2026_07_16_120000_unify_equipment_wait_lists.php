<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Unified Wait List workflow: one record = one customer + one category +
 * one or more selected acceptable PRODUCTS (product types, not inventory
 * units). Replaces the old Category-vs-Specific-Equipment branching.
 *
 * Schema:
 *  - equipment_wait_list_products: normalized selected-product set (no
 *    fixed Choice #1–#3 columns, no artificial limit). The set is a
 *    snapshot owned by the record — later catalog/category changes never
 *    silently alter an existing request.
 *  - equipment_wait_list_alerts.disposition: outcome of working a match
 *    (App\Enums\WaitList\WaitListAlertDisposition).
 *  - equipment_wait_list_alerts.matched_product_id: the returned unit's
 *    product at match time, snapshotted for display/history.
 *
 * Legacy-record migration (rule documented here, applied below):
 *  - CATEGORY requests meant "anything in this category", so each one is
 *    mapped to every Rental product currently in its category. A category
 *    with no Rental products yields zero selections; the record is logged
 *    and the matcher keeps honoring it via the legacy category fallback —
 *    no guessing.
 *  - SPECIFIC-EQUIPMENT requests: each chosen unit's assigned product
 *    becomes a selected acceptable product. The record's category is set
 *    from the units when they agree on exactly one category. Units with no
 *    assigned product (or records whose units disagree on category) are
 *    logged; a record left with zero selections keeps matching via the
 *    legacy exact-equipment fallback — no guessing.
 *  - Historical rows are never deleted or rewritten: request_type keeps
 *    its original value and equipment_wait_list_items stays intact.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Idempotent guards: safe to re-run against a partially/fully
        // migrated database, and lets tests exercise the legacy-record
        // migration rule directly.
        if (! Schema::hasTable('equipment_wait_list_products')) {
            Schema::create('equipment_wait_list_products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('equipment_wait_list_id')
                    ->constrained(table: 'equipment_wait_lists', indexName: 'wl_prod_wait_list_fk')
                    ->cascadeOnDelete();
                $table->foreignId('product_id')
                    ->constrained(table: 'products', indexName: 'wl_prod_product_fk')
                    ->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['equipment_wait_list_id', 'product_id'], 'wait_list_product_unique');
            });
        }

        if (! Schema::hasColumn('equipment_wait_list_alerts', 'disposition')) {
            Schema::table('equipment_wait_list_alerts', function (Blueprint $table) {
                $table->string('disposition')->nullable()->after('status');
                $table->foreignId('matched_product_id')->nullable()->after('matched_category_id')
                    ->constrained(table: 'products', indexName: 'wl_alert_matched_product_fk')
                    ->nullOnDelete();
            });
        }

        $this->migrateLegacyRecords();
    }

    public function down(): void
    {
        Schema::table('equipment_wait_list_alerts', function (Blueprint $table) {
            $table->dropForeign('wl_alert_matched_product_fk');
            $table->dropColumn(['disposition', 'matched_product_id']);
        });

        Schema::dropIfExists('equipment_wait_list_products');
    }

    private function migrateLegacyRecords(): void
    {
        $now = now();
        $unmigratable = [];
        $summary = [
            'category_records_migrated' => 0,
            'specific_records_migrated' => 0,
            'product_selections_created' => 0,
            // Legacy specific-unit picks deliberately BROADEN to product level:
            // a request once tied to one exact unit now matches any unit of the
            // same product (and category). Multiple units of one product
            // collapse into a single product selection; this log quantifies it.
            'unit_selections_expanded' => 0,
            'units_collapsed_into_shared_product' => 0,
        ];

        // Category requests → every Rental product currently in the category.
        $categoryRecords = DB::table('equipment_wait_lists')
            ->where('request_type', 'category')
            ->whereNotNull('product_category_id')
            ->get(['id', 'product_category_id']);

        foreach ($categoryRecords as $record) {
            // Re-run safety: a record that already owns a product set is
            // final — later catalog changes never alter an existing request.
            if (DB::table('equipment_wait_list_products')->where('equipment_wait_list_id', $record->id)->exists()) {
                continue;
            }

            $productIds = DB::table('product_category_children')
                ->join('products', 'products.id', '=', 'product_category_children.product_id')
                ->where('product_category_children.product_category_id', $record->product_category_id)
                ->where('products.product_type', 'Rental')
                ->pluck('products.id')->unique();

            if ($productIds->isEmpty()) {
                $unmigratable[] = "wait_list #{$record->id} (category request, no Rental products in category {$record->product_category_id})";
                continue;
            }

            foreach ($productIds as $productId) {
                DB::table('equipment_wait_list_products')->insertOrIgnore([
                    'equipment_wait_list_id' => $record->id,
                    'product_id'             => $productId,
                    'created_at'             => $now,
                    'updated_at'             => $now,
                ]);
            }

            $summary['category_records_migrated']++;
            $summary['product_selections_created'] += $productIds->count();
        }

        // Specific-equipment requests → each unit's assigned product; the
        // record's category comes from the units when they agree on one.
        $specificRecords = DB::table('equipment_wait_lists')
            ->where('request_type', 'specific_equipment')
            ->get(['id', 'product_category_id']);

        foreach ($specificRecords as $record) {
            if (DB::table('equipment_wait_list_products')->where('equipment_wait_list_id', $record->id)->exists()) {
                continue;
            }

            $units = DB::table('equipment_wait_list_items')
                ->join('equipment', 'equipment.id', '=', 'equipment_wait_list_items.equipment_id')
                ->where('equipment_wait_list_items.equipment_wait_list_id', $record->id)
                ->get(['equipment.assigned_product_id', 'equipment.product_category_id']);

            $productIds = $units->pluck('assigned_product_id')->filter()->unique();

            if ($productIds->isEmpty()) {
                $unmigratable[] = "wait_list #{$record->id} (specific request, no unit has an assigned product)";
                continue;
            }

            foreach ($productIds as $productId) {
                DB::table('equipment_wait_list_products')->insertOrIgnore([
                    'equipment_wait_list_id' => $record->id,
                    'product_id'             => $productId,
                    'created_at'             => $now,
                    'updated_at'             => $now,
                ]);
            }

            $unitsWithProducts = $units->pluck('assigned_product_id')->filter()->count();
            $summary['specific_records_migrated']++;
            $summary['product_selections_created'] += $productIds->count();
            $summary['unit_selections_expanded'] += $unitsWithProducts;
            $summary['units_collapsed_into_shared_product'] += $unitsWithProducts - $productIds->count();

            $categories = $units->pluck('product_category_id')->filter()->unique();
            if ($record->product_category_id === null && $categories->count() === 1) {
                DB::table('equipment_wait_lists')
                    ->where('id', $record->id)
                    ->update(['product_category_id' => $categories->first()]);
            } elseif ($record->product_category_id === null) {
                $unmigratable[] = "wait_list #{$record->id} (specific request, units span " . $categories->count() . ' categories — category left null)';
            }
        }

        if ($summary['category_records_migrated'] > 0 || $summary['specific_records_migrated'] > 0) {
            Log::info('Wait list unification: legacy records migrated', $summary);
        }

        if ($unmigratable !== []) {
            Log::warning('Wait list unification: records kept on legacy matching fallbacks', [
                'records' => $unmigratable,
            ]);
        }
    }
};
