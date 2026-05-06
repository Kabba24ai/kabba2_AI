<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipment\Specification;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\MaintenanceManagement\EquipmentSpecification;
use App\Services\OpenAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GenerateController extends Controller
{
    private const SPEC_KEYS = [
        'engine_horsepower'        => 'Engine Horsepower',
        'operating_weight'         => 'Operating Weight',
        'rated_operating_capacity' => 'Rated Operating Capacity',
        'tipping_load'             => 'Tipping Load',
        'hydraulic_flow'           => 'Hydraulic Flow',
        'width'                    => 'Width',
        'height'                   => 'Height',
        'length'                   => 'Length',
        'fuel_type'                => 'Fuel Type',
        'lift_type'                => 'Lift Type',
        'ground_pressure'          => 'Ground Pressure',
        'travel_speed'             => 'Travel Speed',
    ];

    public function __invoke(Request $request, string $uniqueId, OpenAIService $openAI): JsonResponse
    {
        $equipment = Equipment::with('productCategory')->where('unique_id', $uniqueId)->firstOrFail();

        $forceKey = $request->input('force_key'); // optional – retry a single spec

        $categoryTitle = (string) (optional($equipment->productCategory)->title ?? '');
        $requestLookupPayload = (array) $request->input('lookup_payload', []);

        $fromRequest = static function (string $key) use ($requestLookupPayload) {
            $value = $requestLookupPayload[$key] ?? null;

            if (is_string($value)) {
                $value = trim($value);
            }

            return $value;
        };

        $lookupPayload = [
            'brand'          => $fromRequest('brand') ?: (string) ($equipment->brand ?? ''),
            'model'          => $fromRequest('model') ?: (string) ($equipment->model ?? ''),
            'model_year'     => $fromRequest('model_year') ?: $equipment->model_year,
            'category'       => $fromRequest('category') ?: $categoryTitle,
            'equipment_name' => $fromRequest('equipment_name') ?: (string) ($equipment->equipment_name ?? ''),
            'equipment_id'   => $fromRequest('equipment_id') ?: (string) ($equipment->equipment_id ?? ''),
            'serial_number'  => $fromRequest('serial_number') ?: ($equipment->serial_number ?? null),
            'vin'            => $fromRequest('vin') ?: ($equipment->vehicle_identification_number ?? null),
        ];

        $specKeysList = implode(', ', array_keys(self::SPEC_KEYS));
        $specKeyCount = count(self::SPEC_KEYS);

        $userMessage = <<<PROMPT
Return technical specifications for the following equipment.

Brand: {$lookupPayload['brand']}
Model: {$lookupPayload['model']}
Model Year: {$lookupPayload['model_year']}
Category: {$lookupPayload['category']}
Equipment Name: {$lookupPayload['equipment_name']}
Equipment ID: {$lookupPayload['equipment_id']}
Serial Number: {$lookupPayload['serial_number']}
VIN: {$lookupPayload['vin']}

Return ONLY a valid JSON array. Each element must include exactly these fields:
- spec_key: one of [{$specKeysList}]
- spec_label: human-readable label for the spec
- value: string value or null if unknown
- unit: unit of measurement string or null
- confidence_score: float 0.0–1.0
- source_url: most authoritative URL for this spec, or null

Include all {$specKeyCount} spec_keys. Do not include markdown, explanation, or extra keys.
PROMPT;

        try {
            $response = $openAI->chatCompletion([
                ['role' => 'system', 'content' => 'You are a heavy equipment specification expert. Return ONLY valid JSON arrays with no markdown or extra text.'],
                ['role' => 'user',   'content' => $userMessage],
            ]);

            $rawText = $response['choices'][0]['message']['content'] ?? '';

            // Strip markdown code fences if model wraps output
            $cleanJson = preg_replace('/^```(?:json)?\s*/i', '', trim($rawText));
            $cleanJson = preg_replace('/\s*```$/', '', $cleanJson);

            $aiSpecs = json_decode($cleanJson, true);

            if (!is_array($aiSpecs)) {
                return response()->json(['success' => false, 'message' => 'AI returned non-JSON output. Please try again.'], 422);
            }
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        $now = now();

        foreach ($aiSpecs as $specData) {
            $key = $specData['spec_key'] ?? null;

            if (!$key || !isset(self::SPEC_KEYS[$key])) {
                continue;
            }

            // Skip approved specs unless this is a targeted retry for that key
            $existing = EquipmentSpecification::where('equipment_id', $equipment->id)
                ->where('spec_key', $key)
                ->first();

            if ($existing && $existing->is_approved && $key !== $forceKey) {
                continue;
            }

            EquipmentSpecification::updateOrCreate(
                ['equipment_id' => $equipment->id, 'spec_key' => $key],
                [
                    'spec_label'        => self::SPEC_KEYS[$key],
                    'value'             => isset($specData['value']) ? (string) $specData['value'] : null,
                    'unit'              => isset($specData['unit']) ? (string) $specData['unit'] : null,
                    'source_url'        => isset($specData['source_url']) ? (string) $specData['source_url'] : null,
                    'confidence_score'  => isset($specData['confidence_score']) ? (float) $specData['confidence_score'] : null,
                    'last_verified_at'  => $now,
                    'ai_lookup_payload' => $lookupPayload,
                    'is_manual_override' => false,
                ]
            );
        }

        $specs = $equipment->specifications()->with('approvedByUser')->orderBy('spec_key')->get();

        return response()->json([
            'success' => true,
            'specs'   => $specs->map(fn ($s) => $this->formatSpec($s))->values(),
        ]);
    }

    public static function formatSpec(EquipmentSpecification $s): array
    {
        return [
            'id'               => $s->id,
            'spec_key'         => $s->spec_key,
            'spec_label'       => $s->spec_label,
            'value'            => $s->value,
            'unit'             => $s->unit,
            'source_url'       => $s->source_url,
            'confidence_score' => $s->confidence_score,
            'is_approved'      => $s->is_approved,
            'is_manual_override' => $s->is_manual_override,
            'last_verified_at' => $s->last_verified_at?->format('Y-m-d H:i'),
            'approved_by_name' => $s->approvedByUser?->name,
            'approved_at'      => $s->approved_at?->format('Y-m-d H:i'),
        ];
    }
}
