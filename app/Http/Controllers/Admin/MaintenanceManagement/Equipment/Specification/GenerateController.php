<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipment\Specification;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\MaintenanceManagement\EquipmentCriticalMatchingCriterion;
use App\Models\MaintenanceManagement\EquipmentSpecification;
use App\Services\OpenAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;

class GenerateController extends Controller
{
    private const SPEC_KEYS = [
        'working_height'           => 'Working Height',
        'platform_height'          => 'Platform Height',
        'horizontal_reach'         => 'Horizontal Reach',
        'machine_weight'           => 'Machine Weight',
        'platform_capacity'        => 'Platform Capacity',
        'machine_width'            => 'Machine Width',
        'retracted_width'          => 'Retracted Width',
        'machine_length'           => 'Machine Length',
        'machine_height'           => 'Machine Height',
        'drive_speed'              => 'Drive Speed',
        'gradeability'             => 'Gradeability',
        'outriggers_required'      => 'Outriggers Required',
        'outrigger_type'           => 'Outrigger Type',
        'self_propelled'           => 'Self Propelled',
        'towable'                  => 'Towable',
        'drive_while_elevated'     => 'Drive While Elevated',
        'rough_terrain_capable'    => 'Rough Terrain Capable',
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

        $criteriaRows = EquipmentCriticalMatchingCriterion::query()
            ->where('product_category_id', $equipment->product_category_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $requestedSpecs = $this->buildRequestedSpecMap($criteriaRows, $forceKey);
        $prioritySpecs = $criteriaRows
            ->mapWithKeys(function (EquipmentCriticalMatchingCriterion $row) use ($requestedSpecs) {
                $key = trim((string) $row->criteria_key);

                if ($key === '' || !isset($requestedSpecs[$key])) {
                    return [];
                }

                return [$key => $requestedSpecs[$key]];
            })
            ->all();

        $specKeysList = implode(', ', array_keys($requestedSpecs));
        $requestedSpecLines = $this->buildPromptSpecLines($requestedSpecs);
        $prioritySpecLines = $this->buildPromptSpecLines($prioritySpecs);

        $userMessage = <<<PROMPT
You are helping build standardized equipment comparison data for the Kabba rental software platform.

Equipment Category: {$lookupPayload['category']}
Equipment Make & Model: {$lookupPayload['brand']} {$lookupPayload['model']}

Task:
Research the manufacturer-published specifications for the exact equipment make and model listed above. Return standardized general specifications using US-based unit types only.
Use consistent comparison verbiage across all equipment in this category.

Return the data using this schema:
Internal Key | Standard Label | Value Type | Unit Type | Value | Source Confidence | Notes

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
11. Keep Standard Label wording exactly consistent.
12. Return data in valid JSON format with fields: spec_key, spec_label, value_type, unit_type, value, confidence_score, notes.
13. Return one JSON object for every requested spec_key below.
14. If a requested spec_key cannot be found, still return it with value set to null, unit_type set to null, and explain why in notes.
15. The priority spec_keys are the most important and must be attempted first.

Include all of these spec_keys: [{$specKeysList}]

Priority spec_keys:
{$prioritySpecLines}

Requested spec_keys and exact labels:
{$requestedSpecLines}

Output format:
1. Equipment summary
2. Standardized specification table
3. JSON array
4. Source notes
5. Missing or uncertain fields

Equipment Details:
- Model Year: {$lookupPayload['model_year']}
- Equipment Name: {$lookupPayload['equipment_name']}
- Equipment ID: {$lookupPayload['equipment_id']}
- Serial Number: {$lookupPayload['serial_number']}
- VIN: {$lookupPayload['vin']}

Return ONLY valid JSON array with no markdown or extra text.
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

            $aiSpecs = $this->normalizeAiSpecs($aiSpecs, $requestedSpecs);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        $now = now();

        foreach ($aiSpecs as $specData) {
            $key = $specData['spec_key'] ?? null;

            if (!$key || !isset($requestedSpecs[$key])) {
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
                    'spec_label'        => $requestedSpecs[$key],
                    'value'             => isset($specData['value']) ? (string) $specData['value'] : null,
                    'unit'              => isset($specData['unit_type']) ? (string) $specData['unit_type'] : (isset($specData['unit']) ? (string) $specData['unit'] : null),
                    'value_type'        => isset($specData['value_type']) ? (string) $specData['value_type'] : null,
                    'source_url'        => isset($specData['source_url']) ? (string) $specData['source_url'] : null,
                    'confidence_score'  => isset($specData['confidence_score']) ? (float) $specData['confidence_score'] : (isset($specData['source_confidence']) ? (float) $specData['source_confidence'] : null),
                    'notes'             => isset($specData['notes']) ? (string) $specData['notes'] : null,
                    'last_verified_at'  => $now,
                    'ai_lookup_payload' => $lookupPayload,
                    'is_manual_override' => false,
                ]
            );
        }

        $specs = $equipment->specifications()->with('approvedByUser')->orderBy('spec_key')->get();
        $this->syncCategoryCatalogSpecs($equipment, $specs);
        $criteria = $this->syncCriticalMatchingCriteria($equipment, $specs);

        return response()->json([
            'success' => true,
            'specs'   => $specs->map(fn ($s) => $this->formatSpec($s))->values(),
            'criteria' => $criteria,
        ]);
    }

