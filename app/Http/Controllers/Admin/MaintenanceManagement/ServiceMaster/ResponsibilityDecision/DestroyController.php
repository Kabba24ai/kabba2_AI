<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\ResponsibilityDecision;

use App\Http\Controllers\Controller;
use App\Models\Service\ServiceResponsibilityDecision;

class DestroyController extends Controller
{
    public function __invoke($id)
    {
        $decision = ServiceResponsibilityDecision::findOrFail($id);

        // Canonical seeded concepts are permanent business identities — they may
        // be deactivated but never destroyed, so long-term reporting stays stable.
        if ($decision->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'This is a canonical responsibility decision and cannot be deleted. Deactivate it instead.',
            ], 422);
        }

        // A decision used by any ticket is never destroyed — historical tickets
        // must keep resolving it. Deactivate it instead.
        if ($decision->isInUse()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete a responsibility decision that has been used by a service ticket. Deactivate it instead.',
            ], 422);
        }

        $decision->delete();

        return response()->json([
            'success' => true,
            'message' => 'Responsibility decision deleted successfully.',
        ]);
    }
}
