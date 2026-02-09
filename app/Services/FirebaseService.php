<?php

namespace App\Services;

use App\Models\Configurations\UserNotificationSetting;
use Google\Auth\Credentials\ServiceAccountCredentials;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

// Models
use App\Models\Iam\Personnel\UserDevice;
use App\Models\Iam\Personnel\UserNotification;

class FirebaseService
{
    protected string $projectId;
    protected string $serviceAccountPath;
    protected Client $httpClient;

    public function __construct()
    {
        $this->projectId = config('services.firebase.project_id');

        $this->serviceAccountPath = storage_path(
            config('services.firebase.service_account_path')
        );

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
        // Log::debug('FCM getAccessToken: Starting token retrieval', [
        //     'project_id' => $this->projectId,
        //     'service_account_path' => $this->serviceAccountPath,
        // ]);

        $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];

        try {
            $credentials = new ServiceAccountCredentials(
                $scopes,
                $this->serviceAccountPath
            );

            $token = $credentials->fetchAuthToken(
                    \Google\Auth\HttpHandler\HttpHandlerFactory::build(new Client())
                );


            if (!isset($token['access_token'])) {
                throw new \RuntimeException('Unable to fetch Firebase access token.');
            }

            return $token['access_token'];
        } catch (\Throwable $e) {
            Log::error('FCM getAccessToken error', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'exception_class' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
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
                'data' => array_map('strval', $data),
            ],
        ];

        try {

            $response = $this->httpClient->post($url, [
                'headers' => [
                    'Authorization' => "Bearer {$accessToken}",
                    'Content-Type'  => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $body = json_decode((string) $response->getBody(), true);

            // Response contains message name like: projects/xxx/messages/0:123...
            return $body['name'] ?? null;
        } catch (\Throwable $e) {
            Log::error('FCM sendToDevice error', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'device_token' => substr($deviceToken, 0, 20) . '...',
                'title' => $title,
                'exception_class' => get_class($e),
                'trace' => $e->getTraceAsString(),
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
            Log::error('FCM sendToTopic error', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'topic' => $topic,
                'title' => $title,
                'exception_class' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Send notification to all user devices.
     */
    public function sendToAllDevices(string $title, string $body, array $data = []): array
    {
        $devices = UserDevice::has('user')->whereNotNull('fcm_token')->get();

        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($devices as $device) {
            $deviceToken = $device->fcm_token;
            if (!$deviceToken) {
                continue; // Skip if token is null or empty
            }
            $messageId = $this->sendToDevice($deviceToken, $title, $body, $data);
            $results[$deviceToken] = $messageId;

            if ($messageId) {
                $successCount++;
                $user = $device->user;
                UserNotification::create([
                    'user_id' => $user->id,
                    'order_id' => $data['order_id'] ?? null,
                    'status' => 'unread',
                    'type' => 'new_order',
                    'title' => $title,
                    'body' => $body,
                    'params' => json_encode($data),
                ]);

            } else {
                $failureCount++;
            }
        }

        Log::info('FCM sendToAllDevices: Bulk send completed', [
            'total_devices' => count($results),
            'successful' => $successCount,
            'failed' => $failureCount,
        ]);

        return $results;
    }
}