    private function buildRequestedSpecMap(Collection $criteriaRows, ?string $forceKey = null): array
    {
        $requestedSpecs = [];

        foreach ($criteriaRows as $row) {
            $key = trim((string) $row->criteria_key);
            $label = trim((string) $row->name);

            if ($key === '') {
                continue;
            }

            $requestedSpecs[$key] = $label !== ''
                ? $label
                : self::SPEC_KEYS[$key] ?? ucwords(str_replace('_', ' ', $key));
        }

        foreach (self::SPEC_KEYS as $key => $label) {
            if (!isset($requestedSpecs[$key])) {
                $requestedSpecs[$key] = $label;
            }
        }

        if ($forceKey && isset($requestedSpecs[$forceKey])) {
            return [$forceKey => $requestedSpecs[$forceKey]];
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

            $specData['spec_key'] = $key;
            $specData['spec_label'] = $requestedSpecs[$key];
            $normalized[$key] = $specData;
        }

        foreach ($requestedSpecs as $key => $label) {
            if (isset($normalized[$key])) {
                continue;
            }

            $normalized[$key] = [
                'spec_key' => $key,
                'spec_label' => $label,
                'value_type' => null,
                'unit_type' => null,
                'value' => null,
                'confidence_score' => 0,
                'notes' => 'Requested prioritized specification was not returned by AI.',
            ];
        }

        return array_values($normalized);
    }

    private function syncCriticalMatchingCriteria(Equipment $equipment, Collection $specs): array
    {
        $criteriaRows = EquipmentCriticalMatchingCriterion::query()
            ->where('product_category_id', $equipment->product_category_id)
            ->where('is_active', true)
            ->get();

        $criteriaState = (array) ($equipment->critical_matching_criteria ?? []);

        if ($criteriaRows->isEmpty() || $specs->isEmpty()) {
            return $criteriaState;
        }

        $specIndex = $this->buildSpecIndex($specs);
        $hasChanges = false;

        foreach ($criteriaRows as $row) {
            $matchedSpec = $this->findSpecForCriterion($row, $specIndex);

            if ($matchedSpec === null) {
                continue;
            }

            $threshold = $this->extractComparableThreshold($matchedSpec->value);

            if ($threshold === null) {
                continue;
            }

            $criteriaKey = (string) $row->criteria_key;
            $existingState = is_array($criteriaState[$criteriaKey] ?? null)
                ? $criteriaState[$criteriaKey]
                : [];

            $criteriaState[$criteriaKey] = [
                'enabled' => array_key_exists('enabled', $existingState)
                    ? filter_var($existingState['enabled'], FILTER_VALIDATE_BOOLEAN)
                    : true,
                'threshold' => $threshold,
                'weight' => isset($existingState['weight']) ? (int) $existingState['weight'] : (int) ($row->default_weight ?? 50),
                'upgrade_exceeds_value' => array_key_exists('upgrade_exceeds_value', $existingState)
                    ? filter_var($existingState['upgrade_exceeds_value'], FILTER_VALIDATE_BOOLEAN)
                    : (bool) ($row->upgrade_exceeds_value ?? true),
                'caution_if_change_value' => array_key_exists('caution_if_change_value', $existingState)
                    ? filter_var($existingState['caution_if_change_value'], FILTER_VALIDATE_BOOLEAN)
                    : (bool) ($row->caution_if_change_value ?? false),
                'upgrade_is_below_value' => array_key_exists('upgrade_is_below_value', $existingState)
                    ? filter_var($existingState['upgrade_is_below_value'], FILTER_VALIDATE_BOOLEAN)
                    : (bool) ($row->upgrade_is_below_value ?? false),
                'caution_if_below_value' => array_key_exists('caution_if_below_value', $existingState)
                    ? filter_var($existingState['caution_if_below_value'], FILTER_VALIDATE_BOOLEAN)
                    : (bool) ($row->caution_if_below_value ?? false),
            ];

            if (($existingState['threshold'] ?? null) !== $threshold) {
                $hasChanges = true;
            }
        }

        if ($hasChanges) {
            $equipment->forceFill([
                'critical_matching_criteria' => $criteriaState,
            ])->save();
        }

        return $criteriaState;
    }

