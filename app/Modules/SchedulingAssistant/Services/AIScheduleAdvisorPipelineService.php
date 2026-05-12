<?php

namespace App\Modules\SchedulingAssistant\Services;

use App\Modules\SchedulingAssistant\DTOs\AssistantResultData;
use App\Modules\SchedulingAssistant\DTOs\AIAdvisorResultData;

class AIScheduleAdvisorPipelineService
{
    public function __construct(
        protected ScheduleAssistantService $scheduleAssistantService,
        protected AIContextBuilderService $contextBuilderService,
        protected OpenAISchedulingAdvisorService $openAIService,
    ) {
    }

    public function analyzeWithAI(int $orderProductId): array
    {
        /** @var AssistantResultData $assistantResult */
        $assistantResult = $this->scheduleAssistantService->analyzeOrderProduct($orderProductId);

        $context = $this->contextBuilderService->build($orderProductId, $assistantResult);

        /** @var AIAdvisorResultData $aiResult */
        $aiResult = $this->openAIService->advise($context);

        return [
            'assistant' => $assistantResult->toArray(),
            'ai' => $aiResult->toArray(),
            'context' => $context, // helpful for dev/testing; remove later if you want
        ];
    }
}
