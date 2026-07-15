<?php

namespace App\Services\Website;

use App\Models\WebsiteManagement\WebsiteThemeSetting;
use Illuminate\Support\Facades\Cache;

class ThemeService
{
    const CACHE_KEY     = 'website_theme_settings';
    const CACHE_CSS_KEY = 'website_theme_css_vars';
    const CACHE_TTL     = 3600;

    /**
     * Full schema: group → key → config.
     * 'css_var' keys are emitted as CSS custom properties in cssVariables().
     * 'default' is used when no DB row exists.
     */
    public static array $schema = [

        'branding' => [
            'logo_primary'     => ['type' => 'media',    'label' => 'Primary Logo',      'default' => null],
            'logo_alternate'   => ['type' => 'media',    'label' => 'Alternate Logo',    'default' => null],
            'logo_mobile'      => ['type' => 'media',    'label' => 'Mobile Logo',       'default' => null],
            'logo_footer'      => ['type' => 'media',    'label' => 'Footer Logo',       'default' => null],
            'favicon'          => ['type' => 'media',    'label' => 'Favicon',           'default' => null],
            'apple_touch_icon' => ['type' => 'media',    'label' => 'Apple Touch Icon',  'default' => null],
            'og_image_default' => ['type' => 'media',    'label' => 'Default OG Image',  'default' => null],
            'business_name'    => ['type' => 'text',     'label' => 'Business Name',     'default' => ''],
            'tagline'          => ['type' => 'text',     'label' => 'Tagline',           'default' => ''],
        ],

        'colors' => [
            'color_primary'    => ['type' => 'color', 'label' => 'Primary',    'css_var' => '--color-primary',    'default' => '#3B82F6'],
            'color_secondary'  => ['type' => 'color', 'label' => 'Secondary',  'css_var' => '--color-secondary',  'default' => '#6B7280'],
            'color_accent'     => ['type' => 'color', 'label' => 'Accent',     'css_var' => '--color-accent',     'default' => '#F59E0B'],
            'color_success'    => ['type' => 'color', 'label' => 'Success',    'css_var' => '--color-success',    'default' => '#10B981'],
            'color_warning'    => ['type' => 'color', 'label' => 'Warning',    'css_var' => '--color-warning',    'default' => '#F59E0B'],
            'color_danger'     => ['type' => 'color', 'label' => 'Danger',     'css_var' => '--color-danger',     'default' => '#EF4444'],
            'color_light'      => ['type' => 'color', 'label' => 'Light',      'css_var' => '--color-light',      'default' => '#F9FAFB'],
            'color_dark'       => ['type' => 'color', 'label' => 'Dark',       'css_var' => '--color-dark',       'default' => '#111827'],
            'color_background' => ['type' => 'color', 'label' => 'Background', 'css_var' => '--color-background', 'default' => '#FFFFFF'],
            'color_text'       => ['type' => 'color', 'label' => 'Text',       'css_var' => '--color-text',       'default' => '#111827'],
            'color_text_muted' => ['type' => 'color', 'label' => 'Muted Text', 'css_var' => '--color-text-muted', 'default' => '#6B7280'],
        ],

        'typography' => [
            'font_heading'       => ['type' => 'text',   'label' => 'Heading Font',       'css_var' => '--font-heading',    'default' => 'Inter, sans-serif'],
            'font_body'          => ['type' => 'text',   'label' => 'Body Font',          'css_var' => '--font-body',       'default' => 'Inter, sans-serif'],
            'font_size_base'     => ['type' => 'text',   'label' => 'Base Font Size',     'css_var' => '--font-size-base',  'default' => '16px'],
            'line_height'        => ['type' => 'text',   'label' => 'Line Height',        'css_var' => '--line-height',     'default' => '1.6'],
            'letter_spacing'     => ['type' => 'text',   'label' => 'Letter Spacing',     'css_var' => '--letter-spacing',  'default' => '0em'],
            'font_weight_button' => ['type' => 'select', 'label' => 'Button Font Weight', 'css_var' => '--font-weight-btn', 'default' => '600'],
        ],

        'buttons' => [
            'btn_border_radius'   => ['type' => 'text',  'label' => 'Border Radius',     'css_var' => '--btn-radius',         'default' => '0.375rem'],
            'btn_padding_x'       => ['type' => 'text',  'label' => 'Padding X',         'css_var' => '--btn-padding-x',      'default' => '1.25rem'],
            'btn_padding_y'       => ['type' => 'text',  'label' => 'Padding Y',         'css_var' => '--btn-padding-y',      'default' => '0.5rem'],
            'btn_primary_bg'      => ['type' => 'color', 'label' => 'Primary BG',        'css_var' => '--btn-primary-bg',     'default' => ''],
            'btn_primary_text'    => ['type' => 'color', 'label' => 'Primary Text',      'css_var' => '--btn-primary-text',   'default' => '#FFFFFF'],
            'btn_secondary_bg'    => ['type' => 'color', 'label' => 'Secondary BG',      'css_var' => '--btn-secondary-bg',   'default' => ''],
            'btn_secondary_text'  => ['type' => 'color', 'label' => 'Secondary Text',    'css_var' => '--btn-secondary-text', 'default' => ''],
            'btn_hover_opacity'   => ['type' => 'text',  'label' => 'Hover Opacity',     'css_var' => '--btn-hover-opacity',  'default' => '0.9'],
        ],

        'header' => [
            'header_sticky'           => ['type' => 'boolean',  'label' => 'Sticky Header',          'default' => '0'],
            'header_transparent'      => ['type' => 'boolean',  'label' => 'Transparent Header',     'default' => '0'],
            'header_height'           => ['type' => 'text',     'label' => 'Header Height',          'css_var' => '--header-height', 'default' => '70px'],
            'header_topbar'           => ['type' => 'boolean',  'label' => 'Top Bar Enabled',        'default' => '0'],
            'header_search'           => ['type' => 'boolean',  'label' => 'Search Enable',          'default' => '0'],
            'header_cta_text'         => ['type' => 'text',     'label' => 'CTA Button Text',        'default' => ''],
            'header_cta_url'          => ['type' => 'text',     'label' => 'CTA Button URL',         'default' => ''],
            'announcement_enabled'    => ['type' => 'boolean',  'label' => 'Announcement Bar',       'default' => '0'],
            'announcement_text'       => ['type' => 'textarea', 'label' => 'Announcement Text',      'default' => ''],
            'announcement_bg_color'   => ['type' => 'color',    'label' => 'Announcement BG Color',  'default' => '#1D4ED8'],
            'announcement_text_color' => ['type' => 'color',    'label' => 'Announcement Text Color','default' => '#FFFFFF'],
        ],

        'footer' => [
            'footer_layout'      => ['type' => 'select',   'label' => 'Footer Layout',       'default' => 'standard'],
            'footer_copyright'   => ['type' => 'textarea', 'label' => 'Copyright Text',      'default' => ''],
            'footer_newsletter'  => ['type' => 'boolean',  'label' => 'Newsletter Enable',   'default' => '0'],
            'footer_social'      => ['type' => 'boolean',  'label' => 'Social Icons',        'default' => '1'],
            'footer_back_to_top' => ['type' => 'boolean',  'label' => 'Back To Top Button',  'default' => '1'],
        ],

        'business' => [
            'biz_name'      => ['type' => 'text',     'label' => 'Business Name',    'default' => ''],
            'biz_phone'     => ['type' => 'text',     'label' => 'Phone',            'default' => ''],
            'biz_email'     => ['type' => 'text',     'label' => 'Email',            'default' => ''],
            'biz_address'   => ['type' => 'textarea', 'label' => 'Address',          'default' => ''],
            'biz_maps_url'  => ['type' => 'text',     'label' => 'Google Maps URL',  'default' => ''],
            'biz_hours'     => ['type' => 'textarea', 'label' => 'Business Hours',   'default' => ''],
            'biz_emergency' => ['type' => 'text',     'label' => 'Emergency Number', 'default' => ''],
        ],

        'social' => [
            'social_facebook'  => ['type' => 'text', 'label' => 'Facebook',    'default' => ''],
            'social_instagram' => ['type' => 'text', 'label' => 'Instagram',   'default' => ''],
            'social_linkedin'  => ['type' => 'text', 'label' => 'LinkedIn',    'default' => ''],
            'social_youtube'   => ['type' => 'text', 'label' => 'YouTube',     'default' => ''],
            'social_x'         => ['type' => 'text', 'label' => 'X (Twitter)', 'default' => ''],
            'social_tiktok'    => ['type' => 'text', 'label' => 'TikTok',      'default' => ''],
            'social_pinterest' => ['type' => 'text', 'label' => 'Pinterest',   'default' => ''],
            'social_threads'   => ['type' => 'text', 'label' => 'Threads',     'default' => ''],
        ],

        'custom_code' => [
            'custom_css'        => ['type' => 'code', 'label' => 'Custom CSS',           'default' => ''],
            'head_scripts'      => ['type' => 'code', 'label' => 'Header Scripts',       'default' => ''],
            'footer_scripts'    => ['type' => 'code', 'label' => 'Footer Scripts',       'default' => ''],
            'ga_id'             => ['type' => 'text', 'label' => 'Google Analytics ID',  'default' => ''],
            'gtm_id'            => ['type' => 'text', 'label' => 'Google Tag Manager ID','default' => ''],
            'fb_pixel_id'       => ['type' => 'text', 'label' => 'Facebook Pixel ID',    'default' => ''],
            'meta_verification' => ['type' => 'text', 'label' => 'Meta Verification Tag','default' => ''],
        ],
    ];

