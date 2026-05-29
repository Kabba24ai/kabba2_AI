<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\MaintenanceManagement\EquipmentAiProfile;
use App\Models\ProductManagement\ProductCategory;

class RefreshController extends Controller
{
    /**
     * Rebuild AI profiles so they match current equipment records exactly:
     *
     *  - Stale profiles (no matching equipment) with 0 specs → deleted automatically
     *  - Stale profiles that have specs → flagged as "needs_review" (not auto-deleted)
     *  - Equipment combos with no profile → created
     */
    public function __invoke(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:product_categories,id',
        ]);

        $categoryId = $request->category_id;

        // ── Step 1: Build expected (normalized_make, normalized_model) set ──────
        $equipment = Equipment::where('product_category_id', $categoryId)
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->get(['id', 'brand', 'model']);

        // Key = "normalized_make|||normalized_model" — value = canonical display values
        $expectedCombos = [];

        foreach ($equipment as $item) {
            $make  = trim($item->brand ?? '');
            $model = trim($item->model ?? '');

            $normalizedMake  = EquipmentAiProfile::normalize($make);
            $normalizedModel = EquipmentAiProfile::normalize($model);

            if ($normalizedMake === '') continue;

            $key = $normalizedMake . '|||' . $normalizedModel;

            // First occurrence wins for the display label
            if (!isset($expectedCombos[$key])) {
                $expectedCombos[$key] = [
                    'normalized_make'  => $normalizedMake,
                    'normalized_model' => $normalizedModel,
                    'make'             => $make,
                    'model'            => $model,
                ];
            }
        }

        // ── Step 2: Evaluate existing profiles ───────────────────────────────────
        $existingProfiles = EquipmentAiProfile::where('category_id', $categoryId)
            ->withCount('specifications')
            ->get();

        $deleted = 0;
        $flagged = 0;

        foreach ($existingProfiles as $profile) {
            $key = $profile->normalized_make . '|||' . $profile->normalized_model;

            if (isset($expectedCombos[$key])) {
                continue; // Still matched — keep as-is
            }

            // No matching equipment for this profile
            if ($profile->specifications_count === 0) {
                // Safe to auto-delete — nothing to lose
                $profile->delete();
                $deleted++;
            } else {
                // Has AI specs — flag for manual review, do not auto-delete
                $profile->update(['ai_status' => 'needs_review']);
                $flagged++;
            }
        }

        // ── Step 3: Create profiles for new equipment combos ─────────────────────
        $created = 0;

        foreach ($expectedCombos as $data) {
            $profile = EquipmentAiProfile::firstOrCreate(
                [
                    'category_id'      => $categoryId,
                    'normalized_make'  => $data['normalized_make'],
                    'normalized_model' => $data['normalized_model'],
                ],
                [
                    'make'      => $data['make'],
                    'model'     => $data['model'],
                    'ai_status' => 'pending',
                ]
            );

            if ($profile->wasRecentlyCreated) {
                $created++;
            }
        }

        // ── Flash result ──────────────────────────────────────────────────────────
        $category = ProductCategory::find($categoryId);

        $parts = [];
        if ($created > 0) {
            $parts[] = $created . ' new profile(s) created';
        }
        if ($deleted > 0) {
            $parts[] = $deleted . ' stale profile(s) removed';
        }
        if ($flagged > 0) {
            $parts[] = $flagged . ' orphaned profile(s) flagged for review (had specs — review and delete manually)';
        }
        if (empty($parts)) {
            $parts[] = 'No changes needed — profiles already match current equipment data';
        }

        $message = 'Refresh complete for "' . $category->title . '": ' . implode(', ', $parts) . '.';

        return redirect()
            ->route('admin.maintenance-management.equipment-ai.index', ['category_id' => $categoryId])
            ->with('success', $message);
    }
}
