<?php

namespace App\Services;

use App\Helpers\ConfigurationHelper;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class TwilioService
{
    protected ?Client $client = null;
    protected string $sid;
    protected string $authToken;
    protected string $fromNumber;
    protected string $messagingServiceSid;
    protected bool $isTestMode;

    public function __construct()
    {
        $settings = ConfigurationHelper::getSettings('Communication Settings');

        $this->sid = (string) ($settings['twilio_sid'] ?? '');
        $this->authToken = (string) ($settings['twilio_auth_token'] ?? '');
        $this->fromNumber = (string) ($settings['twilio_from_number'] ?? '');
        $this->messagingServiceSid = (string) ($settings['twilio_messaging_service_sid'] ?? '');
        // If test mode is enabled, use Twilio's Magic Number for testing WhatsApp
        // https://www.twilio.com/docs/whatsapp/sandbox
        $this->isTestMode = (bool) ($settings['sms_test_mode'] ?? false);

        if (empty($this->sid) || empty($this->authToken) || empty($this->fromNumber)) {
            throw new \InvalidArgumentException('Twilio credentials are not fully configured.');
        }

        $this->client = new Client($this->sid, $this->authToken);
    }

    /**
     * Send a single SMS message.
     *
     * @param string $to E.164 formatted destination number
     * @param string $message Body text (max 1600 chars typical Twilio limit per segment aggregation)
     * @param array $options ['media_urls' => [...], 'status_callback' => url]
     * @return array
     */
    public function sendSms(string $to, string $message, array $options = []): array
    {
        $to = $this->normalizePhone($to);

        $result = [
            'success' => false,
            'message' => 'Unknown error',
            'sid' => null,
            'to' => $to,
            'from' => $this->fromNumber,
        ];

        if (!$this->isValidPhone($to)) {
            $result['message'] = 'Invalid destination phone number';
            return $result;
        }

        try {
            $data = ['from' => $this->messagingServiceSid, 'body' => $message];
            if (!empty($options['media_urls']) && is_array($options['media_urls'])) {
                $data['mediaUrl'] = $options['media_urls'];
            }
            if (!empty($options['status_callback'])) {
                $data['statusCallback'] = $options['status_callback'];
            }

            $twilioMessage = $this->client->messages->create($to, $data);
            $result['success'] = true;
            $result['message'] = 'SMS sent';
            $result['sid'] = $twilioMessage->sid ?? null;
            $result['segments'] = $twilioMessage->numSegments ?? null;
        } catch (\Throwable $e) {
            $this->logError('Twilio sendSms failed: ' . $e->getMessage(), ['exception' => $e]);
            $result['message'] = $e->getMessage();
        }
        return $result;
    }

    /**
     * Send an SMS to multiple recipients.
     *
     * @param array $recipients Array of destination phone numbers
     * @param string $message
     * @param array $options
     * @return array ['total' => int, 'success' => int, 'failed' => int, 'results' => [...]]
     */
    public function sendBulkSms(array $recipients, string $message, array $options = []): array
    {
        $results = [];
        $success = 0;
        $failed = 0;
        foreach ($recipients as $to) {
            $result = $this->sendSms($to, $message, $options);
            $results[] = $result;
            if ($result['status'] === 'success') {
                $success++;
            } else {
                $failed++;
            }
        }

        return [
            'success' => true,
            'total' => count($recipients),
            'success' => $success,
            'failed' => $failed,
            'results' => $results,
        ];
    }

    /**
     * Send a WhatsApp message via Twilio (uses the same messaging API with whatsapp: prefix)
     *
     * @param string $to Destination number (E.164)
     * @param string $message
     * @param array $options
     * @return array
     */
    public function sendWhatsApp(string $to, string $message, array $options = []): array
    {
        if ($this->isTestMode) {
            $this->fromNumber = '+14155238886'; // Sandbox sender
        }

        $result = [
            'status' => 'failure',
            'message' => 'Unknown error',
            'sid' => null,
            'to' => $to,
            'from' => $this->fromNumber,
        ];

        $validTo = $this->normalizePhone($to);
        if (!$this->isValidPhone($validTo)) {
            $result['message'] = 'Invalid destination phone number';
            return $result;
        }

        $to   = 'whatsapp:+' . ltrim($validTo, '+');
        $from = 'whatsapp:+' . ltrim($this->fromNumber, '+');

        try {
            $data = [
                'from' => $from,
                'body' => $message,
            ];
            if (!empty($options['media_urls']) && is_array($options['media_urls'])) {
                $data['mediaUrl'] = $options['media_urls'];
            }
            if (!empty($options['status_callback'])) {
                $data['statusCallback'] = $options['status_callback'];
            }

            $twilioMessage = $this->client->messages->create($to, $data);

            return [
                'success' => true,
                'message' => 'WhatsApp message sent',
                'sid' => $twilioMessage->sid ?? null,
                'to' => $to,
                'from' => $from,
            ];
        } catch (\Throwable $e) {
            $this->logError('Twilio sendWhatsApp failed: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'to' => $to,
            ];
        }
    }

    protected function logInfo(string $message, array $context = []): void
    {
        Log::channel('twilio')->info($message, $context);
    }

    protected function logError(string $message, array $context = []): void
    {
        Log::channel('twilio')->error($message, $context);
    }

    protected function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);
        $normalized = '';

        // If already starts with country code (e.g., 91 for India, 1 for US)
        if (str_starts_with($digits, '91') && strlen($digits) === 12) {
            $normalized = '+'.$digits;
        } elseif (strlen($digits) === 10) {
            // If number is 10 digits, assume India (+91)
            $normalized = '+91'.$digits;
        } elseif (str_starts_with($digits, '00')) {
            // If starts with 00, remove it and add +
            $digits = substr($digits, 2);
            $normalized = '+'.$digits;
        } elseif (str_starts_with($digits, '1') && strlen($digits) === 11) {
            // If already starts with 1 and is 11 digits, assume US
            $normalized = '+'.$digits;
        } else {
            // Fallback: just add +
            $normalized = '+'.$digits;
        }

        return $normalized;
    }

    protected function isValidPhone(string $phone): bool
    {
        return preg_match('/^\+[1-9]\d{7,15}$/', $phone) === 1;
    }
}