    private function syncCategoryCatalogSpecs(Equipment $equipment, Collection $specs): void
    {
        if ($specs->isEmpty()) {
            return;
        }

        foreach ($specs as $spec) {
            $criteriaKey = trim((string) $spec->spec_key);

            if ($criteriaKey === '') {
                continue;
            }

            $existing = EquipmentCriticalMatchingCriterion::query()
                ->where('product_category_id', $equipment->product_category_id)
                ->where('criteria_key', $criteriaKey)
                ->first();

            $payload = [
                'name' => (string) ($spec->spec_label ?? $criteriaKey),
                'unit' => (string) ($spec->unit ?? ''),
                'source_type' => 'ai',
                'is_active' => true,
            ];

            if ($existing) {
                $existing->forceFill($payload)->save();
                continue;
            }

            EquipmentCriticalMatchingCriterion::create([
                'product_category_id' => $equipment->product_category_id,
                'criteria_key' => $criteriaKey,
                'name' => $payload['name'],
                'unit' => $payload['unit'],
                'source_type' => 'ai',
                'default_weight' => 50,
                'upgrade_exceeds_value' => true,
                'caution_if_change_value' => false,
                'upgrade_is_below_value' => false,
                'caution_if_below_value' => false,
                'sort_order' => 0,
                'is_active' => true,
                'is_key_criteria' => false,
            ]);
        }
    }

    private function buildSpecIndex(Collection $specs): array
    {
        $index = [];

        foreach ($specs as $spec) {
            $keys = [
                $this->normalizeMatchKey($spec->spec_key),
                $this->normalizeMatchKey($spec->spec_label),
            ];

            $normalizedSpecKey = $this->normalizeMatchKey($spec->spec_key);
            $aliasTarget = $this->criteriaAliasTarget($normalizedSpecKey);
            if ($aliasTarget) {
                $keys[] = $this->normalizeMatchKey($aliasTarget);
            }

            foreach (array_filter(array_unique($keys)) as $key) {
                $index[$key] = $spec;
            }
        }

        return $index;
    }

    private function findSpecForCriterion(EquipmentCriticalMatchingCriterion $criterion, array $specIndex): ?EquipmentSpecification
    {
        $candidates = [
            $this->normalizeMatchKey($criterion->criteria_key),
            $this->normalizeMatchKey($criterion->name),
        ];

        foreach (array_filter($candidates) as $candidate) {
            if (isset($specIndex[$candidate])) {
                return $specIndex[$candidate];
            }

            $aliasTarget = $this->criteriaAliasTarget($candidate);
            $aliasKey = $aliasTarget ? $this->normalizeMatchKey($aliasTarget) : null;

            if ($aliasKey && isset($specIndex[$aliasKey])) {
                return $specIndex[$aliasKey];
            }
        }

        return null;
    }

    private function criteriaAliasTarget(string $candidate): ?string
    {
        return match ($candidate) {
            'operatingweight', 'weight', 'machineweight' => 'operating_weight',
            'enginehorsepower', 'horsepower', 'enginehp', 'hp' => 'engine_horsepower',
            'ratedoperatingcapacity', 'operatingcapacity', 'capacity', 'roc' => 'rated_operating_capacity',
            'tippingload' => 'tipping_load',
            'hydraulicflow', 'flow' => 'hydraulic_flow',
            'groundpressure' => 'ground_pressure',
            'travelspeed' => 'travel_speed',
            'overallwidth' => 'width',
            'overallheight' => 'height',
            'overalllength' => 'length',
            default => null,
        };
    }

    private function normalizeMatchKey(?string $value): string
    {
        return preg_replace('/[^a-z0-9]+/', '', strtolower(trim((string) $value))) ?? '';
    }

    private function extractComparableThreshold(mixed $value): ?string
    {
        $stringValue = trim((string) ($value ?? ''));

        if ($stringValue === '') {
            return null;
        }

        if (!preg_match('/-?\d+(?:,\d{3})*(?:\.\d+)?/', $stringValue, $matches)) {
            return null;
        }

        $numericValue = (float) str_replace(',', '', $matches[0]);

        if ((float) ((int) $numericValue) === $numericValue) {
            return (string) ((int) $numericValue);
        }

        return rtrim(rtrim(number_format($numericValue, 4, '.', ''), '0'), '.');
    }

    public static function formatSpec(EquipmentSpecification $s): array
    {
        return [
            'id'               => $s->id,
            'spec_key'         => $s->spec_key,
            'spec_label'       => $s->spec_label,
            'value'            => $s->value,
            'value_type'       => $s->value_type,
            'unit'             => $s->unit,
            'source_url'       => $s->source_url,
            'confidence_score' => $s->confidence_score,
            'notes'            => $s->notes,
            'is_approved'      => $s->is_approved,
            'is_manual_override' => $s->is_manual_override,
            'last_verified_at' => $s->last_verified_at?->format('Y-m-d H:i'),
            'approved_by_name' => $s->approvedByUser?->name,
            'approved_at'      => $s->approved_at?->format('Y-m-d H:i'),
        ];
    }
}
