<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\KeyComparisons\Specifications;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\MaintenanceManagement\EquipmentCriticalMatchingCriterion;
use App\Models\ProductManagement\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PreviewController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $categoryId = (int) $request->input('category_id', 0);

        if ($categoryId <= 0) {
            return response()->json(['success' => false, 'message' => 'category_id is required.'], 422);
        }

        $category = ProductCategory::find($categoryId);

        if (!$category) {
            return response()->json(['success' => false, 'message' => 'Category not found.'], 404);
        }

        $categoryTitle = (string) $category->title;

        // Load all active equipment in this category
        $equipmentList = Equipment::query()
            ->where('product_category_id', $categoryId)
            ->whereNull('deleted_at')
            ->select(['id', 'unique_id', 'brand', 'model'])
            ->orderBy('brand')
            ->orderBy('model')
            ->get()
            ->unique(fn(Equipment $equipment) => strtolower(trim((string) $equipment->brand) . '|' . trim((string) $equipment->model)))
            ->values();

        if ($equipmentList->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No equipment found in this category.'], 422);
        }

        foreach ($equipmentList as $equipment) {
            if (trim((string) $equipment->brand) === '' || trim((string) $equipment->model) === '') {
                return response()->json(['success' => false, 'message' => 'Some equipment items are missing brand or model. Please fill in both fields before generating AI specs.'], 422);
            }
        }

        // Load category-level criteria
        $criteriaRows = EquipmentCriticalMatchingCriterion::query()
            ->where('product_category_id', $categoryId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $requestedSpecs = $this->buildRequestedSpecMap($criteriaRows);
        $specKeysList = implode(', ', array_keys($requestedSpecs));
        $requestedSpecLines = $this->buildPromptSpecLines($requestedSpecs);

        $equipmentLines = $equipmentList
            ->map(fn(Equipment $e) => sprintf('- ID: %s | Equipment Make & Model: %s %s', $e->unique_id, $e->brand ?? 'N/A', $e->model ?? 'N/A'))
            ->implode("\n");

        $equipmentCount = $equipmentList->count();

        $prompt = <<<PROMPT
You are helping build standardized equipment comparison data for the Kabba rental software platform.

Equipment Category: {$categoryTitle}
Total Equipment: {$equipmentCount}

Task:
For each piece of equipment listed below, research the manufacturer-published specifications. Return standardized specifications using US-based unit types only. Use consistent comparison verbiage across all equipment in this category.

Equipment List (use the ID field as the key in your response):
{$equipmentLines}

Spec Keys to populate for EVERY piece of equipment:
{$requestedSpecLines}

Full spec key list: [{$specKeysList}]

Rules:
1. Use only US-based units.
2. Convert metric values to US units when needed.
3. Store numeric values only in the Value field when possible.
4. Do not include unit symbols inside the Value field.
5. Use decimals where needed.
6. Use boolean true/false for yes/no values.
7. Use enum values only when the answer must be selected from a controlled list.
8. If a specification cannot be verified, return null and explain in Notes.
9. Prioritize manufacturer specifications over dealer listings.
10. Do not guess. If sources conflict, use the manufacturer value and mention the conflict in Notes.
11. Keep Standard Label wording exactly consistent across all equipment.
12. Return one JSON object per spec_key for each piece of equipment.
13. If a spec_key cannot be found for a piece of equipment, still return it with value null and explain in notes.

Output format — return ONLY a valid JSON object keyed by the equipment unique_id:
{
  "<unique_id>": [
    {
      "spec_key": "...",
      "spec_label": "...",
      "value_type": "...",
      "unit_type": "...",
      "value": "...",
      "confidence_score": 0.0,
      "notes": "..."
    }
  ]
}

Return ONLY valid JSON with no markdown, no explanations, no extra text.
PROMPT;

        return response()->json([
            'success' => true,
            'prompt' => $prompt,
            'category_title' => $categoryTitle,
            'equipment_count' => $equipmentCount,
            'equipment_list' => $equipmentList->map(fn(Equipment $e) => [
                'unique_id' => $e->unique_id,
                'brand' => $e->brand,
                'model' => $e->model,
            ])->values()->all(),
        ]);
    }

    private function buildRequestedSpecMap(Collection $criteriaRows): array
    {
        $requestedSpecs = [];

        foreach ($criteriaRows as $row) {
            $key = trim((string) $row->criteria_key);
            $label = trim((string) $row->name);

            if ($key === '') {
                continue;
            }

            $requestedSpecs[$key] = $label !== '' ? $label : ucwords(str_replace('_', ' ', $key));
        }

        return $requestedSpecs;
    }

    private function buildPromptSpecLines(array $specs): string
    {
        if ($specs === []) {
            return '- None';
        }

        return collect($specs)
            ->map(fn(string $label, string $key) => "- {$key}: {$label}")
            ->implode("\n");
    }
}
