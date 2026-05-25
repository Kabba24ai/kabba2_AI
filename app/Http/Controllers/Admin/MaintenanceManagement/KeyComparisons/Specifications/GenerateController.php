<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\KeyComparisons\Specifications;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\MaintenanceManagement\EquipmentCriticalMatchingCriterion;
use App\Models\MaintenanceManagement\EquipmentSpecification;
use App\Models\ProductManagement\ProductCategory;
use App\Services\OpenAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class GenerateController extends Controller
{
    public function __invoke(Request $request, OpenAIService $openAI): JsonResponse
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
            ->unique(fn (Equipment $equipment) => strtolower(trim((string) $equipment->brand) . '|' . trim((string) $equipment->model)))
            ->values();



        if ($equipmentList->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No equipment found in this category.'], 422);
        }

        // Load category-level criteria (defines which spec keys to collect)
        $criteriaRows = EquipmentCriticalMatchingCriterion::query()
            ->where('product_category_id', $categoryId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $requestedSpecs = $this->buildRequestedSpecMap($criteriaRows);
        $specKeysList = implode(', ', array_keys($requestedSpecs));
        $requestedSpecLines = $this->buildPromptSpecLines($requestedSpecs);

        // Build the equipment list section of the prompt
        $equipmentLines = $equipmentList->map(function (Equipment $e) {
            return sprintf(
                '- ID: %s | Equipment Make & Model: %s %s',
                $e->unique_id,
                $e->brand ?? 'N/A',
                $e->model ?? 'N/A'
            );
        })->implode("\n");

        $equipmentCount = $equipmentList->count();

        $userMessage = <<<PROMPT
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
    { "spec_key": "...", "spec_label": "...", "value_type": "...", "unit_type": "...", "value": "...", "confidence_score": 0.0, "notes": "..." },
    ...
  ],
  ...
}

