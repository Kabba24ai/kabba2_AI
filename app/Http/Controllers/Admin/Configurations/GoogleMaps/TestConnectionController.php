<?php

namespace App\Http\Controllers\Admin\Configurations\GoogleMaps;

use App\Enums\Routing\RouteStatus;
use App\Http\Controllers\Controller;
use App\Models\Configurations\Setting;
use App\Services\Routing\RoutingService;
use Illuminate\Support\Carbon;

/**
 * Net-new "Test Connection" diagnostic for the Google Maps & Routing
 * integration (no equivalent existed). Performs the cheapest possible
 * server-side provider call — a single geocode of a fixed neutral address —
 * to confirm the stored key is valid and reachable. The key never leaves the
 * server and is never returned to the browser; only a normalized status,
 * message, and timestamps are echoed and persisted.
 */
class TestConnectionController extends Controller
{
    private const GROUP = 'Google Maps Settings';
    private const PROBE = '1600 Amphitheatre Parkway, Mountain View, CA 94043';

    public function __invoke(RoutingService $routing)
    {
        if (!$routing->isConfigured()) {
            $this->persist(RouteStatus::NotConfigured, success: false);

            return response()->json([
                'success' => false,
                'status'  => RouteStatus::NotConfigured->value,
                'message' => 'Enter and save an API key, and enable the integration, before testing.',
                'tested_at' => $this->now(),
            ]);
        }

        $result  = $routing->geocode(self::PROBE);
        $success = $result->status->isOk();

        $this->persist($result->status, $success);

        return response()->json([
            'success'   => $success,
            'status'    => $result->status->value,
            'message'   => $success
                ? 'Connection successful — geocoding and credentials are working.'
                : $this->failureMessage($result->status, $result->errorDetail),
            'tested_at' => $this->now(),
        ], $success ? 200 : 422);
    }

    private function failureMessage(RouteStatus $status, ?string $detail): string
    {
        return match ($status) {
            RouteStatus::InvalidCredentials  => 'The API key was rejected. Check the key and that Geocoding + Routes APIs are enabled and the key is unrestricted to this server.',
            RouteStatus::RateLimit           => 'The provider rate limit was reached. Try again shortly.',
            RouteStatus::Timeout             => 'The request timed out. Check outbound HTTPS access to Google.',
            RouteStatus::ProviderUnavailable => 'The provider was unreachable. Check outbound network access.',
            RouteStatus::GeocodingFailure    => 'The test address could not be geocoded — verify the Geocoding API is enabled.',
            default                          => 'Connection test failed: ' . $status->label() . '.',
        };
    }

    private function persist(RouteStatus $status, bool $success): void
    {
        $this->set('google_maps_connection_status', $status->value);
        $this->set('google_maps_last_tested_at', $this->now());
        if ($success) {
            $this->set('google_maps_last_success_at', $this->now());
        }
    }

    private function set(string $name, ?string $value): void
    {
        if ($setting = Setting::where('setting_name', $name)->where('setting_type', self::GROUP)->first()) {
            $setting->setting_value = $value;
            $setting->save();
        }
    }

    private function now(): string
    {
        return Carbon::now()->toDateTimeString();
    }
}
