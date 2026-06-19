<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MaintenanceManagement\EquipmentAiCustomSpec;
use App\Models\MaintenanceManagement\EquipmentAiCustomSpecValue;
use App\Models\MaintenanceManagement\EquipmentAiProfile;
use App\Models\MaintenanceManagement\EquipmentAiSpecification;
use App\Models\MaintenanceManagement\EquipmentCategoryComparisonKey;
use App\Models\MaintenanceManagement\EquipmentIntelligenceRule;
use App\Models\ProductManagement\ProductCategory;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        $categories = ProductCategory::orderBy('title')->get(['id', 'title']);

        $profiles       = collect();
        $comparisonKeys = collect();
        $selectedCategory = null;
        $matrix         = [];
        $matrixProfiles = collect();

        if ($request->filled('category_id')) {
            $selectedCategory = ProductCategory::find($request->category_id);

            $profiles = EquipmentAiProfile::withCount('specifications')->withCount('keySpecifications')
                ->where('category_id', $request->category_id)
                ->orderBy('make')->orderBy('model')
                ->get();

            $comparisonKeys = EquipmentCategoryComparisonKey::where('category_id', $request->category_id)
                ->orderBy('sort_order')->orderBy('spec_key')
                ->get();

            // ── Matrix data ───────────────────────────────────────────────
            $matrixProfiles = EquipmentAiProfile::where('category_id', $request->category_id)
                ->whereHas('specifications')
                ->orderBy('make')->orderBy('model')
                ->get(['id', 'unique_id', 'make', 'model']);

            if ($matrixProfiles->isNotEmpty()) {
                $profileIds = $matrixProfiles->pluck('id');
                $compKeySet = EquipmentCategoryComparisonKey::where('category_id', $request->category_id)
                    ->pluck('spec_key', 'spec_key');

                $allSpecs = EquipmentAiSpecification::whereIn('equipment_ai_profile_id', $profileIds)
                    ->select('equipment_ai_profile_id', 'spec_key', 'spec_label', 'spec_value', 'is_key_comparison', 'is_ignored')
                    ->get();

                foreach ($allSpecs->groupBy('spec_key') as $specKey => $specsForKey) {
                    $rep = $specsForKey->sortByDesc('is_key_comparison')->first();
                    $isKey     = $specsForKey->contains('is_key_comparison', true) || isset($compKeySet[$specKey]);
                    $isIgnored = !$isKey && $specsForKey->contains('is_ignored', true);
                    $presence  = [];
                    foreach ($matrixProfiles as $p) {
                        // Placeholder rows (spec_value = null) are treated as missing
                        $presence[$p->id] = $specsForKey
                            ->where('equipment_ai_profile_id', $p->id)
                            ->filter(fn($s) => filled($s->spec_value))
                            ->isNotEmpty();
                    }
                    $matrix[] = [
                        'spec_key'          => $specKey,
                        'spec_label'        => $rep->spec_label,
                        'is_key_comparison' => $isKey,
                        'is_ignored'        => $isIgnored,
                        'is_custom'         => false,
                        'presence'          => $presence,
                    ];
                }

                foreach (EquipmentAiCustomSpec::where('category_id', $request->category_id)->get() as $cs) {
                    $valueRows = EquipmentAiCustomSpecValue::where('custom_spec_id', $cs->id)
                        ->get()->keyBy('equipment_ai_profile_id');
                    $presence = [];
                    foreach ($matrixProfiles as $p) {
                        $presence[$p->id] = (bool) optional($valueRows->get($p->id))->spec_value;
                    }
                    $matrix[] = [
                        'spec_key'          => $cs->spec_key,
                        'spec_label'        => $cs->spec_label,
                        'is_key_comparison' => $cs->is_key_comparison,
                        'is_ignored'        => $cs->is_ignored,
                        'is_custom'         => true,
                        'presence'          => $presence,
                    ];
                }

                usort($matrix, fn ($a, $b) =>
                    ($a['is_key_comparison'] ? 0 : ($a['is_ignored'] ? 2 : 1)) <=>
                    ($b['is_key_comparison'] ? 0 : ($b['is_ignored'] ? 2 : 1))
                    ?: strcasecmp($a['spec_label'], $b['spec_label'])
                );
            }
        }

        // Annotate each comparison key with whether any spec row still exists for it.
        // Orphaned keys (has_specs = false) can appear after a reset + regenerate cycle
        // when the new AI uses a slightly different spec_key name.
        if ($selectedCategory) {
            $existingMatrixKeySet = collect($matrix)->pluck('spec_key')->flip()->toArray();
            foreach ($comparisonKeys as $ck) {
                $ck->has_specs = isset($existingMatrixKeySet[$ck->spec_key]);
            }
            $activeComparisonKeyCount = $comparisonKeys->where('has_specs', true)->count();
        } else {
            $activeComparisonKeyCount = 0;
        }

        $intelligenceRuleCount = $selectedCategory
            ? EquipmentIntelligenceRule::where('equipment_category_id', $selectedCategory->id)->active()->count()
            : 0;

        $intelligenceRulePendingCount = $selectedCategory
            ? EquipmentIntelligenceRule::where('equipment_category_id', $selectedCategory->id)->active()->pending()->count()
            : 0;

        return view('admin.maintenance_management.equipment_ai.index', compact(
            'categories',
            'profiles',
            'comparisonKeys',
            'selectedCategory',
            'matrix',
            'matrixProfiles',
            'activeComparisonKeyCount',
            'intelligenceRuleCount',
            'intelligenceRulePendingCount',
        ));
    }
}
