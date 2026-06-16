<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Specifications;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\EquipmentAiSpecification;
use App\Services\OpenAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeepResearchController extends Controller
{
    public function __invoke(Request $request, int $id, OpenAIService $openAI): JsonResponse
    {
        $request->validate([
            'prompt' => 'required|string|max:1000',
        ]);

        $spec    = EquipmentAiSpecification::with('profile.category')->findOrFail($id);
        $profile = $spec->profile;

        $make       = trim($profile->make  ?? '');
        $model      = trim($profile->model ?? '');
        $category   = trim(optional($profile->category)->title ?? '');
        $label      = $spec->spec_label;
        $key        = $spec->spec_key;
        $current    = $spec->spec_value ?? 'unknown';
        $unit       = $spec->spec_unit  ?? '';
        $userPrompt = trim($request->input('prompt'));

        $prompt = <<<PROMPT
You are a technical equipment specification expert for rental equipment.

Equipment Make:     {$make}
Equipment Model:    {$model}
Equipment Category: {$category}

Specification: {$label} (key: {$key})
Current Recorded Value: {$current} {$unit}

The user wants you to research and verify/correct this specific specification value.
User's research instruction: {$userPrompt}

Return ONLY a valid JSON object with this exact structure:
{
  "value": "...",
  "unit": "...",
  "confidence": 0.95,
  "notes": "Brief explanation of source and confidence"
}

Rules:
1. value must be only the numeric or text value — never include the unit inside the value field.
2. unit should use US-based units (ft, in, lbs, hp, mph, gal, psi, etc.).
3. confidence must be 0.0–1.0:
   - 0.90–1.00 → confirmed from manufacturer spec sheet
   - 0.70–0.89 → dealer listing or secondary source
   - Below 0.70 → estimated or inferred
4. notes should be 1–2 sentences explaining the source and reasoning.
5. Return ONLY the JSON object. No markdown, no preamble, no trailing text.
PROMPT;

        try {
            $response = $openAI->chatCompletion([
                [
                    'role'    => 'system',
                    'content' => 'You are a heavy equipment specification expert. Return ONLY valid JSON with no markdown or extra text.',
                ],
                [
                    'role'    => 'user',
                    'content' => $prompt,
                ],
            ]);

            $rawText   = $response['choices'][0]['message']['content'] ?? '';
            $cleanJson = preg_replace('/^```(?:json)?\s*/i', '', trim($rawText));
            $cleanJson = preg_replace('/\s*```$/m', '', $cleanJson);

            $result = json_decode($cleanJson, true);

            if (!is_array($result) || !array_key_exists('value', $result)) {
                return response()->json([
                    'success' => false,
                    'message' => 'AI returned an unexpected response. Please try again.',
                ], 422);
            }

            return response()->json([
                'success'    => true,
                'value'      => (string) ($result['value'] ?? ''),
                'unit'       => (string) ($result['unit']  ?? ''),
                'confidence' => isset($result['confidence'])
                    ? min(1.0, max(0.0, (float) $result['confidence']))
                    : null,
                'notes'      => (string) ($result['notes'] ?? ''),
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'AI call failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
