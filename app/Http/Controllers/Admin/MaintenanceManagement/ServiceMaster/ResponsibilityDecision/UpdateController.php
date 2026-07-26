<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\ResponsibilityDecision;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\ServiceMaster\ResponsibilityDecision\UpdateRequest;
use App\Models\Service\ServiceResponsibilityDecision;

class UpdateController extends Controller
{
    public function __invoke(UpdateRequest $request, $id)
    {
        $decision = ServiceResponsibilityDecision::findOrFail($id);

        // `key` is intentionally never in the validated set — it is immutable.
        $validated = $request->validated();
        $validated['updated_by'] = auth()->id();

        $decision->update($validated);

        return response()->json([
            'success'  => true,
            'decision' => $decision->fresh(),
            'message'  => 'Responsibility decision updated successfully.',
        ]);
    }
}
