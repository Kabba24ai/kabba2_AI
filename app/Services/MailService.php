<?php

namespace App\Services;

use App\Helpers\ConfigurationHelper;
use Illuminate\Support\Facades\Log;

class MailService
{
    protected array $settings = [];

    public function __construct()
    {
        $this->loadMailSettings();
    }

    /**
     * Load all mail credentials from the database and decrypt if needed.
     */
    private function loadMailSettings(): void
    {
        $get = fn($key) => ConfigurationHelper::getSettings('Mail Send Settings', $key);

        $this->settings = [
            'mailer'     => $get('mail_mailer') ?? '',
            'host'       => $get('mail_host') ?? '',
            'port'       => $get('mail_port') ?? '',
            'username'   => ConfigurationHelper::safeDecrypt($get('mail_username')),
            'password'   => ConfigurationHelper::safeDecrypt($get('mail_password')),
            'encryption' => $get('mail_encryption') ?? 'tls',
            'from'       => [
                'address' => $get('mail_from_address') ?? '',
                'name'    => $get('mail_from_name') ?? '',
            ],
        ];

    }

    /**
     * Get all mail settings.
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * Get a specific mail setting.
     */
    public function get(string $key): mixed
    {
        return $this->settings[$key] ?? null;
    }

    /**
     * Get "from" info.
     */
    public function getFrom(): array
    {
        return $this->settings['from'] ?? ['address' => '', 'name' => ''];
    }
}
