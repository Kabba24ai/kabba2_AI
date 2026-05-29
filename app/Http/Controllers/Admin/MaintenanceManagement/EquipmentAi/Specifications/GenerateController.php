<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentAi\Specifications;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\EquipmentAiProfile;
use App\Models\MaintenanceManagement\EquipmentAiSpecification;
use App\Services\OpenAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GenerateController extends Controller
{
    /**
     * Call OpenAI to research general specifications for a single AI profile
     * (make + model combination) and persist the results.
     *
     * Only specs with a verifiable value are returned by the prompt —
     * we skip anything null or empty so the table stays clean.
     *
     * AI-generated rows use source = 'ai_openai' and will NOT overwrite
     * specs that were manually entered (source = 'manual') unless the
     * admin re-runs generation and the updateOrCreate replaces them.
     */
    public function __invoke(Request $request, string $uniqueId, OpenAIService $openAI): JsonResponse
    {
        $profile = EquipmentAiProfile::with('category')
            ->where('unique_id', $uniqueId)
            ->firstOrFail();

        $make     = trim($profile->make  ?? '');
        $model    = trim($profile->model ?? '');
        $category = trim(optional($profile->category)->title ?? '');

        // ── Build the prompt ─────────────────────────────────────────────────
        $prompt = <<<PROMPT
You are a technical equipment specification expert for rental equipment.

Research the manufacturer-published general specifications for the following rental equipment.
These specs will be used to compare and substitute equipment units (e.g. substituting a 56 ft boom lift for a 40 ft boom lift).

Equipment Make:     {$make}
Equipment Model:    {$model}
Equipment Category: {$category}

Return ONLY a valid JSON array with no markdown, no preamble, and no trailing text.
Each element must use this exact structure:

[
  {
    "spec_key": "platform_height_ft",
    "spec_label": "Platform Height",
    "spec_value": "50",
    "spec_unit": "ft",
    "confidence_score": 0.95
  }
]

Rules:
1. Use US-based units only (ft, in, lbs, hp, mph, gal, psi, kW, etc.).
2. spec_value must contain only the numeric value — never include the unit inside the value field.
3. spec_key must be lowercase snake_case (e.g. platform_height_ft, lift_capacity_lbs, engine_hp).
4. Return 15–35 specifications covering at minimum:
   - Working height, platform height, horizontal reach
   - Lift capacity / rated load at full height
   - Machine weight, overall dimensions (length, width, height stowed/raised)
   - Engine horsepower, fuel type, power source (diesel / electric / dual)
   - Max drive speed (elevated & lowered), gradeability
   - Tire type, drive type (2WD / 4WD)
   - Platform size (length × width)
   - Turntable tail swing
   - Any rough-terrain or outrigger requirements
5. confidence_score must be 0.0–1.0:
   - 0.90–1.00 → confirmed from manufacturer spec sheet
   - 0.70–0.89 → dealer listing or secondary source
   - Below 0.70 → estimated or inferred — prefer to omit these
6. Omit any spec you cannot verify — do NOT return rows with a null or empty spec_value.
7. Return ONLY the JSON array. No extra text.
PROMPT;

        // ── Call OpenAI ──────────────────────────────────────────────────────
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

            // Strip markdown code fences if the model wraps the output
            $cleanJson = preg_replace('/^```(?:json)?\s*/i', '', trim($rawText));
            $cleanJson = preg_replace('/\s*```$/m', '', $cleanJson);

            $aiSpecs = json_decode($cleanJson, true);

            if (!is_array($aiSpecs) || empty($aiSpecs)) {
                return response()->json([
                    'success' => false,
                    'message' => 'AI returned an unexpected response. Please try again.',
                ], 422);
            }
        } catch (\Throwable $e) {
            $profile->update(['ai_status' => 'failed']);

            return response()->json([
                'success' => false,
                'message' => 'AI call failed: ' . $e->getMessage(),
            ], 500);
        }

        // ── Persist specs ────────────────────────────────────────────────────
        $saved   = 0;
        $skipped = 0;

        foreach ($aiSpecs as $specData) {
            if (!is_array($specData)) {
                $skipped++;
                continue;
            }

            // Sanitize the key: lowercase, underscores only
            $key = trim(strtolower((string) ($specData['spec_key'] ?? '')));
            $key = preg_replace('/[^a-z0-9_]/', '_', $key);
            $key = trim($key, '_');

            if ($key === '') {
                $skipped++;
                continue;
            }

            $value = isset($specData['spec_value']) && $specData['spec_value'] !== null && $specData['spec_value'] !== ''
                ? (string) $specData['spec_value']
                : null;

            // Skip rows with no value — keeps the table clean
            if ($value === null) {
                $skipped++;
                continue;
            }

            $label = trim((string) ($specData['spec_label'] ?? ''));
            if ($label === '') {
                $label = ucwords(str_replace('_', ' ', $key));
            }

            $unit = isset($specData['spec_unit']) && $specData['spec_unit'] !== ''
                ? trim((string) $specData['spec_unit'])
                : null;

            $confidence = isset($specData['confidence_score'])
                ? min(1.0, max(0.0, (float) $specData['confidence_score']))
                : null;

            EquipmentAiSpecification::updateOrCreate(
                [
                    'equipment_ai_profile_id' => $profile->id,
                    'spec_key'                => $key,
                ],
                [
                    'spec_label'       => $label,
                    'spec_value'       => $value,
                    'spec_unit'        => $unit,
                    'confidence_score' => $confidence,
                    'source'           => 'ai_openai',
                ]
            );

            $saved++;
        }

        // ── Update profile status ────────────────────────────────────────────
        $profile->update([
            'ai_status'         => 'completed',
            'last_ai_update_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'saved'   => $saved,
            'skipped' => $skipped,
            'message' => $saved . ' specification(s) generated and saved.',
        ]);
    }
}