Return ONLY valid JSON with no markdown, no explanations, no extra text.
PROMPT;

        try {
            $response = $openAI->chatCompletion([
                ['role' => 'system', 'content' => 'You are a heavy equipment specification expert. Return ONLY a valid JSON object keyed by equipment unique_id. No markdown, no extra text.'],
                ['role' => 'user',   'content' => $userMessage],
            ]);

            $rawText = $response['choices'][0]['message']['content'] ?? '';

            $cleanJson = preg_replace('/^```(?:json)?\s*/i', '', trim($rawText));
            $cleanJson = preg_replace('/\s*```$/', '', $cleanJson);

            $aiResult = json_decode($cleanJson, true);

            if (!is_array($aiResult)) {
                return response()->json(['success' => false, 'message' => 'AI returned non-JSON output. Please try again.'], 422);
            }
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        $now = now();
        $processedCount = 0;
        $specsCreated = 0;

        foreach ($equipmentList as $equipment) {
            $uniqueId = (string) $equipment->unique_id;
            $equipmentSpecs = $aiResult[$uniqueId] ?? null;

            if (!is_array($equipmentSpecs)) {
                continue;
            }

            $normalizedSpecs = $this->normalizeAiSpecs($equipmentSpecs, $requestedSpecs);
            $lookupPayload = [
                'brand' => (string) ($equipment->brand ?? ''),
                'model' => (string) ($equipment->model ?? ''),
            ];

            foreach ($normalizedSpecs as $specData) {
                $key = $specData['spec_key'] ?? null;

                if (!$key || !isset($requestedSpecs[$key])) {
                    continue;
                }

                // Do not overwrite approved specs
                $existing = EquipmentSpecification::where('equipment_id', $equipment->id)
                    ->where('spec_key', $key)
                    ->first();

                if ($existing && $existing->is_approved) {
                    continue;
                }

                EquipmentSpecification::updateOrCreate(
                    ['equipment_id' => $equipment->id, 'spec_key' => $key],
                    [
                        'spec_label'         => $requestedSpecs[$key],
                        'value'              => isset($specData['value']) ? (string) $specData['value'] : null,
                        'unit'               => isset($specData['unit_type']) ? (string) $specData['unit_type'] : (isset($specData['unit']) ? (string) $specData['unit'] : null),
                        'value_type'         => isset($specData['value_type']) ? (string) $specData['value_type'] : null,
                        'source_url'         => isset($specData['source_url']) ? (string) $specData['source_url'] : null,
                        'confidence_score'   => isset($specData['confidence_score']) ? (float) $specData['confidence_score'] : (isset($specData['source_confidence']) ? (float) $specData['source_confidence'] : null),
                        'notes'              => isset($specData['notes']) ? (string) $specData['notes'] : null,
                        'last_verified_at'   => $now,
                        'ai_lookup_payload'  => $lookupPayload,
                        'is_manual_override' => false,
                    ]
                );

                $specsCreated++;
            }

            // Sync category catalog specs from this equipment's saved specs
            $savedSpecs = $equipment->specifications()->orderBy('spec_key')->get();
            $this->syncCategoryCatalogSpecs($equipment, $savedSpecs, $categoryId);

            $processedCount++;
        }

        // Return updated AI specs for the category so the UI can refresh
        $updatedAiSpecs = $this->getAiSpecs($categoryId);

        return response()->json([
            'success'         => true,
            'processed'       => $processedCount,
            'specs_created'   => $specsCreated,
            'equipment_total' => $equipmentCount,
            'ai_items'        => $updatedAiSpecs,
            'message'         => "Processed {$processedCount} of {$equipmentCount} equipment. {$specsCreated} spec entries created or updated.",
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function buildRequestedSpecMap(Collection $criteriaRows): array
    {
        $requestedSpecs = [];

        foreach ($criteriaRows as $row) {
            $key   = trim((string) $row->criteria_key);
            $label = trim((string) $row->name);

            if ($key === '') {
                continue;
            }

            $requestedSpecs[$key] = $label !== ''
                ? $label
                : ucwords(str_replace('_', ' ', $key));
        }

        return $requestedSpecs;
    }

    private function buildPromptSpecLines(array $specs): string
    {
        if ($specs === []) {
            return '- None';
        }

        return collect($specs)
            ->map(fn (string $label, string $key) => "- {$key}: {$label}")
            ->implode("\n");
    }

    private function normalizeAiSpecs(array $aiSpecs, array $requestedSpecs): array
    {
        $normalized = [];

        foreach ($aiSpecs as $specData) {
            if (!is_array($specData)) {
                continue;
            }

            $key = trim((string) ($specData['spec_key'] ?? ''));

            if ($key === '' || !isset($requestedSpecs[$key])) {
                continue;
            }

            $specData['spec_key']   = $key;
            $specData['spec_label'] = $requestedSpecs[$key];
            $normalized[$key]       = $specData;
        }

        foreach ($requestedSpecs as $key => $label) {
            if (isset($normalized[$key])) {
                continue;
            }

            $normalized[$key] = [
                'spec_key'         => $key,
                'spec_label'       => $label,
                'value_type'       => null,
                'unit_type'        => null,
                'value'            => null,
                'confidence_score' => 0,
                'notes'            => 'Spec was not returned by AI for this equipment.',
            ];
        }

        return array_values($normalized);
    }

    private function syncCategoryCatalogSpecs(Equipment $equipment, Collection $specs, int $categoryId): void
    {
        foreach ($specs as $spec) {
            $criteriaKey = trim((string) $spec->spec_key);

            if ($criteriaKey === '') {
                continue;
            }

            $existing = EquipmentCriticalMatchingCriterion::query()
                ->where('product_category_id', $categoryId)
                ->where('criteria_key', $criteriaKey)
                ->first();

            $payload = [
                'name'        => (string) ($spec->spec_label ?? $criteriaKey),
                'unit'        => (string) ($spec->unit ?? ''),
                'source_type' => 'ai',
                'is_active'   => true,
            ];

            if ($existing) {
                $existing->forceFill($payload)->save();
                continue;
            }

            EquipmentCriticalMatchingCriterion::create([
                'product_category_id'    => $categoryId,
                'criteria_key'           => $criteriaKey,
                'name'                   => $payload['name'],
                'unit'                   => $payload['unit'],
                'source_type'            => 'ai',
                'default_weight'         => 50,
                'upgrade_exceeds_value'  => true,
                'caution_if_change_value'=> false,
                'upgrade_is_below_value' => false,
                'caution_if_below_value' => false,
                'sort_order'             => 0,
                'is_active'              => true,
                'is_key_criteria'        => false,
            ]);
        }
    }

    private function getAiSpecs(int $categoryId): array
    {
        return EquipmentCriticalMatchingCriterion::query()
            ->where('product_category_id', $categoryId)
            ->where('is_active', true)
            ->where('source_type', 'ai')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn ($item) => [
                'id'             => (int) $item->id,
                'spec_key'       => (string) ($item->criteria_key ?? ''),
                'spec_label'     => (string) ($item->name ?? ''),
                'unit'           => (string) ($item->unit ?? ''),
                'is_key_criteria'=> (bool) $item->is_key_criteria,
                'source_type'    => (string) ($item->source_type ?? 'ai'),
            ])
            ->values()
            ->all();
    }
}
