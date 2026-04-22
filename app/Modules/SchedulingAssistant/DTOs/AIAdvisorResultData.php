<?php

namespace App\Modules\SchedulingAssistant\DTOs;

class AIAdvisorResultData
{
    public function __construct(
        public bool $success,
        public ?array $recommendation,
        public ?array $rawResponse = null,
        public ?string $requestId = null,
        public ?string $error = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'recommendation' => $this->recommendation,
            'request_id' => $this->requestId,
            'error' => $this->error,
        ];
    }
}
