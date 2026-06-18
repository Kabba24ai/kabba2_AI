<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Commonize\CustomSpec;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\EquipmentAiCustomSpec;
use App\Models\MaintenanceManagement\EquipmentAiCustomSpecValue;
use App\Models\MaintenanceManagement\EquipmentAiProfile;
use App\Services\OpenAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResearchController extends Controller
{
    /**
     * Ask OpenAI to determine the custom spec value for every profile in the category.
     * Existing manual/confirmed values are preserved.
     */
    public function __invoke(Request $request, int $id, OpenAIService $openAI): JsonResponse
    {
        $spec = EquipmentAiCustomSpec::with('category')->findOrFail($id);

        $categoryTitle  = $spec->category?->title ?? 'Unknown Category';
        $allowedOptions = $spec->allowed_values ?? [];
        $allowedStr     = !empty($allowedOptions)
            ? 'Allowed values: ' . implode(', ', $allowedOptions) . '. Return exactly one of these.'
            : 'Return the most accurate value you can determine.';

        // Load profiles with their existing specs for context
        $profiles = EquipmentAiProfile::where('category_id', $spec->category_id)
            ->whereHas('specifications')
            ->with(['specifications' => fn ($q) => $q->select('equipment_ai_profile_id', 'spec_label', 'spec_value', 'spec_unit')])
            ->get(['id', 'make', 'model']);

        if ($profiles->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No profiles found.'], 422);
        }

        // Build the profile list section of the prompt
        $profileLines = $profiles->map(function ($profile) {
            $specSummary = $profile->specifications
                ->take(20)
                ->map(fn ($s) => "  - {$s->spec_label}: {$s->spec_value}" . ($s->spec_unit ? " {$s->spec_unit}" : ''))
                ->implode("\n");

            return "Profile ID {$profile->id}: {$profile->make} {$profile->model}\n{$specSummary}";
        })->implode("\n\n");

        $prompt = <<<PROMPT
You are a technical equipment specification researcher for rental equipment.

Category: {$categoryTitle}

Specification to research: "{$spec->spec_label}"
Description / Research Instruction: {$spec->description}
Value Type: {$spec->value_type}
{$allowedStr}

For each equipment profile listed below, determine the value of this specification.
Use the provided existing specifications as context. Also use your knowledge of manufacturer-published data.
If you cannot confidently determine the value, return "Unknown".
Do NOT guess. Only return confident values.

Equipment Profiles:
{$profileLines}

Return ONLY a valid JSON array — no markdown, no preamble:
[
  {
    "profile_id": 1,
    "value": "Yes",
    "confidence": 0.90,
    "reason": "One-sentence explanation of why this value was determined.",
    "source_reference": "Reference to manufacturer spec or documentation if known, otherwise null."
  }
]

Rules:
- Return exactly one object per profile, in the same order as listed.
- confidence must be 0.0–1.0. Use 0.0 and value "Unknown" if unsure.
- reason must be one concise sentence.
- source_reference is the manufacturer doc, spec sheet name, or URL if known; otherwise null.
PROMPT;

        try {
            $response = $openAI->chatCompletion([
                [
                    'role'    => 'system',
                    'content' => 'You are a heavy equipment specification expert. Return ONLY valid JSON arrays with no markdown or extra text.',
                ],
                [
                    'role'    => 'user',
                    'content' => $prompt,
                ],
            ]);

            $rawText   = $response['choices'][0]['message']['content'] ?? '';
            $cleanJson = preg_replace('/^```(?:json)?\s*/i', '', trim($rawText));
            $cleanJson = preg_replace('/\s*```$/m', '', $cleanJson);
            $aiResults = json_decode($cleanJson, true);

            if (!is_array($aiResults) || empty($aiResults)) {
                return response()->json([
                    'success' => false,
                    'message' => 'AI returned an unexpected response. Please try again.',
                ], 422);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'AI call failed: ' . $e->getMessage(),
            ], 500);
        }

        // Persist results — skip rows that were manually confirmed by the user
        $updatedValues = [];

        foreach ($aiResults as $result) {
            if (!is_array($result) || !isset($result['profile_id'])) {
                continue;
            }

            $profileId  = (int) $result['profile_id'];
            $value      = isset($result['value']) && $result['value'] !== '' ? (string) $result['value'] : null;
            $confidence = isset($result['confidence']) ? min(1.0, max(0.0, (float) $result['confidence'])) : null;

            $valueRow = EquipmentAiCustomSpecValue::firstOrNew([
                'custom_spec_id'          => $spec->id,
                'equipment_ai_profile_id' => $profileId,
            ]);

            // Preserve manually confirmed values
            if ($valueRow->exists && $valueRow->confirmed_by_user) {
                $updatedValues[$profileId] = [
                    'id'         => $valueRow->id,
                    'value'      => $valueRow->spec_value,
                    'source'     => $valueRow->value_source,
                    'confidence' => $valueRow->confidence_score,
                    'confirmed'  => true,
                    'reason'     => $valueRow->ai_reason,
                ];
                continue;
            }

            $valueRow->fill([
                'spec_value'        => $value,
                'confidence_score'  => $confidence,
                'value_source'      => 'ai',
                'source_reference'  => $result['source_reference'] ?? null,
                'ai_reason'         => $result['reason'] ?? null,
                'confirmed_by_user' => false,
            ])->save();

            $updatedValues[$profileId] = [
                'id'         => $valueRow->id,
                'value'      => $valueRow->spec_value,
                'source'     => $valueRow->value_source,
                'confidence' => $valueRow->confidence_score,
                'confirmed'  => false,
                'reason'     => $valueRow->ai_reason,
            ];
        }

        return response()->json([
            'success' => true,
            'values'  => $updatedValues,
            'message' => count($updatedValues) . ' profile(s) researched.',
        ]);
    }
}
