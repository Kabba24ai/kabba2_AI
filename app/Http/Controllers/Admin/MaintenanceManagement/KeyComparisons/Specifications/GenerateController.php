<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\KeyComparisons\Specifications;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\EquipmentCriticalMatchingCriterion;
use App\Models\ProductManagement\ProductCategory;
use App\Services\OpenAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GenerateController extends Controller
{
    public function __invoke(Request $request, OpenAIService $openAI): JsonResponse
    {
        $categoryId = (int) $request->input('category_id', 0);
        $prompt = trim((string) $request->input('prompt', ''));

        if ($categoryId <= 0) {
            return response()->json(['success' => false, 'message' => 'category_id is required.'], 422);
        }

        if ($prompt === '') {
            return response()->json(['success' => false, 'message' => 'prompt is required.'], 422);
        }

        \Log::info('Starting GenerateController', ['category_id' => $categoryId, 'prompt' => $prompt]);
        $category = ProductCategory::find($categoryId);

        if (!$category) {
            return response()->json(['success' => false, 'message' => 'Category not found.'], 404);
        }

        // Call OpenAI with the prompt provided by the client
        try {
            \Log::info('Before OpenAI call', ['category_id' => $categoryId]);
            $response = $openAI->chatCompletion([
                [
                    'role' => 'system',
                    'content' => 'You are a heavy equipment specification expert. Return ONLY a valid JSON object keyed by equipment unique_id. No markdown, no extra text.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ]);

            \Log::info('After OpenAI call', ['response' => $response]);

            $rawText = $response['choices'][0]['message']['content'] ?? '';

            \Log::info('Raw OpenAI response', ['rawText' => $rawText]);

            $cleanJson = preg_replace('/^```(?:json)?\s*/i', '', trim($rawText));
            $cleanJson = preg_replace('/\s*```$/', '', $cleanJson);

            \Log::info('Cleaned JSON string', ['cleanJson' => $cleanJson]);

            $aiResult = json_decode($cleanJson, true);

            \Log::info('Decoded AI result', ['aiResult' => $aiResult]);

            if (!is_array($aiResult)) {
                \Log::error('AI returned non-JSON output', ['rawText' => $rawText, 'cleanJson' => $cleanJson]);
                return response()->json(['success' => false, 'message' => 'AI returned non-JSON output. Please try again.'], 422);
            }
        } catch (\Throwable $e) {
            \Log::error('Exception during OpenAI call', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        // Extract unique spec keys/labels/units across all equipment responses
        // and save them as category-level criteria only (no per-equipment specs)
        $specsCreated = 0;
        $seenKeys = [];

        \Log::info('Before adding AI result to DB', ['aiResult' => $aiResult]);
        foreach ($aiResult as $uniqueId => $equipmentSpecs) {
            if (!is_array($equipmentSpecs)) {
                continue;
            }

            foreach ($equipmentSpecs as $specData) {
                if (!is_array($specData)) {
                    continue;
                }

                $key = trim((string) ($specData['spec_key'] ?? ''));

                if ($key === '' || isset($seenKeys[$key])) {
                    continue;
                }

                $seenKeys[$key] = true;

                $label = trim((string) ($specData['spec_label'] ?? ''));
                if ($label === '') {
                    $label = ucwords(str_replace('_', ' ', $key));
                }

                $unit = trim((string) ($specData['unit_type'] ?? $specData['unit'] ?? ''));

                $existing = EquipmentCriticalMatchingCriterion::query()
                    ->where('product_category_id', $categoryId)
                    ->where('criteria_key', $key)
                    ->first();

                if ($existing) {
                    $existing->forceFill([
                        'name'        => $label,
                        'unit'        => $unit,
                        'source_type' => 'ai',
                        'is_active'   => true,
                    ])->save();
                } else {
                    EquipmentCriticalMatchingCriterion::create([
                        'product_category_id'    => $categoryId,
                        'criteria_key'           => $key,
                        'name'                   => $label,
                        'unit'                   => $unit,
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

                    $specsCreated++;
                }
            }
        }
        \Log::info('After adding AI result to DB', ['specsCreated' => $specsCreated]);

        $aiItems = EquipmentCriticalMatchingCriterion::query()
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

        return response()->json([
            'success'       => true,
            'specs_created' => $specsCreated,
            'ai_items'      => $aiItems,
            'message'       => "{$specsCreated} new specification(s) added to the category.",
        ]);
    }
}
