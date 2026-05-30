<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Commonize;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\EquipmentAiProfile;
use App\Models\MaintenanceManagement\EquipmentAiSpecification;
use App\Models\ProductManagement\ProductCategory;
use App\Services\OpenAIService;
use Illuminate\Http\Request;

class AnalyzeController extends Controller
{
    /**
     * Gather all unique spec keys/labels for a category, send to OpenAI,
     * and return a review page with proposed synonym groups.
     */
    public function __invoke(Request $request, OpenAIService $openAI)
    {
        $request->validate(['category_id' => 'required|exists:product_categories,id']);

        $categoryId = (int) $request->input('category_id');
        $category   = ProductCategory::findOrFail($categoryId);

        // ── Gather all unique (spec_key, spec_label) pairs in this category ─
        $specs = EquipmentAiSpecification::whereHas(
                'profile', fn ($q) => $q->where('category_id', $categoryId)
            )
            ->select('spec_key', 'spec_label')
            ->distinct()
            ->orderBy('spec_key')
            ->get();

        $profileCount = EquipmentAiProfile::where('category_id', $categoryId)
            ->whereHas('specifications')
            ->count();

        if ($specs->count() < 2) {
            return redirect()
                ->route('admin.maintenance-management.equipment-ai.index', ['category_id' => $categoryId])
                ->with('error', 'Not enough specifications to compare. Run AI research on some profiles first.');
        }

        // ── Build prompt ───────────────────────────────────────────────────
        $specList = $specs->map(fn ($s) => $s->spec_key . ' | ' . $s->spec_label)->join("\n");

        $messages = [
            [
                'role'    => 'system',
                'content' => 'You are an equipment data analyst specializing in standardizing specification terminology across different manufacturers.',
            ],
            [
                'role'    => 'user',
                'content' => <<<PROMPT
I am standardizing equipment specifications for the category: "{$category->title}".

Below is the complete list of all unique specification keys and labels currently used across all equipment profiles in this category. Each line is in the format: spec_key | Display Label

{$specList}

TASK: Identify specifications that represent the same physical attribute but use different naming (synonyms, abbreviations, alternate phrasings). Examples: "hp" and "horsepower", "outrigger_type" and "outriggers", "rated_capacity" and "load_capacity".

For each synonym group found:
- Propose a canonical spec_key (snake_case, concise, descriptive — prefer the most descriptive existing key if suitable)
- Propose a canonical display label (Title Case, human-readable)
- List every member spec_key from the input list that belongs to this group
- Provide a one-sentence reason explaining why these were grouped

Rules:
1. ONLY include groups with 2 or more members from the input list above
2. Do NOT invent new specs not in the input list
3. Do NOT include specs with no synonyms in the list
4. Return ONLY valid JSON — no markdown, no explanation, no code fences

Return a JSON array of objects in this exact format:
[
  {
    "canonical_key": "engine_horsepower",
    "canonical_label": "Engine Horsepower",
    "reason": "HP and horsepower are two common ways to express the same engine output metric.",
    "members": [
      {"spec_key": "hp", "spec_label": "HP"},
      {"spec_key": "horsepower", "spec_label": "Horsepower"}
    ]
  }
]

If no synonym groups are found, return an empty array: []
PROMPT
            ],
        ];

        // ── Call OpenAI ────────────────────────────────────────────────────
        try {
            $response = $openAI->chatCompletion($messages, ['max_tokens' => 3000]);
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('admin.maintenance-management.equipment-ai.index', ['category_id' => $categoryId])
                ->with('error', 'OpenAI request failed: ' . $e->getMessage());
        }

        $rawContent = $response['choices'][0]['message']['content'] ?? '';

        // Strip markdown fences if the model wraps its output
        $clean  = trim(preg_replace('/```(?:json)?\s*|\s*```/', '', $rawContent));
        $groups = json_decode($clean, true);

        if (!is_array($groups)) {
            return redirect()
                ->route('admin.maintenance-management.equipment-ai.index', ['category_id' => $categoryId])
                ->with('error', 'Could not parse the AI response. Please try again.');
        }

        return view('admin.maintenance_management.equipment_ai.commonize', [
            'category'     => $category,
            'groups'       => $groups,
            'specCount'    => $specs->count(),
            'profileCount' => $profileCount,
        ]);
    }
}
