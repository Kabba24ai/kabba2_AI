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
            'prompt' => 'required|string|max:5000',
        ]);

        $spec    = EquipmentAiSpecification::with('profile')->findOrFail($id);
        $profile = $spec->profile;

        $label      = $spec->spec_label;
        $current    = $spec->spec_value ?? 'unknown';
        $unit       = $spec->spec_unit  ?? '';
        $userPrompt = trim($request->input('prompt'));

        $jsonInstruction = <<<JSON
Return ONLY a valid JSON object with this exact structure — no markdown, no preamble, no trailing text:
{
  "label": "{$label}",
  "value": "...",
  "unit": "...",
  "confidence": 85,
  "source_type": "manufacturer",
  "reasoning": "Brief explanation of why this value is correct or uncertain.",
  "source_notes": "Key sources used or description of where the value was found."
}

Rules:
1. value must be only the numeric or text value — never include the unit inside the value field.
2. unit should use US-based units (ft, in, lbs, hp, mph, gal, psi, etc.).
3. confidence is an integer 0–100: 90–100 = manufacturer spec sheet; 70–89 = dealer/secondary source; below 70 = estimated or conflicting.
4. source_type must be one of: manufacturer / dealer / manual / third-party / unclear
5. reasoning: 1–3 sentences on why this value is correct or uncertain.
6. source_notes: list the key sources or describe where the value was found.
JSON;

        try {
            $response = $openAI->chatCompletion([
                [
                    'role'    => 'system',
                    'content' => 'You are verifying a single equipment specification for a rental/equipment comparison database. Return ONLY valid JSON with no markdown or extra text.',
                ],
                [
                    'role'    => 'user',
                    'content' => $userPrompt . "\n\n" . $jsonInstruction,
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

            $rawConfidence = isset($result['confidence']) ? (float) $result['confidence'] : null;
            // Normalise: accept either 0–100 integer or 0.0–1.0 float
            if ($rawConfidence !== null && $rawConfidence <= 1.0) {
                $rawConfidence = $rawConfidence * 100;
            }
            $confidence = $rawConfidence !== null ? min(100, max(0, (int) round($rawConfidence))) : null;

            return response()->json([
                'success'      => true,
                'value'        => (string) ($result['value']        ?? ''),
                'unit'         => (string) ($result['unit']         ?? ''),
                'confidence'   => $confidence,
                'source_type'  => (string) ($result['source_type']  ?? 'unclear'),
                'reasoning'    => (string) ($result['reasoning']    ?? ''),
                'source_notes' => (string) ($result['source_notes'] ?? ''),
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'AI call failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
