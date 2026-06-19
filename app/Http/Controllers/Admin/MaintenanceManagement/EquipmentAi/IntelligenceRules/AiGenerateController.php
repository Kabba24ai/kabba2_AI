<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\IntelligenceRules;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\EquipmentAiProfile;
use App\Models\ProductManagement\ProductCategory;
use App\Services\EquipmentAi\IntelligenceRuleGeneratorService;
use Illuminate\Http\Request;

class AiGenerateController extends Controller
{
    public function __construct(private IntelligenceRuleGeneratorService $generator) {}

    public function __invoke(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:product_categories,id',
            'profile_id'  => 'nullable|exists:equipment_ai_profiles,id',
        ]);

        $categoryId = (int) $request->input('category_id');
        $profileId  = $request->filled('profile_id') ? (int) $request->input('profile_id') : null;

        try {
            if ($profileId) {
                $profile = EquipmentAiProfile::findOrFail($profileId);
                $count   = $this->generator->generateForProfile($profile, auth()->id());
                $context = "{$profile->make} {$profile->model}";
            } else {
                $category = ProductCategory::findOrFail($categoryId);
                $count    = $this->generator->generateForCategory($category, auth()->id());
                $context  = $category->title;
            }

            return response()->json([
                'success' => true,
                'count'   => $count,
                'message' => "{$count} intelligence " . str($count)->plural('rule', $count) . " generated for {$context}. Review and approve each one before they are used.",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'AI generation failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
