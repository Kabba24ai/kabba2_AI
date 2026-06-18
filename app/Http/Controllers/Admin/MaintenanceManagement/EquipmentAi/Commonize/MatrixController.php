<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Commonize;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\EquipmentAiCustomSpec;
use App\Models\MaintenanceManagement\EquipmentAiCustomSpecValue;
use App\Models\MaintenanceManagement\EquipmentAiProfile;
use App\Models\MaintenanceManagement\EquipmentAiSpecification;
use App\Models\MaintenanceManagement\EquipmentCategoryComparisonKey;
use App\Models\ProductManagement\ProductCategory;
use Illuminate\Http\Request;

class MatrixController extends Controller
{
    /**
     * Build and render the specification matrix for a category.
     *
     * Two types of rows:
     *   1. Extracted specs  — scraped from manufacturer AI research (is_custom = false)
     *   2. Custom specs     — manually added by admin (is_custom = true), with actual values per profile
     *
     * Rows sorted: Key (0) → Normal (1) → Ignored (2), then alphabetical within each group.
     */
    public function __invoke(Request $request)
    {
        $request->validate(['category_id' => 'required|exists:product_categories,id']);

        $categoryId = (int) $request->input('category_id');
        $category   = ProductCategory::findOrFail($categoryId);

        // Profiles that have at least one specification
        $profiles = EquipmentAiProfile::where('category_id', $categoryId)
            ->whereHas('specifications')
            ->orderBy('make')
            ->orderBy('model')
            ->get(['id', 'unique_id', 'make', 'model']);

        if ($profiles->isEmpty()) {
            return redirect()
                ->route('admin.maintenance-management.equipment-ai.index', ['category_id' => $categoryId])
                ->with('error', 'No profiles with specifications found. Run AI research on some profiles first.');
        }

        $profileIds = $profiles->pluck('id');

        // ── 1. Extracted spec rows ────────────────────────────────────────────
        $allSpecs = EquipmentAiSpecification::whereIn('equipment_ai_profile_id', $profileIds)
            ->select('equipment_ai_profile_id', 'spec_key', 'spec_label', 'spec_value', 'spec_unit', 'is_key_comparison', 'is_ignored')
            ->get();

        $compKeyRecords = EquipmentCategoryComparisonKey::where('category_id', $categoryId)->get();
        $compKeySet     = $compKeyRecords->pluck('spec_key', 'spec_key');
        $compKeyMap     = $compKeyRecords->keyBy('spec_key');

        $matrix = [];

        foreach ($allSpecs->groupBy('spec_key') as $specKey => $specsForKey) {
            $representative = $specsForKey->sortByDesc('is_key_comparison')->first();

            $presence = [];
            foreach ($profiles as $profile) {
                // A cell is "present" only when a non-null, non-empty value exists.
                // Placeholder rows (spec_value = null) are treated as missing so the gray
                // research button still appears for that cell.
                $presence[$profile->id] = $specsForKey
                    ->where('equipment_ai_profile_id', $profile->id)
                    ->filter(fn($s) => filled($s->spec_value))
                    ->isNotEmpty();
            }

            $isKey     = $specsForKey->contains('is_key_comparison', true) || isset($compKeySet[$specKey]);
            $isIgnored = !$isKey && $specsForKey->contains('is_ignored', true);
            $compKey   = $compKeyMap->get($specKey);

            $matrix[] = [
                'spec_key'           => $specKey,
                'spec_label'         => $representative->spec_label,
                'spec_unit'          => $representative->spec_unit,
                'is_key_comparison'  => $isKey,
                'is_ignored'         => $isIgnored,
                'is_custom'          => false,
                'presence'           => $presence,
                'presence_count'     => collect($presence)->filter()->count(),
                'comparison_key_id'  => $compKey?->id,
                'criteria_flags'     => $compKey ? [
                    'upgrade_exceeds_value'    => (bool) $compKey->upgrade_exceeds_value,
                    'caution_if_exceeds_value' => (bool) $compKey->caution_if_exceeds_value,
                    'upgrade_is_below_value'   => (bool) $compKey->upgrade_is_below_value,
                    'caution_if_below_value'   => (bool) $compKey->caution_if_below_value,
                ] : null,
            ];
        }

        // ── 2. Custom spec rows ───────────────────────────────────────────────
        $customSpecs = EquipmentAiCustomSpec::where('category_id', $categoryId)->get();

        foreach ($customSpecs as $customSpec) {
            $valueRows = EquipmentAiCustomSpecValue::where('custom_spec_id', $customSpec->id)
                ->get()
                ->keyBy('equipment_ai_profile_id');

            $values   = [];
            $presence = [];

            foreach ($profiles as $profile) {
                $vRow = $valueRows->get($profile->id);
                if ($vRow) {
                    $values[$profile->id] = [
                        'id'         => $vRow->id,
                        'value'      => $vRow->spec_value,
                        'source'     => $vRow->value_source,
                        'confidence' => $vRow->confidence_score,
                        'confirmed'  => $vRow->confirmed_by_user,
                        'reason'     => $vRow->ai_reason,
                    ];
                } else {
                    $values[$profile->id] = [
                        'id'         => null,
                        'value'      => null,
                        'source'     => 'unknown',
                        'confidence' => null,
                        'confirmed'  => false,
                        'reason'     => null,
                    ];
                }
                $presence[$profile->id] = $values[$profile->id]['value'] !== null;
            }

            $matrix[] = [
                'spec_key'          => $customSpec->spec_key,
                'spec_label'        => $customSpec->spec_label,
                'description'       => $customSpec->description,
                'is_key_comparison' => $customSpec->is_key_comparison,
                'is_ignored'        => $customSpec->is_ignored,
                'is_custom'         => true,
                'custom_spec_id'    => $customSpec->id,
                'value_type'        => $customSpec->value_type,
                'allowed_values'    => $customSpec->allowed_values ?? [],
                'values'            => $values,
                'presence'          => $presence,
                'presence_count'    => collect($presence)->filter()->count(),
            ];
        }

        // ── 3. Sort: Key (0) → Normal (1) → Ignored (2), then alphabetical ───
        usort($matrix, function ($a, $b) {
            $aOrder = $a['is_key_comparison'] ? 0 : ($a['is_ignored'] ? 2 : 1);
            $bOrder = $b['is_key_comparison'] ? 0 : ($b['is_ignored'] ? 2 : 1);
            return $aOrder <=> $bOrder ?: strcasecmp($a['spec_label'], $b['spec_label']);
        });

        return view('admin.maintenance_management.equipment_ai.commonize.matrix', compact(
            'category',
            'profiles',
            'matrix',
        ));
    }
}
