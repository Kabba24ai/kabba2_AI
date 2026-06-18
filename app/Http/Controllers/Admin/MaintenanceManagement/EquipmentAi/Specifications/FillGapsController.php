<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Specifications;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\EquipmentAiProfile;
use App\Models\MaintenanceManagement\EquipmentAiSpecification;
use App\Models\MaintenanceManagement\EquipmentCategoryComparisonKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FillGapsController extends Controller
{
    /**
     * Ensure every profile in a category has a record (possibly null) for every
     * spec_key that exists anywhere in the category.  Rows created here use
     * source = 'placeholder' and spec_value = null so the matrix can distinguish
     * "not yet researched" from "AI found nothing".
     *
     * POST /profiles/{unique_id}/specifications/fill-gaps
     *   → fills gaps for one profile (called internally by GenerateController too)
     *
     * POST /category-fill-gaps  (category_id body param)
     *   → fills gaps across the entire category  (manual "catch-all" button)
     */
    public function __invoke(Request $request, ?string $uniqueId = null): JsonResponse
    {
        // Support both single-profile and whole-category modes
        if ($uniqueId !== null) {
            $profile    = EquipmentAiProfile::where('unique_id', $uniqueId)->firstOrFail();
            $categoryId = $profile->category_id;
            $profileIds = EquipmentAiProfile::where('category_id', $categoryId)->pluck('id');
        } else {
            $request->validate(['category_id' => 'required|exists:product_categories,id']);
            $categoryId = (int) $request->input('category_id');
            $profile    = null;
            $profileIds = EquipmentAiProfile::where('category_id', $categoryId)->pluck('id');
        }

        // Build the master spec-key → label map across ALL profiles in the category
        $masterKeys = EquipmentAiSpecification::whereIn('equipment_ai_profile_id', $profileIds)
            ->select('spec_key', 'spec_label', 'spec_unit')
            ->get()
            ->filter(fn($s) => filled($s->spec_key))
            ->unique('spec_key')
            ->mapWithKeys(fn($s) => [trim($s->spec_key) => [
                'label' => trim($s->spec_label),
                'unit'  => $s->spec_unit,
            ]]);

        if ($masterKeys->isEmpty()) {
            return response()->json(['success' => true, 'filled' => 0, 'message' => 'No spec keys found in category.']);
        }

        // Which profiles to fill?
        $targetIds = $profile ? collect([$profile->id]) : $profileIds;

        $filled = 0;

        foreach ($targetIds as $pid) {
            // Keys this profile already has
            $existing = EquipmentAiSpecification::where('equipment_ai_profile_id', $pid)
                ->pluck('spec_key')
                ->map(fn($k) => trim(strtolower((string) $k)))
                ->flip()
                ->toArray();

            foreach ($masterKeys as $specKey => $meta) {
                $normalizedKey = trim(strtolower((string) $specKey));
                if (isset($existing[$normalizedKey])) {
                    continue;
                }

                EquipmentAiSpecification::create([
                    'equipment_ai_profile_id' => $pid,
                    'spec_key'                => $specKey,
                    'spec_label'              => $meta['label'],
                    'spec_unit'               => $meta['unit'],
                    'spec_value'              => null,
                    'confidence_score'        => 0,
                    'source'                  => 'placeholder',
                ]);

                $filled++;
            }
        }

        // Sync is_key_comparison from category-level keys so placeholder rows
        // for key specs are flagged correctly (keeps matrix + profile counts in sync)
        $catKeys = EquipmentCategoryComparisonKey::where('category_id', $categoryId)
            ->pluck('spec_key')->toArray();
        if (!empty($catKeys)) {
            EquipmentAiSpecification::whereIn('equipment_ai_profile_id', $targetIds)
                ->whereIn('spec_key', $catKeys)
                ->update(['is_key_comparison' => true]);
        }

        return response()->json([
            'success' => true,
            'filled'  => $filled,
            'message' => $filled . ' placeholder row(s) inserted.',
        ]);
    }
}
