<?php

namespace App\Http\Controllers\Admin\Configurations;

use App\Http\Controllers\Controller;
use App\Models\Dashboard\FuelNotePreset;
use App\Models\Dashboard\ResolutionNotePreset;
use Illuminate\Http\Request;

/**
 * Note Presets administration (Billing Engine UI refinement) — employees
 * manage the canned notes offered by the Billing Engine dropdowns (fuel
 * charge notes, resolution notes) instead of the lists living as static
 * data no one could change. Full CRUD + optional display order; the
 * dropdowns load exclusively from these managed lists.
 */
class NotePresetsController extends Controller
{
    private const TYPES = [
        'fuel' => FuelNotePreset::class,
        'resolution' => ResolutionNotePreset::class,
    ];

    public function index()
    {
        return view('admin.configurations.note_presets', [
            'fuelPresets' => FuelNotePreset::ordered()->get(),
            'resolutionPresets' => ResolutionNotePreset::ordered()->get(),
        ]);
    }

    /**
     * Live list for the shared Note Preset Manager (Billing Charge
     * Operations polish) — every preset dropdown on a page refreshes from
     * this after a manager mutation, without a page reload.
     */
    public function list(string $type)
    {
        return response()->json([
            'success' => true,
            'presets' => $this->modelFor($type)::ordered()->get(['id', 'label', 'sort_order']),
        ]);
    }

    /**
     * Persist a full display order — ids in the desired order become
     * sort_order 0..n. Ids not in the list are untouched.
     */
    public function reorder(Request $request, string $type)
    {
        $model = $this->modelFor($type);

        $validated = $request->validate([
            'ids'   => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($model, $validated) {
            foreach ($validated['ids'] as $index => $id) {
                $model::where('id', $id)->update(['sort_order' => $index]);
            }
        });

        return response()->json(['success' => true]);
    }

    public function store(Request $request, string $type)
    {
        $model = $this->modelFor($type);
        $max = $type === 'fuel' ? 255 : 100; // matches each table's column width

        $validated = $request->validate([
            'label' => ['required', 'string', "max:{$max}", "unique:{$model}"],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $preset = $model::create([
            'label' => trim($validated['label']),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return response()->json(['success' => true, 'preset' => $preset->only(['id', 'label', 'sort_order'])]);
    }

    public function update(Request $request, string $type, int $id)
    {
        $model = $this->modelFor($type);
        $preset = $model::findOrFail($id);
        $max = $type === 'fuel' ? 255 : 100;

        $validated = $request->validate([
            'label' => ['required', 'string', "max:{$max}", "unique:{$model},label,{$preset->id}"],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $preset->update([
            'label' => trim($validated['label']),
            'sort_order' => $validated['sort_order'] ?? $preset->sort_order,
        ]);

        return response()->json(['success' => true, 'preset' => $preset->only(['id', 'label', 'sort_order'])]);
    }

    public function destroy(string $type, int $id)
    {
        $this->modelFor($type)::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }

    private function modelFor(string $type): string
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        return self::TYPES[$type];
    }
}
