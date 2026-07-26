<?php

namespace App\Http\Controllers\Admin\ServiceManagement\ProblemTemplates;

use App\Enums\Service\ServiceSymptomProfileSymptomMode;
use App\Http\Controllers\Controller;
use App\Models\Service\ServiceSymptom;
use App\Models\Service\ServiceSymptomCategory;
use App\Models\Service\ServiceSymptomProfile;
use App\Models\Service\ServiceSymptomProfileSymptom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Problem Templates — reusable, named, ordered item lists assembled in the
 * builder and attached to equipment units. A template is a
 * ServiceSymptomProfile whose items are Include-mode profile-symptom rows
 * ordered by sort_order (Equipment problem-templates). Editing a template
 * propagates to every unit referencing it immediately; existing tickets are
 * unaffected (they hold plain-string snapshots taken at creation).
 */
class TemplateController extends Controller
{
    /**
     * Problem Library — the category-card entrance. Cards carry the category's
     * active problem count and its DISTINCT equipment-profile count (via the
     * profile↔category pivot), in the saved display order. Drilling into a card
     * manages that category's problem items (reusing the Library item endpoints).
     */
    public function index()
    {
        $totalProfiles = ServiceSymptomProfile::active()->count();

        $categories = ServiceSymptomCategory::query()
            ->withCount([
                'symptoms as active_problem_count' => fn ($q) => $q->where('is_active', true),
                'profiles as profile_count' => fn ($q) => $q->where('service_symptom_profiles.is_active', true),
            ])
            ->with(['symptoms' => fn ($q) => $q->orderBy('display_order')])
            ->orderBy('display_order')->orderBy('name')
            ->get();

        return view('admin.service_management.problem_templates.index', [
            'categories'    => $categories,
            'totalProfiles' => $totalProfiles,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $template = ServiceSymptomProfile::create([
            'name'          => trim($validated['name']),
            'description'   => $validated['description'] ?? null,
            'display_order' => (int) ServiceSymptomProfile::max('display_order') + 10,
            'is_active'     => true,
        ]);

        return response()->json([
            'success'     => true,
            'template'    => $template,
            'builder_url' => route('admin.service-management.problem-templates.builder', $template),
        ]);
    }

    public function builder(ServiceSymptomProfile $profile)
    {
        return view('admin.service_management.problem_templates.builder', [
            'template'   => $profile,
            'categories' => ServiceSymptomCategory::active()->orderBy('display_order')->get(['id', 'name']),
            'items'      => ServiceSymptom::active()->orderBy('display_order')
                ->get(['id', 'service_symptom_category_id', 'name']),
            // Current explicit items, in template order.
            'selectedItemIds' => $profile->profileSymptoms()
                ->where('mode', ServiceSymptomProfileSymptomMode::Include)
                ->orderBy('sort_order')->pluck('service_symptom_id')->values(),
        ]);
    }

    public function update(Request $request, ServiceSymptomProfile $profile)
    {
        $validated = $request->validate([
            'name'        => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active'   => ['sometimes', 'boolean'],
        ]);

        $profile->update($validated);

        return response()->json(['success' => true, 'template' => $profile->fresh()]);
    }

    public function destroy(ServiceSymptomProfile $profile)
    {
        // Detaching happens by construction: equipment.service_symptom_profile_id
        // is nullOnDelete, so units simply revert to "No Template Listed".
        $profile->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Replace the template's explicit item list with the submitted ordered
     * set (the builder's whole point). Include-mode rows only — legacy
     * category-include rows, if any, are left untouched. Idempotent: the
     * unique (profile,symptom) index means re-saving the same set is safe.
     */
    public function saveItems(Request $request, ServiceSymptomProfile $profile)
    {
        $validated = $request->validate([
            'item_ids'   => ['present', 'array'],
            'item_ids.*' => ['integer', 'exists:service_symptoms,id'],
        ]);

        DB::transaction(function () use ($profile, $validated) {
            $profile->profileSymptoms()
                ->where('mode', ServiceSymptomProfileSymptomMode::Include)
                ->delete();

            foreach (array_values($validated['item_ids']) as $index => $symptomId) {
                ServiceSymptomProfileSymptom::create([
                    'service_symptom_profile_id' => $profile->id,
                    'service_symptom_id'         => $symptomId,
                    'mode'                       => ServiceSymptomProfileSymptomMode::Include->value,
                    'sort_order'                 => ($index + 1) * 10,
                ]);
            }
        });

        return response()->json(['success' => true, 'count' => count($validated['item_ids'])]);
    }
}
