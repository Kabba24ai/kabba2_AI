<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Commonize;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\EquipmentAiProfile;
use App\Models\MaintenanceManagement\EquipmentAiSpecification;
use App\Models\MaintenanceManagement\EquipmentCategoryComparisonKey;
use Illuminate\Http\Request;

class ApplyController extends Controller
{
    /**
     * Apply the admin-approved synonym groups to the database.
     *
     * For each approved group we:
     *  1. Walk every profile in the category
     *     - Collect all spec rows whose spec_key is in the group's member list
     *     - Keep one row (prefer the one already named canonically), rename it
     *     - Delete the rest (avoids the unique-key violation on profile+spec_key)
     *  2. Update the category-level comparison_keys table the same way
     *     - Keep one row, rename it, delete duplicates
     */
    public function __invoke(Request $request)
    {
        $request->validate([
            'category_id'     => 'required|exists:product_categories,id',
            'approved_groups' => 'required|string',
        ]);

        $categoryId = (int) $request->input('category_id');
        $groups     = json_decode($request->input('approved_groups'), true) ?? [];

        if (empty($groups)) {
            return redirect()
                ->route('admin.maintenance-management.equipment-ai.index', ['category_id' => $categoryId])
                ->with('info', 'No groups were selected — nothing was changed.');
        }

        $profileIds       = EquipmentAiProfile::where('category_id', $categoryId)->pluck('id');
        $mergedGroupCount = 0;
        $removedSpecCount = 0;

        foreach ($groups as $group) {
            $canonicalKey   = $group['canonical_key']   ?? null;
            $canonicalLabel = $group['canonical_label'] ?? null;
            $memberKeys     = collect($group['members'] ?? [])->pluck('spec_key')->filter()->values()->toArray();

            if (empty($canonicalKey) || empty($memberKeys)) {
                continue;
            }

            // ── 1. Rename / deduplicate per-profile specs ──────────────────
            foreach ($profileIds as $profileId) {
                $matching = EquipmentAiSpecification::where('equipment_ai_profile_id', $profileId)
                    ->whereIn('spec_key', $memberKeys)
                    ->get();

                if ($matching->isEmpty()) {
                    continue;
                }

                // Prefer keeping the row already named with the canonical key; otherwise keep first
                $keeper  = $matching->firstWhere('spec_key', $canonicalKey) ?? $matching->first();
                $deletes = $matching->where('id', '!=', $keeper->id);

                // Rename keeper to canonical
                $keeper->update([
                    'spec_key'   => $canonicalKey,
                    'spec_label' => $canonicalLabel,
                ]);

                // Delete duplicates for this profile
                if ($deletes->isNotEmpty()) {
                    EquipmentAiSpecification::whereIn('id', $deletes->pluck('id'))->delete();
                    $removedSpecCount += $deletes->count();
                }
            }

            // ── 2. Rename / deduplicate category-level comparison keys ─────
            $existingKeys = EquipmentCategoryComparisonKey::where('category_id', $categoryId)
                ->whereIn('spec_key', $memberKeys)
                ->get();

            if ($existingKeys->isNotEmpty()) {
                // Prefer the row that already carries the canonical key
                $keeperKey  = $existingKeys->firstWhere('spec_key', $canonicalKey) ?? $existingKeys->first();
                $deleteKeys = $existingKeys->where('id', '!=', $keeperKey->id);

                $keeperKey->update([
                    'spec_key'      => $canonicalKey,
                    'display_label' => $canonicalLabel,
                ]);

                if ($deleteKeys->isNotEmpty()) {
                    EquipmentCategoryComparisonKey::whereIn('id', $deleteKeys->pluck('id'))->delete();
                }
            }

            $mergedGroupCount++;
        }

        $message = $mergedGroupCount . ' synonym group(s) commonized'
            . ($removedSpecCount > 0 ? ", {$removedSpecCount} duplicate spec(s) removed" : '')
            . '.';

        return redirect()
            ->route('admin.maintenance-management.equipment-ai.index', ['category_id' => $categoryId])
            ->with('success', $message);
    }
}
