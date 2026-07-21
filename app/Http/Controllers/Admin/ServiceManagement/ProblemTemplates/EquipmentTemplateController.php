<?php

namespace App\Http\Controllers\Admin\ServiceManagement\ProblemTemplates;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Equipment;
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
    private const PER_PAGE = 25;

    public function index(Request $request)
    {
        $query = Equipment::with('symptomProfile:id,name')
            ->orderBy('equipment_name');

        if ($request->filled('search')) {
            $needle = $request->search;
            $query->where(function ($q) use ($needle) {
                $q->where('equipment_name', 'like', "%{$needle}%")
                    ->orWhere('equipment_id', 'like', "%{$needle}%");
            });
        }

        if ($request->filled('template')) {
            $request->template === 'none'
                ? $query->whereNull('service_symptom_profile_id')
                : $query->where('service_symptom_profile_id', (int) $request->template);
        }

        return view('admin.service_management.problem_templates.equipment', [
            'equipment' => $query->paginate(self::PER_PAGE)->withQueryString(),
            'templates' => ServiceSymptomProfile::active()->orderBy('name')->get(['id', 'name']),
            'search'    => $request->input('search'),
            'filter'    => $request->input('template'),
        ]);
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

        return response()->json([
            'success' => true,
            'message' => $validated['template_id']
                ? "Template applied to {$count} unit(s)."
                : "Template cleared from {$count} unit(s).",
        ]);
    }
}
