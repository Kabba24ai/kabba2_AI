<?php

namespace App\Services\Routing\Settings;

use App\Helpers\ConfigurationHelper;
use App\Services\Routing\Contracts\RoutingConfigProvider;
use Throwable;

/**
 * Reads the routing integration's runtime config from the admin-managed
 * Configurations store (the `settings` key-value table, group "Google Maps
 * Settings"). The API key row is encrypted at rest; it is read back with
 * ConfigurationHelper::getDecryptedSetting and never leaves the server.
 *
 * Every read is defensive: if the settings rows do not exist yet (e.g. before
 * the seeding migration runs) or the store is unreachable, the integration
 * simply reports "not configured" and no provider call is attempted.
 */
class ConfigurationRoutingConfigProvider implements RoutingConfigProvider
{
    private const GROUP = 'Google Maps Settings';

    public function enabled(): bool
    {
        return $this->bool('google_maps_enabled');
    }

    public function provider(): string
    {
        return $this->raw('google_maps_provider') ?: 'google';
    }

    public function apiKey(): ?string
    {
        try {
            $key = ConfigurationHelper::getDecryptedSetting(self::GROUP, 'google_maps_api_key');
        } catch (Throwable $e) {
            return null;
        }

        $key = is_string($key) ? trim($key) : '';

        return $key !== '' ? $key : null;
    }

    public function trafficAware(): bool
    {
        return $this->bool('google_maps_traffic_aware', true);
    }

    public function timeout(): int
    {
        $timeout = (int) ($this->raw('google_maps_timeout') ?: 15);

        return $timeout > 0 ? $timeout : 15;
    }

    public function units(): string
    {
        $units = strtolower($this->raw('google_maps_units') ?: 'imperial');

        return in_array($units, ['imperial', 'metric'], true) ? $units : 'imperial';
    }

    public function isConfigured(): bool
    {
        return $this->enabled() && $this->apiKey() !== null;
    }

    /** Raw (non-secret) setting value; null when absent/unreadable. */
    private function raw(string $key): ?string
    {
        try {
            $value = ConfigurationHelper::getSettings(self::GROUP, $key);
        } catch (Throwable $e) {
            return null;
        }

        return is_string($value) ? $value : ($value === null ? null : (string) $value);
    }

    private function bool(string $key, bool $default = false): bool
    {
        $value = $this->raw($key);
        if ($value === null || $value === '') {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