    // ── Public API ──────────────────────────────────────────────────────────

    /**
     * All theme settings as key => value, with defaults for missing keys.
     */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $db = WebsiteThemeSetting::pluck('value', 'key')->all();
            $merged = [];
            foreach (self::$schema as $group => $fields) {
                foreach ($fields as $key => $cfg) {
                    $merged[$key] = array_key_exists($key, $db) ? $db[$key] : $cfg['default'];
                }
            }
            return $merged;
        });
    }

    /**
     * Single setting value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    /**
     * All settings for a specific group as key => value.
     */
    public function group(string $group): array
    {
        $all    = $this->all();
        $schema = self::$schema[$group] ?? [];
        $result = [];
        foreach ($schema as $key => $cfg) {
            $result[$key] = $all[$key] ?? $cfg['default'];
        }
        return $result;
    }

    /**
     * Bulk-save settings for a group. Only keys present in the schema are written.
     * Boolean fields: normalise checkbox-absent (null) → '0'.
     */
    public function saveGroup(string $group, array $data): void
    {
        $schema = self::$schema[$group] ?? [];

        foreach ($schema as $key => $cfg) {
            // Boolean fields: unchecked checkbox sends no value, treat as '0'
            if ($cfg['type'] === 'boolean') {
                $value = isset($data[$key]) ? '1' : '0';
            } else {
                // Skip keys not submitted (e.g. media fields with no change)
                if (!array_key_exists($key, $data)) continue;
                $value = $data[$key] === '' ? null : $data[$key];
            }

            WebsiteThemeSetting::updateOrCreate(
                ['key'   => $key],
                ['value' => $value, 'group' => $group]
            );
        }

        $this->clearCache();
    }

    /**
     * Resolve a media-type setting to its public URL.
     */
    public function imageUrl(string $key): ?string
    {
        $mediaId = $this->get($key);
        if (!$mediaId) return null;
        return \App\Models\Global\Media::find($mediaId)?->url;
    }

    /**
     * Generate CSS :root block with all variables that have a 'css_var' mapping.
     */
    public function cssVariables(): string
    {
        return Cache::remember(self::CACHE_CSS_KEY, self::CACHE_TTL, function () {
            $settings = $this->all();
            $lines    = [':root {'];

            foreach (self::$schema as $group => $fields) {
                foreach ($fields as $key => $cfg) {
                    if (empty($cfg['css_var'])) continue;
                    $value = $settings[$key] ?? $cfg['default'];
                    if ($value === null || $value === '') continue;
                    $lines[] = "  {$cfg['css_var']}: " . e($value) . ";";
                }
            }

            $lines[] = '}';
            return implode("\n", $lines);
        });
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::CACHE_CSS_KEY);
    }

    /**
     * All valid group names.
     */
    public static function groups(): array
    {
        return array_keys(self::$schema);
    }

    /**
     * Schema for a single group (for building forms dynamically).
     */
    public static function groupSchema(string $group): array
    {
        return self::$schema[$group] ?? [];
    }
}
