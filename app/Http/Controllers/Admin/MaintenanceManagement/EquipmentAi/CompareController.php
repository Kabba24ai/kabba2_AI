<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\EquipmentAiProfile;
use App\Models\MaintenanceManagement\EquipmentAiSpecification;
use App\Models\MaintenanceManagement\EquipmentCategoryComparisonKey;
use App\Services\OpenAIService;
use App\Models\ProductManagement\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompareController extends Controller
{
    /**
     * Build a side-by-side comparison of all equipment models in a category
     * using only Key Comparison specs, then ask OpenAI to analyze and rank them.
     *
     * POST /equipment-ai/compare
     * Body: { category_id, focus_profile_id? }
     */
    public function __invoke(Request $request, OpenAIService $openAI): JsonResponse
    {
        $data = $request->validate([
            'category_id'      => 'required|exists:product_categories,id',
            'focus_profile_id' => 'nullable|exists:equipment_ai_profiles,id',
        ]);

        $categoryId      = (int) $data['category_id'];
        $focusProfileId  = isset($data['focus_profile_id']) ? (int) $data['focus_profile_id'] : null;
        $category        = ProductCategory::findOrFail($categoryId);

        // Key Comparison spec keys for this category
        $keySpecKeys = EquipmentCategoryComparisonKey::where('category_id', $categoryId)
            ->pluck('spec_key')
            ->all();

        if (empty($keySpecKeys)) {
            return response()->json([
                'success' => false,
                'message' => 'No Key Comparison specs defined for this category. Mark some specs as Key in the matrix first.',
            ], 422);
        }

        // Profiles with at least one Key Comparison spec
        $profiles = EquipmentAiProfile::where('category_id', $categoryId)
            ->whereHas('specifications', fn($q) => $q->where('is_key_comparison', true))
            ->orderBy('make')->orderBy('model')
            ->get(['id', 'make', 'model']);

        if ($profiles->count() < 2) {
            return response()->json([
                'success' => false,
                'message' => 'At least 2 equipment models need Key Comparison specs before comparison is possible.',
            ], 422);
        }

        // Pull all key spec values for all profiles
        $specs = EquipmentAiSpecification::whereIn('equipment_ai_profile_id', $profiles->pluck('id'))
            ->whereIn('spec_key', $keySpecKeys)
            ->get(['equipment_ai_profile_id', 'spec_key', 'spec_label', 'spec_value', 'spec_unit'])
            ->groupBy('equipment_ai_profile_id');

        // Build the comparison table
        $tableLines = [];
        $header = 'Spec | ' . $profiles->map(fn($p) => "{$p->make} {$p->model}")->implode(' | ');
        $tableLines[] = $header;
        $tableLines[] = str_repeat('-', strlen($header));

        // Use canonical labels from category comparison keys
        $keyLabels = EquipmentCategoryComparisonKey::where('category_id', $categoryId)
            ->get(['spec_key', 'display_label'])
            ->mapWithKeys(fn($k) => [$k->spec_key => $k->display_label]);

        foreach ($keySpecKeys as $specKey) {
            $label  = $keyLabels[$specKey] ?? ucwords(str_replace('_', ' ', $specKey));
            $values = $profiles->map(function ($profile) use ($specs, $specKey) {
                $spec = $specs->get($profile->id)?->firstWhere('spec_key', $specKey);
                if (!$spec || !filled($spec->spec_value)) return '—';
                return $spec->spec_value . ($spec->spec_unit ? ' ' . $spec->spec_unit : '');
            });
            $tableLines[] = "{$label} | " . $values->implode(' | ');
        }

        $table = implode("\n", $tableLines);

        $focusLine = '';
        if ($focusProfileId) {
            $fp = $profiles->firstWhere('id', $focusProfileId);
            if ($fp) {
                $focusLine = "\n\nA customer is currently renting the {$fp->make} {$fp->model}. Pay special attention to how the other models compare as potential substitutes for this unit.";
            }
        }

        $prompt = <<<PROMPT
You are an equipment rental advisor analyzing a category of rental equipment.

Below is a specification comparison table for all equipment models in the "{$category->title}" category.
Only Key Comparison specifications are included — these are the specs that matter most for substitution decisions.

{$table}{$focusLine}

Provide a structured analysis with these sections:

1. **Executive Summary** (2–3 sentences): What are the most important differences between these models?

2. **Model Rankings** (ordered best → acceptable → limited): Rank each model for general rental use, with a one-sentence justification.

3. **Substitution Guide**: For each model pair that are reasonable substitutes, describe the trade-offs the customer should know about (capacity, reach, weight, fuel type, etc.).

4. **Red Flags**: Any spec gaps or concerning differences that could cause problems in the field?

5. **Recommendation**: If a customer asks "which model should I rent?", what is your default recommendation and why?

Be direct and specific — reference actual spec values in your analysis.
PROMPT;

        try {
            $response = $openAI->chatCompletion([
                [
                    'role'    => 'system',
                    'content' => 'You are an experienced equipment rental advisor. Give clear, practical analysis. Reference specific spec values. Format your response with markdown headers.',
                ],
                [
                    'role'    => 'user',
                    'content' => $prompt,
                ],
            ]);

            $analysis = $response['choices'][0]['message']['content'] ?? '';

            if (empty(trim($analysis))) {
                return response()->json(['success' => false, 'message' => 'AI returned an empty response.'], 422);
            }
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'AI call failed: ' . $e->getMessage()], 500);
        }

        return response()->json([
            'success'   => true,
            'analysis'  => $analysis,
            'models'    => $profiles->map(fn($p) => ['id' => $p->id, 'name' => "{$p->make} {$p->model}"])->values(),
            'key_specs' => count($keySpecKeys),
        ]);
    }
}
