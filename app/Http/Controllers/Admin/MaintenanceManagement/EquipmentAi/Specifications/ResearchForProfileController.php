<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Specifications;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\EquipmentAiProfile;
use App\Models\MaintenanceManagement\EquipmentAiSpecification;
use App\Services\OpenAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResearchForProfileController extends Controller
{
    /**
     * Research a single spec key for a specific profile and persist the result.
     * Called from the matrix page gray-cell "Research" button — no existing spec
     * row is required; this creates one if needed.
     *
     * POST /specifications/research-for-profile
     * Body: { profile_id, spec_key, spec_label }
     */
    public function __invoke(Request $request, OpenAIService $openAI): JsonResponse
    {
        $data = $request->validate([
            'profile_id' => 'required|exists:equipment_ai_profiles,id',
            'spec_key'   => 'required|string|max:100',
            'spec_label' => 'required|string|max:255',
        ]);

        $profile  = EquipmentAiProfile::with('category')->findOrFail((int) $data['profile_id']);
        $specKey  = trim(strtolower((string) $data['spec_key']));
        $specKey  = preg_replace('/[^a-z0-9_]/', '_', $specKey);
        $specKey  = trim($specKey, '_');
        $specLabel = trim((string) $data['spec_label']);

        $make     = trim($profile->make  ?? '');
        $model    = trim($profile->model ?? '');
        $category = trim(optional($profile->category)->title ?? '');

        $prompt = <<<PROMPT
You are a technical equipment specification expert for rental equipment.

Research the single manufacturer specification listed below for the following piece of equipment.

Equipment Make:     {$make}
Equipment Model:    {$model}
Equipment Category: {$category}
Specification:      {$specLabel} (key: {$specKey})

Return ONLY a valid JSON object — no markdown, no preamble, no trailing text:
{
  "spec_value": "50",
  "spec_unit": "ft",
  "confidence_score": 0.95,
  "source_notes": "Brief source description"
}

Rules:
1. spec_value must be only the numeric or text value — never include the unit inside the value field.
2. spec_unit should use US-based units (ft, in, lbs, hp, mph, gal, psi, etc.).
3. confidence_score is 0.0–1.0: 0.90–1.00 = manufacturer spec sheet; 0.70–0.89 = dealer/secondary; below 0.70 = omit and return null for spec_value.
4. If you cannot verify the value with confidence >= 0.70, return null for spec_value.
5. Return ONLY the JSON object.
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

            if (!is_array($result)) {
                return response()->json(['success' => false, 'message' => 'AI returned an unexpected response.'], 422);
            }
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'AI call failed: ' . $e->getMessage()], 500);
        }

        $specValue  = isset($result['spec_value']) && $result['spec_value'] !== null && $result['spec_value'] !== ''
            ? (string) $result['spec_value'] : null;
        $specUnit   = isset($result['spec_unit']) && $result['spec_unit'] !== '' ? trim((string) $result['spec_unit']) : null;
        $confidence = isset($result['confidence_score']) ? min(1.0, max(0.0, (float) $result['confidence_score'])) : null;

        $spec = EquipmentAiSpecification::updateOrCreate(
            [
                'equipment_ai_profile_id' => $profile->id,
                'spec_key'                => $specKey,
            ],
            [
                'spec_label'       => $specLabel,
                'spec_value'       => $specValue,
                'spec_unit'        => $specUnit,
                'confidence_score' => $confidence,
                'source'           => $specValue !== null ? 'ai_openai' : 'placeholder',
            ]
        );

        return response()->json([
            'success'    => true,
            'spec_value' => $specValue,
            'spec_unit'  => $specUnit,
            'confidence' => $confidence,
            'found'      => $specValue !== null,
            'message'    => $specValue !== null
                ? "Found: {$specValue}" . ($specUnit ? " {$specUnit}" : '')
                : 'AI could not verify this value with sufficient confidence.',
        ]);
    }
}
