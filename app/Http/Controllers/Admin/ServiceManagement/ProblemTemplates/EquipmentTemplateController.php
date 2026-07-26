<?php

namespace App\Http\Controllers\Admin\ServiceManagement\ProblemTemplates;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\ProductManagement\ProductCategory;
use App\Models\Service\ServiceSymptomProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Equipment attachment — set or clear the Reported-Problem template on
 * equipment units. One template attaches to many units; each unit holds at
 * most one (equipment.service_symptom_profile_id, nullable). Supports a
 * per-row change and a bulk "apply to selected units" action so a template
 * can be put on all 21 skid steers at once.
 */
class EquipmentTemplateController extends Controller
{
    /**
     * Category-first assignment workbench: pick an equipment category, see only
     * that category's units, then bulk-apply a template (or override per row).
     * The filtered set loads in full (no pagination) so "Select All Shown" is
     * unambiguous.
     */
    public function index(Request $request)
    {
        $categories = ProductCategory::whereHas('equipments')->orderBy('title')->get(['id', 'title']);

        $selected  = (string) $request->input('category', '');
        $equipment = collect();

        if ($selected === 'all') {
            $equipment = $this->equipmentQuery()->get();
        } elseif (ctype_digit($selected)) {
            $equipment = $this->equipmentQuery()->where('product_category_id', (int) $selected)->get();
        }

        return view('admin.service_management.problem_templates.equipment', [
            'categories'       => $categories,
            'equipment'        => $equipment,
            'templates'        => ServiceSymptomProfile::active()->orderBy('name')->get(['id', 'name']),
            'selectedCategory' => $selected,
        ]);
    }

    private function equipmentQuery()
    {
        return Equipment::with('symptomProfile:id,name')
            ->orderBy('equipment_name')
            ->select(['id', 'equipment_name', 'equipment_id', 'product_category_id', 'service_symptom_profile_id']);
    }

    /** Set or clear one unit's template (template_id null = detach). */
    public function attach(Request $request)
    {
        $validated = $request->validate([
            'equipment_id' => ['required', 'integer', 'exists:equipment,id'],
            'template_id'  => ['nullable', 'integer', 'exists:service_symptom_profiles,id'],
        ]);

        Equipment::where('id', $validated['equipment_id'])
            ->update(['service_symptom_profile_id' => $validated['template_id'] ?: null]);

        return response()->json(['success' => true]);
    }

    /** Apply one template (or clear) across many units at once. */
    public function bulkApply(Request $request)
    {
        $validated = $request->validate([
            'equipment_ids'   => ['required', 'array', 'min:1'],
            'equipment_ids.*' => ['integer', 'exists:equipment,id'],
            'template_id'     => ['nullable', 'integer', 'exists:service_symptom_profiles,id'],
        ]);

        $count = 0;
        DB::transaction(function () use ($validated, &$count) {
            $count = Equipment::whereIn('id', $validated['equipment_ids'])
                ->update(['service_symptom_profile_id' => $validated['template_id'] ?: null]);
        });

        return response()->json(['success' => true, 'count' => $count]);
    }
}
