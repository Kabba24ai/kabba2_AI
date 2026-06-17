<?php

namespace App\Services;

use Throwable;

class AiEquipmentSuggestionService
{
    public function __construct(private OpenAIService $openAI) {}

    /**
     * Ask OpenAI to rank and reason over the candidate equipment list.
     *
     * @param  array $context   Built by AiEquipmentSuggestionController
     * @return array            ['success' => bool, 'suggestions' => [...], 'overall_summary' => '...', 'error' => '...']
     */
    public function suggest(array $context): array
    {
        $model = (string) config('services.openai.model', 'gpt-4.1-mini');

        $payload = [
            'model' => $model,
            'input' => [
                [
                    'role'    => 'system',
                    'content' => [
                        ['type' => 'input_text', 'text' => $this->systemPrompt()],
                    ],
                ],
                [
                    'role'    => 'user',
                    'content' => [
                        ['type' => 'input_text', 'text' => json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)],
                    ],
                ],
            ],
            'text' => [
                'format' => [
                    'type'   => 'json_schema',
                    'name'   => 'equipment_suggestion_result',
                    'schema' => $this->responseSchema(),
                    'strict' => true,
                ],
            ],
            'max_output_tokens' => 2000,
        ];

        try {
            $json       = $this->openAI->request('/responses', $payload, 'equipment_suggestion');
            $outputText = $this->extractOutputText($json);

            if (!$outputText) {
                return ['success' => false, 'error' => 'No structured output returned by AI.'];
            }

            $decoded = json_decode($outputText, true);

            if (!is_array($decoded)) {
                return ['success' => false, 'error' => 'AI response could not be decoded.'];
            }

            return [
                'success'         => true,
                'suggestions'     => $decoded['suggestions'] ?? [],
                'overall_summary' => $decoded['overall_summary'] ?? '',
            ];
        } catch (Throwable $e) {
            report($e);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function systemPrompt(): string
    {
        return <<<PROMPT
You are a Kabba Equipment Assignment Advisor.

Your job is to evaluate a list of candidate equipment for an incoming rental order and rank them by suitability.

Instructions:
1. The "order" block describes what the customer ordered, the rental window, and the product category.
2. The "comparison_criteria" block lists the specification keys that matter for this category, sorted from most to least important (critical → high → medium → low).
3. Each candidate in "candidates" has a current status (Available, Rented, Maintenance) and a list of "matched_specs" — the AI-extracted specification values that match the comparison criteria.
4. Rank candidates from most to least suitable. Assign each a suitability_score from 0–100.
5. Prefer: Available > Maintenance > Rented (no conflict already filtered).
6. Prefer candidates whose matched_specs cover more critical and high importance criteria with high confidence.
7. Flag any concerns (e.g., maintenance hold, no AI profile, low confidence specs) as warnings.
8. If no candidate is suitable, say so clearly in overall_summary.
9. Return only valid JSON matching the schema.
10. Be concise, specific, and operationally focused.
PROMPT;
    }

    protected function responseSchema(): array
    {
        return [
            'type'                 => 'object',
            'additionalProperties' => false,
            'properties'           => [
                'suggestions' => [
                    'type'  => 'array',
                    'items' => [
                        'type'                 => 'object',
                        'additionalProperties' => false,
                        'properties'           => [
                            'equipment_id'       => ['type' => 'integer'],
                            'rank'               => ['type' => 'integer'],
                            'suitability_score'  => ['type' => 'integer'],
                            'recommendation_type' => [
                                'type' => 'string',
                                'enum' => ['best_match', 'suitable', 'conditional', 'not_recommended'],
                            ],
                            'reasoning'          => ['type' => 'string'],
                            'actions_required'   => ['type' => 'array', 'items' => ['type' => 'string']],
                            'warnings'           => ['type' => 'array', 'items' => ['type' => 'string']],
                        ],
                        'required' => [
                            'equipment_id',
                            'rank',
                            'suitability_score',
                            'recommendation_type',
                            'reasoning',
                            'actions_required',
                            'warnings',
                        ],
                    ],
                ],
                'overall_summary' => ['type' => 'string'],
            ],
            'required' => ['suggestions', 'overall_summary'],
        ];
    }

    protected function extractOutputText(array $json): ?string
    {
        if (!empty($json['output_text']) && is_string($json['output_text'])) {
            return $json['output_text'];
        }

        foreach ($json['output'] ?? [] as $outputItem) {
            foreach ($outputItem['content'] ?? [] as $contentItem) {
                if (($contentItem['type'] ?? null) === 'output_text' && !empty($contentItem['text'])) {
                    return $contentItem['text'];
                }
            }
        }

        return null;
    }
}
