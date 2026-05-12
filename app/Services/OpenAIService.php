<?php

namespace App\Services;

use App\Models\Global\OpenAILog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class OpenAIService
{
    /**
     * Text generation via Responses API.
     */
    public function generateText(string $prompt): string
    {
        $responseData = $this->request('/responses', [
            'model' => $this->model(),
            'input' => $prompt,
        ], 'responses');

        return $responseData['output_text'] ?? 'No response text returned.';
    }

    /**
     * Chat completion helper for conversational prompts.
     */
    public function chatCompletion(array $messages, array $options = []): array
    {
        $payload = array_merge([
            'model' => $this->model(),
            'messages' => $messages,
        ], $options);

        return $this->request('/chat/completions', $payload, 'chat_completion');
    }

    public function request(string $endpoint, array $payload, string $requestType): array
    {
        $apiKey = $this->apiKey();
        $baseUrl = rtrim($this->baseUrl(), '/');
        $startedAt = microtime(true);

        $response = Http::timeout(60)
            ->withToken($apiKey)
            ->acceptJson()
            ->post("{$baseUrl}{$endpoint}", $payload);

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
        $responseJson = $response->json();

        $this->storeLog([
            'request_type' => $requestType,
            'status' => $response->successful() ? 'success' : 'failed',
            'sent_data' => $payload,
            'received_data' => $responseJson,
            'error_message' => $response->successful() ? null : ($responseJson['error']['message'] ?? $response->body()),
            'error_code' => $response->successful() ? null : ($responseJson['error']['code'] ?? (string) $response->status()),
            'prompt_tokens' => $responseJson['usage']['prompt_tokens'] ?? $responseJson['usage']['input_tokens'] ?? null,
            'completion_tokens' => $responseJson['usage']['completion_tokens'] ?? $responseJson['usage']['output_tokens'] ?? null,
            'total_tokens' => $responseJson['usage']['total_tokens'] ?? null,
            'response_time_ms' => $durationMs,
            'openai_request_id' => $response->header('x-request-id'),
            'model' => $payload['model'] ?? null,
            'endpoint' => $endpoint,
            'ip_address' => app()->bound('request') ? request()->ip() : null,
            'notes' => null,
        ]);

        if ($response->failed()) {
            throw new RuntimeException('OpenAI request failed: ' . ($responseJson['error']['message'] ?? $response->body()));
        }

        return is_array($responseJson) ? $responseJson : [];
    }

    protected function apiKey(): string
    {
        $apiKey = (string) config('services.openai.api_key');

        if (!$apiKey) {
            throw new RuntimeException('OPENAI_API_KEY is not configured.');
        }

        return $apiKey;
    }

    protected function model(): string
    {
        return (string) config('services.openai.model', 'gpt-4.1-mini');
    }

    protected function baseUrl(): string
    {
        return (string) config('services.openai.base_url', 'https://api.openai.com/v1');
    }

    protected function storeLog(array $data): void
    {
        try {
            OpenAILog::create($data);
        } catch (Throwable $exception) {
            Log::warning('Failed to persist OpenAI log.', [
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
