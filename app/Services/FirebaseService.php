<?php

namespace App\Services;

use App\Models\Iam\Personnel\UserDevice;
use Google\Auth\Credentials\ServiceAccountCredentials;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    protected string $projectId;
    protected string $serviceAccountPath;
    protected Client $httpClient;

    public function __construct()
    {
        $this->projectId = config('services.firebase.project_id');
        $this->serviceAccountPath = base_path(config('services.firebase.service_account_path'));

        $this->httpClient = new Client([
            'base_uri' => 'https://fcm.googleapis.com/v1/',
            'timeout'  => 5.0,
        ]);
    }

    /**
     * Get OAuth2 access token using service account.
     */
    protected function getAccessToken(): string
    {
        $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];

        $credentials = new ServiceAccountCredentials(
            $scopes,
            $this->serviceAccountPath
        );

        $token = $credentials->fetchAuthToken();

        if (!isset($token['access_token'])) {
            throw new \RuntimeException('Unable to fetch Firebase access token.');
        }

        return $token['access_token'];
    }

    /**
     * Send notification to a specific device token.
     */
    public function sendToDevice(string $deviceToken, string $title, string $body, array $data = []): ?string
    {
        $accessToken = $this->getAccessToken();

        $url = "projects/{$this->projectId}/messages:send";

        $payload = [
            'message' => [
                'token' => $deviceToken,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                ],
                // Custom data (optional)
                'data' => $data,
            ],
        ];

        try {
            $response = $this->httpClient->post($url, [
                'headers' => [
                    'Authorization' => "Bearer {$accessToken}",
                    'Content-Type'  => 'application/json',
                ],
                'json' => $payload,
            ]);

            $body = json_decode((string) $response->getBody(), true);

            // Response contains message name like: projects/xxx/messages/0:123...
            return $body['name'] ?? null;
        } catch (\Throwable $e) {
            Log::error('FCM sendToDevice error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return null;
        }
    }

    /**
     * Send notification to a topic.
     */
    public function sendToTopic(string $topic, string $title, string $body, array $data = []): ?string
    {
        $accessToken = $this->getAccessToken();

        $url = "projects/{$this->projectId}/messages:send";

        $payload = [
            'message' => [
                'topic' => $topic,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                ],
                'data' => $data,
            ],
        ];

        try {
            $response = $this->httpClient->post($url, [
                'headers' => [
                    'Authorization' => "Bearer {$accessToken}",
                    'Content-Type'  => 'application/json',
                ],
                'json' => $payload,
            ]);

            $body = json_decode((string) $response->getBody(), true);

            return $body['name'] ?? null;
        } catch (\Throwable $e) {
            Log::error('FCM sendToTopic error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return null;
        }
    }

    /**
     * Send notification to all user devices.
     */
    public function sendToAllDevices(string $title, string $body, array $data = []): array
    {
        $devices = UserDevice::whereNotNull('fcm_token')->pluck('fcm_token')->filter();
        $results = [];

        foreach ($devices as $deviceToken) {
            if (!$deviceToken) {
                continue; // Skip if token is null or empty
            }
            $messageId = $this->sendToDevice($deviceToken, $title, $body, $data);
            $results[$deviceToken] = $messageId;
        }

        return $results;
    }
}
