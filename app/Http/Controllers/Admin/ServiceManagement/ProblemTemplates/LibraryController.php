<?php

namespace App\Http\Controllers\Admin\ServiceManagement\ProblemTemplates;

use App\Http\Controllers\Controller;
use App\Models\Service\ServiceSymptom;
use App\Models\Service\ServiceSymptomCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Problem Library — the Categories + Items taxonomy behind Reported-Problem
 * templates. This is the existing symptom library (service_symptom_* ) — no
 * parallel problem_* tables. One page manages both; every mutation is JSON
 * so the page updates in place.
 */
class LibraryController extends Controller
{
    public function index()
    {
        return view('admin.service_management.problem_templates.library', [
            'categories' => ServiceSymptomCategory::withCount('symptoms')
                ->orderBy('display_order')->orderBy('name')->get(),
            'items' => ServiceSymptom::orderBy('display_order')->orderBy('name')
                ->get(['id', 'service_symptom_category_id', 'name', 'display_order', 'is_active']),
        ]);
    }

    // ── Categories ──────────────────────────────────────────────────────

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:service_symptom_categories,name'],
        ]);

        $category = ServiceSymptomCategory::create([
            'name'          => trim($validated['name']),
            'display_order' => (int) ServiceSymptomCategory::max('display_order') + 10,
            'is_active'     => true,
        ]);

        return response()->json(['success' => true, 'category' => $category]);
    }

    public function updateCategory(Request $request, ServiceSymptomCategory $category)
    {
        $validated = $request->validate([
            'name'      => ['sometimes', 'required', 'string', 'max:255', "unique:service_symptom_categories,name,{$category->id}"],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $category->update($validated);

        return response()->json(['success' => true, 'category' => $category->fresh()]);
    }

    /**
     * Deactivate, not delete — preserves history (items stay, past tickets
     * keep their plain-string snapshots). A category with active items is
     * blocked from a hard delete; deactivation is always allowed.
     */
    public function destroyCategory(ServiceSymptomCategory $category)
    {
        if ($category->symptoms()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This category has items. Deactivate it instead, or move/remove its items first.',
            ], 422);
        }

        $category->delete();

        return response()->json(['success' => true]);
    }

    public function reorderCategories(Request $request)
    {
        $validated = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['ids'] as $index => $id) {
                ServiceSymptomCategory::where('id', $id)->update(['display_order' => ($index + 1) * 10]);
            }
        });

        return response()->json(['success' => true]);
    }

    // ── Items ───────────────────────────────────────────────────────────

    public function storeItem(Request $request)
    {
        $validated = $request->validate([
            'service_symptom_category_id' => ['required', 'integer', 'exists:service_symptom_categories,id'],
            'name'                        => ['required', 'string', 'max:255', 'unique:service_symptoms,name'],
        ]);

        $item = ServiceSymptom::create([
            'service_symptom_category_id' => $validated['service_symptom_category_id'],
            'name'                        => trim($validated['name']),
            'display_order'               => (int) ServiceSymptom::where('service_symptom_category_id', $validated['service_symptom_category_id'])->max('display_order') + 10,
            'is_active'                   => true,
        ]);

        return response()->json(['success' => true, 'item' => $item]);
    }

    public function updateItem(Request $request, ServiceSymptom $item)
    {
        $validated = $request->validate([
            'name'                        => ['sometimes', 'required', 'string', 'max:255', "unique:service_symptoms,name,{$item->id}"],
            'service_symptom_category_id' => ['sometimes', 'required', 'integer', 'exists:service_symptom_categories,id'],
            'is_active'                   => ['sometimes', 'boolean'],
        ]);

        $item->update($validated);

        return response()->json(['success' => true, 'item' => $item->fresh()]);
    }

    public function destroyItem(ServiceSymptom $item)
    {
        // Removing an item from the library also removes it from any template
        // (the profile-symptom join cascades on delete). Past tickets are
        // untouched — they hold plain-string snapshots, not this FK.
        $item->delete();

        return response()->json(['success' => true]);
    }

    public function reorderItems(Request $request)
    {
        $validated = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['ids'] as $index => $id) {
                ServiceSymptom::where('id', $id)->update(['display_order' => ($index + 1) * 10]);
            }
        });

        return response()->json(['success' => true]);
    }
}
