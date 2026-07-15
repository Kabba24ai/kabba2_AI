<?php

namespace App\Services\Website;

use App\Helpers\ConfigurationHelper;
use App\Models\Configurations\Setting;
use App\Models\Global\Media;
use Illuminate\Support\Facades\Cache;

/**
 * Global High Demand Alert popup configuration.
 *
 * Products opt in via their has_high_demand_alert checkbox; the popup's
 * IMAGE / TITLE / MESSAGE / PHONE / BUTTON TEXT are shared, edited on
 * Website Management → High Demand Alert, and stored as settings rows
 * (setting_type "High Demand Alert").
 *
 * Every field falls back to the original hard-coded popup content, so
 * installations with no configuration render exactly what they did before
 * this feature existed. The phone additionally falls back to the legacy
 * Branding site_phone, which the popup historically displayed.
 */
class HighDemandAlertService
{
    public const CACHE_KEY    = 'high_demand_alert_config';
    public const SETTING_TYPE = 'High Demand Alert';
    private const CACHE_TTL   = 1800;

    public const DEFAULT_TITLE       = 'High Demand Alert!';
    public const DEFAULT_MESSAGE     = 'This item is currently experiencing a spike in demand, please complete the reservation and then <span class="font-semibold italic">call Customer Service</span> to confirm the schedule details:';
    public const DEFAULT_BUTTON_TEXT = 'Continue Reservation';
    public const DEFAULT_IMAGE_PATH  = 'storage/front/images/lady-image.webp';

    public function config(): object
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $s = Setting::where('setting_type', self::SETTING_TYPE)
                ->pluck('setting_value', 'setting_name');

            $imageMediaId = $s['high_demand_alert_image'] ?? null;
            $imageUrl     = $imageMediaId ? Media::find($imageMediaId)?->url : null;

            return (object) [
                'title'        => filled($s['high_demand_alert_title'] ?? null)
                                    ? $s['high_demand_alert_title'] : self::DEFAULT_TITLE,
                'message'      => filled($s['high_demand_alert_message'] ?? null)
                                    ? $s['high_demand_alert_message'] : self::DEFAULT_MESSAGE,
                'phone'        => filled($s['high_demand_alert_phone'] ?? null)
                                    ? $s['high_demand_alert_phone']
                                    // Legacy fallback: the popup always showed the Branding phone
                                    : ConfigurationHelper::getSettings('Website Management Branding', 'site_phone'),
                'buttonText'   => filled($s['high_demand_alert_button_text'] ?? null)
                                    ? $s['high_demand_alert_button_text'] : self::DEFAULT_BUTTON_TEXT,
                'imageMediaId' => $imageMediaId ?: null,
                'imageUrl'     => $imageUrl ?: asset(self::DEFAULT_IMAGE_PATH),
                'usesDefaultImage' => ! $imageUrl,
            ];
        });
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
