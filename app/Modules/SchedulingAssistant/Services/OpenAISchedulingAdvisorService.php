<?php

namespace App\Modules\SchedulingAssistant\Services;

use App\Modules\SchedulingAssistant\DTOs\AIAdvisorResultData;
use App\Services\OpenAIService;
use Throwable;

class OpenAISchedulingAdvisorService
{
    public function __construct(private OpenAIService $openAI) {}

    public function advise(array $context): AIAdvisorResultData
    {
        $model = (string) config('services.openai.model', 'gpt-4.1-mini');

        $schema = $this->responseSchema();
        $requestPayload = [
            'model' => $model,
            'input' => [
                [
                    'role' => 'system',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => $this->systemPrompt(),
                        ],
                    ],
                ],
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                        ],
                    ],
                ],
            ],
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'scheduling_advisor_result',
                    'schema' => $schema,
                    'strict' => true,
                ],
            ],
            'max_output_tokens' => 2500,
        ];

        if ($this->supportsReasoningEffort($model)) {
            $requestPayload['reasoning'] = [
                'effort' => 'medium',
            ];
        }

        try {
            $json = $this->openAI->request('/responses', $requestPayload, 'scheduling_advisor');

            $outputText = $this->extractOutputText($json);

            if (!$outputText) {
                return new AIAdvisorResultData(
                    success: false,
                    recommendation: null,
                    rawResponse: $json,
                    error: 'No structured output text was returned by the AI advisor.'
                );
            }

            $decoded = json_decode($outputText, true);

            if (!is_array($decoded)) {
                return new AIAdvisorResultData(
                    success: false,
                    recommendation: null,
                    rawResponse: $json,
                    error: 'Structured output could not be decoded as JSON.'
                );
            }

            return new AIAdvisorResultData(
                success: true,
                recommendation: $decoded,
                rawResponse: $json,
                error: null
            );
        } catch (Throwable $e) {
            report($e);

            return new AIAdvisorResultData(
                success: false,
                recommendation: null,
                error: $e->getMessage()
            );
        }
    }

    protected function systemPrompt(): string
    {
        return <<<PROMPT
You are the Kabba Scheduling Advisor.

You are operating in advisory mode only.
You must not assume the system will auto-assign equipment.
You must recommend the best equipment option using the supplied order, candidate, issue, and rule context.

Required behavior:
1. Respect the supplied relationship rules exactly.
2. A primary match requires no action unless another operational risk exists.
3. An upgrade is allowed, but should produce internal notification only unless the supplied context says otherwise.
4. A downgrade must be treated as requiring review and customer approval unless the supplied context says otherwise.
5. Operational risks matter:
   - overlap conflict
   - damaged status
   - maintenance hold
   - open service tasks
   - location mismatch
6. Prefer:
   - primary and ready
   - upgrade and ready
   - conditional choices only if clearly safer than alternatives
7. Avoid blocked candidates unless every option is blocked.
8. Return only valid JSON matching the schema.
9. Be concise, operational, and specific.

If all choices are weak, say so clearly.
PROMPT;
    }

    protected function responseSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'recommended_equipment_id' => [
                    'type' => ['integer', 'null'],
                ],
                'recommended_equipment_name' => [
                    'type' => ['string', 'null'],
                ],
                'relationship_type' => [
                    'type' => 'string',
                    'enum' => ['primary', 'upgrade', 'downgrade', 'unknown'],
                ],
                'decision' => [
                    'type' => 'string',
                    'enum' => ['recommended', 'review_required', 'no_safe_recommendation'],
                ],
                'actions_required' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                ],
                'reasoning' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                ],
                'alternatives' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'equipment_id' => [
                                'type' => ['integer', 'null'],
                            ],
                            'equipment_name' => [
                                'type' => ['string', 'null'],
                            ],
                            'relationship_type' => [
                                'type' => 'string',
                                'enum' => ['primary', 'upgrade', 'downgrade', 'unknown'],
                            ],
                            'actions_required' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'string',
                                ],
                            ],
                            'summary' => [
                                'type' => 'string',
                            ],
                        ],
                        'required' => [
                            'equipment_id',
                            'equipment_name',
                            'relationship_type',
                            'actions_required',
                            'summary',
                        ],
                    ],
                ],
                'warnings' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                ],
            ],
            'required' => [
                'recommended_equipment_id',
                'recommended_equipment_name',
                'relationship_type',
                'decision',
                'actions_required',
                'reasoning',
                'alternatives',
                'warnings',
            ],
        ];
    }

    protected function extractOutputText(array $json): ?string
    {
        if (!empty($json['output_text']) && is_string($json['output_text'])) {
            return $json['output_text'];
        }

        if (!empty($json['output']) && is_array($json['output'])) {
            foreach ($json['output'] as $outputItem) {
                if (!empty($outputItem['content']) && is_array($outputItem['content'])) {
                    foreach ($outputItem['content'] as $contentItem) {
                        if (($contentItem['type'] ?? null) === 'output_text' && !empty($contentItem['text'])) {
                            return $contentItem['text'];
                        }
                    }
                }
            }
        }

        return null;
    }

    protected function supportsReasoningEffort(string $model): bool
    {
        $normalized = strtolower($model);

        return str_starts_with($normalized, 'gpt-5');
    }
}
