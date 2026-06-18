<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\EquipmentAiProfile;
use App\Models\MaintenanceManagement\EquipmentAiSpecification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ResetCategoryController extends Controller
{
    /**
     * Reset all AI-generated specifications for every profile in a category
     * so the "Research and Create Specs for All" button becomes available again.
     *
     * Deletes rows with source = 'ai_openai' or 'placeholder'.
     * Manual entries (source = 'manual') are preserved.
     * Resets ai_status to 'pending' on every profile in the category.
     *
     * POST /equipment-ai/reset-category
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $request->validate([
            'category_id' => 'required|exists:product_categories,id',
        ]);

        $categoryId = (int) $request->input('category_id');

        $profileIds = EquipmentAiProfile::where('category_id', $categoryId)->pluck('id');

        // Remove AI-generated and placeholder specs — keep manual entries
        $deleted = EquipmentAiSpecification::whereIn('equipment_ai_profile_id', $profileIds)
            ->whereIn('source', ['ai_openai', 'placeholder'])
            ->delete();

        // Reset status so the bulk-run button re-enables
        EquipmentAiProfile::whereIn('id', $profileIds)->update([
            'ai_status'         => 'pending',
            'last_ai_update_at' => null,
        ]);

        return redirect()
            ->back()
            ->with('success', "Category reset: {$deleted} AI spec(s) cleared. All profiles set to pending — you can now re-run Research and Create Specs for All.");
    }
}
