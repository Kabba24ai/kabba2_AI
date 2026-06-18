<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Commonize;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\EquipmentAiProfile;
use App\Models\MaintenanceManagement\EquipmentAiSpecification;
use App\Models\MaintenanceManagement\EquipmentCategoryComparisonKey;
use Illuminate\Http\Request;

class SaveFrameworkController extends Controller
{
    /**
     * Persist the admin-defined Master Specification framework.
     *
     * Expected payload:
     *   category_id  – int
     *   framework    – JSON array of:
     *     {
     *       master_key:        "horsepower",
     *       master_label:      "Horsepower",
     *       raw_keys:          ["horsepower", "hp", "engine_hp"],
     *       is_key_comparison: true,
     *       is_ignored:        false
     *     }
     *
     * Status rules (mutually exclusive, Key wins):
     *   is_key_comparison=true, is_ignored=false  → Key   — upsert EquipmentCategoryComparisonKey
     *   is_key_comparison=false, is_ignored=false → Normal — remove from EquipmentCategoryComparisonKey if present
     *   is_key_comparison=false, is_ignored=true  → Ignored — remove from EquipmentCategoryComparisonKey if present
     *
     * For each framework item the controller:
     *   1. Locates all specs in each profile whose spec_key is in raw_keys.
     *   2. Keeps one row (preferring the one already named master_key), renames it,
     *      and deletes the rest (de-dup from grouping).
     *   3. Syncs the category-level EquipmentCategoryComparisonKey record.
     */
    public function __invoke(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:product_categories,id',
            'framework'   => 'required|string',
        ]);

        $categoryId = (int) $request->input('category_id');
        $framework  = json_decode($request->input('framework'), true) ?? [];

        if (empty($framework)) {
            return redirect()
                ->route('admin.maintenance-management.equipment-ai.commonize.matrix', ['category_id' => $categoryId])
                ->with('info', 'Nothing to save — framework was empty.');
        }

        $profileIds   = EquipmentAiProfile::where('category_id', $categoryId)->pluck('id');
        $renamedCount = 0;
        $mergedCount  = 0;
        $keyCount     = 0;

        foreach ($framework as $item) {
            $masterKey   = $item['master_key']        ?? null;
            $masterLabel = $item['master_label']       ?? null;
            $rawKeys     = $item['raw_keys']           ?? [];
            $isKey       = (bool) ($item['is_key_comparison'] ?? false);
            $isIgnored   = !$isKey && (bool) ($item['is_ignored'] ?? false);

            if (!$masterKey || !$masterLabel || empty($rawKeys)) {
                continue;
            }

            // ── 1. Rename / merge per-profile specs ─────────────────────────
            foreach ($profileIds as $profileId) {
                $matching = EquipmentAiSpecification::where('equipment_ai_profile_id', $profileId)
                    ->whereIn('spec_key', $rawKeys)
                    ->get();

                if ($matching->isEmpty()) {
                    continue;
                }

                // Keep one row (prefer the row already using the canonical key)
                $keeper  = $matching->firstWhere('spec_key', $masterKey) ?? $matching->first();
                $deletes = $matching->where('id', '!=', $keeper->id);

                $keeper->update([
                    'spec_key'          => $masterKey,
                    'spec_label'        => $masterLabel,
                    'is_key_comparison' => $isKey,
                    'is_ignored'        => $isIgnored,
                ]);

                if ($deletes->isNotEmpty()) {
                    EquipmentAiSpecification::whereIn('id', $deletes->pluck('id'))->delete();
                    $mergedCount += $deletes->count();
                }

                $renamedCount++;
            }

            // ── 2. Sync category-level comparison key ────────────────────────
            if ($isKey) {
                $flags = $item['criteria_flags'] ?? [];
                EquipmentCategoryComparisonKey::updateOrCreate(
                    ['category_id' => $categoryId, 'spec_key' => $masterKey],
                    [
                        'display_label'           => $masterLabel,
                        'upgrade_exceeds_value'    => (bool) ($flags['upgrade_exceeds_value']    ?? true),
                        'caution_if_exceeds_value' => (bool) ($flags['caution_if_exceeds_value'] ?? false),
                        'upgrade_is_below_value'   => (bool) ($flags['upgrade_is_below_value']   ?? false),
                        'caution_if_below_value'   => (bool) ($flags['caution_if_below_value']   ?? false),
                    ]
                );
                $keyCount++;
            } else {
                // Remove the comparison key only if no profile still flags it
                $stillUsed = EquipmentAiSpecification::whereIn('equipment_ai_profile_id', $profileIds)
                    ->where('spec_key', $masterKey)
                    ->where('is_key_comparison', true)
                    ->exists();

                if (!$stillUsed) {
                    EquipmentCategoryComparisonKey::where('category_id', $categoryId)
                        ->where('spec_key', $masterKey)
                        ->delete();
                }
            }

            // ── 3. Merge raw-key synonyms in the comparison keys table ───────
            if (count($rawKeys) > 1) {
                $existingKeys = EquipmentCategoryComparisonKey::where('category_id', $categoryId)
                    ->whereIn('spec_key', $rawKeys)
                    ->get();

                if ($existingKeys->count() > 1) {
                    $keeperKey  = $existingKeys->firstWhere('spec_key', $masterKey) ?? $existingKeys->first();
                    $deleteKeys = $existingKeys->where('id', '!=', $keeperKey->id);

                    $keeperKey->update([
                        'spec_key'      => $masterKey,
                        'display_label' => $masterLabel,
                    ]);

                    EquipmentCategoryComparisonKey::whereIn('id', $deleteKeys->pluck('id'))->delete();
                }
            }
        }

        $parts = ['Framework saved.'];
        if ($renamedCount > 0) $parts[] = "{$renamedCount} spec row(s) updated.";
        if ($mergedCount > 0)  $parts[] = "{$mergedCount} duplicate(s) merged.";
        if ($keyCount > 0)     $parts[] = "{$keyCount} key comparison item(s) confirmed.";

        return redirect()
            ->route('admin.maintenance-management.equipment-ai.index', ['category_id' => $categoryId])
            ->with('success', implode(' ', $parts));
    }
}
