<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\ResponsibilityDecision;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\ServiceMaster\ResponsibilityDecision\StoreRequest;
use App\Models\Service\ServiceResponsibilityDecision;

class StoreController extends Controller
{
    public function __invoke(StoreRequest $request)
    {
        // The stable key is supplied EXPLICITLY by the administrator (validated
        // unique + lowercase snake_case) — a deliberate, permanent business
        // identifier, never a slug derived from the display name. Custom
        // records are is_system = false (DB default), so they remain deletable
        // while unused.
        $validated = $request->validated();
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        $decision = ServiceResponsibilityDecision::create($validated);

        return response()->json([
            'success'  => true,
            'decision' => $decision,
            'message'  => 'Responsibility decision created successfully.',
        ]);
    }
}
