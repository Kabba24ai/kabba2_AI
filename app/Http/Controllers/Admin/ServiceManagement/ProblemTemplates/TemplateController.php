<?php

namespace App\Http\Controllers\Admin\ServiceManagement\ProblemTemplates;

use App\Enums\Service\ServiceSymptomProfileSymptomMode;
use App\Http\Controllers\Controller;
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

    /** Create a template (name/description/active) with its Include items. */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active'   => ['sometimes', 'boolean'],
            'item_ids'    => ['present', 'array'],
            'item_ids.*'  => ['integer', 'exists:service_symptoms,id'],
        ]);

        $template = ServiceSymptomProfile::create([
            'name'          => trim($validated['name']),
            'description'   => $validated['description'] ?? null,
            'display_order' => (int) ServiceSymptomProfile::max('display_order') + 10,
            'is_active'     => $request->boolean('is_active', true),
        ]);

        $this->syncItems($template, $validated['item_ids'] ?? []);

        return response()->json([
            'success'  => true,
            'redirect' => route('admin.service-management.problem-templates.index'),
        ]);
    }

    /** Create-mode builder (empty template). */
    public function create()
    {
        return view('admin.service_management.problem_templates.builder', $this->builderData(null));
    }

    /** Edit-mode builder (preloaded template) — same view as create. */
    public function builder(ServiceSymptomProfile $profile)
    {
        return view('admin.service_management.problem_templates.builder', $this->builderData($profile));
    }

    public function update(Request $request, ServiceSymptomProfile $profile)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active'   => ['sometimes', 'boolean'],
            'item_ids'    => ['sometimes', 'array'],
            'item_ids.*'  => ['integer', 'exists:service_symptoms,id'],
        ]);

        $profile->update([
            'name'        => trim($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_active'   => $request->boolean('is_active', (bool) $profile->is_active),
        ]);

        if ($request->has('item_ids')) {
            $this->syncItems($profile, $validated['item_ids'] ?? []);
        }

        return response()->json([
            'success'  => true,
            'redirect' => route('admin.service-management.problem-templates.index'),
        ]);
    }

    public function destroy(ServiceSymptomProfile $profile)
    {
        // Detaching happens by construction: equipment.service_symptom_profile_id
        // is nullOnDelete, so units simply revert to "No Template Listed".
        $profile->delete();

        return response()->json(['success' => true]);
    }

    /** Shared builder view data — category cards (with counts) + grouped active items. */
    private function builderData(?ServiceSymptomProfile $profile): array
    {
        $totalProfiles = ServiceSymptomProfile::active()->count();

        $categories = ServiceSymptomCategory::active()
            ->withCount([
                'symptoms as active_problem_count' => fn ($q) => $q->where('is_active', true),
                'profiles as profile_count' => fn ($q) => $q->where('service_symptom_profiles.is_active', true),
            ])
            ->with(['symptoms' => fn ($q) => $q->where('is_active', true)->orderBy('display_order')])
            ->orderBy('display_order')->orderBy('name')
            ->get();

        return [
            'template'        => $profile,
            'categories'      => $categories,
            'totalProfiles'   => $totalProfiles,
            'selectedItemIds' => $profile
                ? $profile->profileSymptoms()
                    ->where('mode', ServiceSymptomProfileSymptomMode::Include)
                    ->orderBy('sort_order')->pluck('service_symptom_id')->map(fn ($i) => (int) $i)->values()
                : collect(),
        ];
    }

    /**
     * Replace the template's Include item set with the submitted ids, in submit
     * order. Rendering (builder + intake) always resolves the canonical Problem
     * Library order, so sort_order here never competes with the library.
     */
    private function syncItems(ServiceSymptomProfile $profile, array $itemIds): void
    {
        DB::transaction(function () use ($profile, $itemIds) {
            $profile->profileSymptoms()
                ->where('mode', ServiceSymptomProfileSymptomMode::Include)
                ->delete();

            foreach (array_values($itemIds) as $index => $symptomId) {
                ServiceSymptomProfileSymptom::create([
                    'service_symptom_profile_id' => $profile->id,
                    'service_symptom_id'         => $symptomId,
                    'mode'                       => ServiceSymptomProfileSymptomMode::Include->value,
                    'sort_order'                 => ($index + 1) * 10,
                ]);
            }
        });
    }
}
