<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\Preset;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\ServiceMaster\Preset\StoreRequest;
use App\Models\MaintenanceManagement\IntervalPreset;

class StoreController extends Controller
{
    public function __invoke(StoreRequest $request)
    {
        $validated = $request->validated();

        // Sort intervals in ascending order
        $validated['intervals'] = collect($validated['intervals'])
            ->sort()
            ->values()
            ->toArray();

        $preset = IntervalPreset::create($validated);

        return response()->json([
            'success' => true,
            'preset' => $preset,
            'message' => 'Interval preset created successfully',
        ]);
    }
}
