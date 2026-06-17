<?php

namespace App\Jobs;

use App\Services\DispatchAI\DispatchAIService;
use App\Services\DispatchAI\DispatchContextBuilder;
use App\Services\DispatchAI\DispatchDraftProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BuildDispatchDraftJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;
    public int $tries   = 2;

    public function __construct(
        public readonly int    $triggeredByUserId,
        public readonly string $triggeredBy   = 'manual',
        public readonly int    $lookAheadDays = 3,
    ) {}

    public function handle(
        DispatchContextBuilder $builder,
        DispatchAIService      $aiService,
        DispatchDraftProcessor $processor,
    ): void {
        try {
            $context  = $builder->build($this->lookAheadDays);
            $aiResult = $aiService->generateDraft($context);
            $processor->save($aiResult, $this->lookAheadDays, $this->triggeredByUserId, $this->triggeredBy);
        } catch (\Throwable $e) {
            Log::error('BuildDispatchDraftJob failed', [
                'error'             => $e->getMessage(),
                'triggered_by_user' => $this->triggeredByUserId,
            ]);
            throw $e;
        }
    }
}
