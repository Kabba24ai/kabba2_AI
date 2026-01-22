<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\Preset;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\ServiceMaster\Preset\UpdateRequest;
use App\Models\MaintenanceManagement\IntervalPreset;

class UpdateController extends Controller
{
    public function __invoke(UpdateRequest $request, $id)
    {
        $preset = IntervalPreset::findOrFail($id);

        $validated = $request->validated();

        // Sort intervals in ascending order
        $validated['intervals'] = collect($validated['intervals'])
            ->sort()
            ->values()
            ->toArray();

        $preset->update($validated);

        return response()->json([
            'success' => true,
            'preset' => $preset->fresh(),
            'message' => 'Interval preset updated successfully',
        ]);
    }
}
