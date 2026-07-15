<?php

namespace App\Helpers;

use App\Models\Configurations\Setting;

/**
 * Canonical maps for the six administrative delivery tiers.
 *
 * System Configuration (settings, type 'Product Settings') is the
 * authoritative pricing source; products hold COPIED per-tier rates linked
 * by their truck_fee_size_setting size. "Custom 1–4" are administrative
 * labels only — customers eventually see the configured distances.
 */
class DeliveryTierHelper
{
    public const TIERS = ['standard', 'extended', 'custom_1', 'custom_2', 'custom_3', 'custom_4'];

    public const CUSTOM_TIERS = ['custom_1', 'custom_2', 'custom_3', 'custom_4'];

    /** products.truck_fee_size_setting value => setting-key prefix */
    public const SIZE_MAP = [
        'Small'      => 'small',
        'Medium'     => 'medium',
        'Large'      => 'large',
        'X-Large'    => 'x_large',
        '2X-Large'   => '2x_large',
        'Commercial' => 'commercial',
    ];

    /** products column holding the copied one-way rate for a tier */
    public static function productColumn(string $tier): string
    {
        return "{$tier}_delivery_fee";
    }

    /** Product Settings key for a size + tier one-way rate */
    public static function feeSettingKey(string $sizePrefix, string $tier): string
    {
        return "{$sizePrefix}_{$tier}_delivery_fee";
    }

    /**
     * Resolve the four Custom fee columns for a product create/update.
     *
     * Precedence: a submitted value always wins — the form submits all four
     * fields, a blank arrives as NULL (ConvertEmptyStringsToNull) and stays
     * NULL. Only a field absent from the request entirely (disabled JS that
     * drops fields, API-like callers, tests) falls back to the current
     * global rate for the selected size, so alternate paths cannot create
     * inconsistent data. A later Product Settings save still overwrites via
     * the propagation rule regardless of what was saved here.
     */
    public static function resolveCustomFees(array $validated): array
    {
        $fallback = self::customFeeColumnsForSize($validated['truck_fee_size_setting'] ?? null);

        $resolved = [];
        foreach ($fallback as $column => $globalValue) {
            $resolved[$column] = array_key_exists($column, $validated) ? $validated[$column] : $globalValue;
        }

        return $resolved;
    }

    /**
     * Customer-facing availability of the Custom tiers for one product.
     *
     * A tier is available only when BOTH its global distance value and the
     * product's copied one-way rate are non-null. A 0.00 rate is a valid,
     * intentionally free option. Order is always custom_1..custom_4 —
     * administrators may configure any values, so no numeric sorting.
     * Customers never see the tier identifiers, only the distances.
     *
     * @param  \App\Models\ProductManagement\Product|object  $product
     * @param  array  $productSettings  ConfigurationHelper::getSettings('Product Settings')
     * @return array<int, array{tier: string, distance: string, unit: string, one_way_rate: float}>
     */
    public static function availableCustomTiersForProduct($product, array $productSettings): array
    {
        $unit = (string) ($productSettings['distance_unit'] ?? '');

        $available = [];
        foreach (self::CUSTOM_TIERS as $tier) {
            $distance = $productSettings["{$tier}_delivery_range"] ?? null;
            $rate = $product->{self::productColumn($tier)} ?? null;

            if ($distance === null || $distance === '' || $rate === null || $rate === '') {
                continue;
            }

            $available[] = [
                'tier'         => $tier,
                'distance'     => (string) $distance,
                'unit'         => $unit,
                'one_way_rate' => floatval($rate),
            ];
        }

        return $available;
    }

    /**
     * Current global Custom-tier rates for a product size, keyed by the
     * products column they populate. Blank/NULL stays NULL (tier not
     * configured); an explicit "0" becomes 0.0 (intentionally free).
     */
    public static function customFeeColumnsForSize(?string $truckFeeSizeSetting): array
    {
        $prefix = self::SIZE_MAP[$truckFeeSizeSetting] ?? null;

        $columns = [];
        foreach (self::CUSTOM_TIERS as $tier) {
            $columns[self::productColumn($tier)] = null;
        }

        if ($prefix === null) {
            return $columns;
        }

        $settingKeys = [];
        foreach (self::CUSTOM_TIERS as $tier) {
            $settingKeys[self::feeSettingKey($prefix, $tier)] = self::productColumn($tier);
        }

        $values = Setting::where('setting_type', 'Product Settings')
            ->whereIn('setting_name', array_keys($settingKeys))
            ->pluck('setting_value', 'setting_name');

        foreach ($settingKeys as $settingKey => $column) {
            $value = $values[$settingKey] ?? null;
            $columns[$column] = ($value === null || $value === '') ? null : floatval($value);
        }

        return $columns;
    }
}
