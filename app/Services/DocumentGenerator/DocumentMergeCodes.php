<?php

namespace App\Services\DocumentGenerator;

use App\Helpers\ConfigurationHelper;
use App\Models\Stores\Store;
use Illuminate\Support\Carbon;

/**
 * Reusable merge-code resolver for Document Generator text blocks (titles,
 * disclaimers, footers, final-page text). Values come from Company Settings,
 * live Store records, structured hours of operation, and the generation
 * timestamp — never from hardcoded company text.
 *
 * Output safety: every replacement value is PLAIN TEXT. Callers render the
 * result through Blade escaping (documents display these blocks with
 * white-space: pre-line), so merge values cannot inject markup or scripts.
 * Unknown merge codes are left visible so admins can spot typos — they never
 * break generation.
 */
class DocumentMergeCodes
{
    /** Merge code => admin-facing description (shown next to editors). */
    public static function available(): array
    {
        return [
            'company_name'       => 'Company display name (Company Settings)',
            'main_url'           => 'Main website URL (Company Settings)',
            'main_phone'         => 'Main phone number (Company Settings)',
            'sales_phone'        => 'Sales phone number (Company Settings; falls back to main phone)',
            'store_locations'    => 'Active store names, addresses, and phones (Store records)',
            'store_hours'        => 'Primary store hours of operation (or the fallback hours setting)',
            'generated_date'     => 'Date the document was generated, e.g. ' . now()->format('M j, Y'),
            'generated_datetime' => 'Date and time the document was generated',
        ];
    }

    /** Replace supported merge codes in raw text. Unknown codes stay visible. */
    public static function apply(?string $text, ?Carbon $generatedAt = null): ?string
    {
        if ($text === null || $text === '') {
            return $text;
        }

        $values = self::values($generatedAt);

        return preg_replace_callback(
            '/\{\{\s*([A-Za-z0-9_]+)\s*\}\}/',
            fn (array $match) => array_key_exists($match[1], $values) ? $values[$match[1]] : $match[0],
            $text
        );
    }

    /** All merge values as plain text, resolved fresh at generation time. */
    public static function values(?Carbon $generatedAt = null): array
    {
        $generatedAt ??= now();

        return [
            'company_name'       => self::companyName(),
            'main_url'           => self::mainUrl(),
            'main_phone'         => self::mainPhone(),
            'sales_phone'        => self::salesPhone(),
            'store_locations'    => self::storeLocationsText(),
            'store_hours'        => implode("\n", self::storeHoursLines()),
            'generated_date'     => $generatedAt->format('M j, Y'),
            'generated_datetime' => $generatedAt->format('M j, Y g:i A'),
        ];
    }

    // ── Company Settings values ────────────────────────────────────

    public static function companyName(): string
    {
        return self::setting('company_name') ?: config('app.name', '');
    }

    public static function mainUrl(): string
    {
        return self::setting('main_url') ?: '';
    }

    public static function mainPhone(): string
    {
        // Fallback: the primary active store's phone, so documents stay
        // usable before the setting is filled in.
        return self::setting('company_main_phone')
            ?: (string) (self::activeStores()->firstWhere('is_primary', 'Yes')?->phone
                ?? self::activeStores()->first()?->phone);
    }

    public static function salesPhone(): string
    {
        return self::setting('company_sales_phone') ?: self::mainPhone();
    }

    // ── Store-derived values ───────────────────────────────────────

    /** One line per active store: name — address, city ZIP — phone. */
    public static function storeLocationsText(): string
    {
        return self::activeStores()
            ->map(function (Store $store) {
                $address = trim(implode(', ', array_filter([
                    $store->address,
                    trim($store->city . ' ' . $store->zip_code),
                ])));

                return implode(' — ', array_filter([$store->store_name, $address, $store->phone]));
            })
            ->implode("\n");
    }

    /**
     * Hours lines for the primary active store from its structured
     * hours_of_operation rows, with consecutive same-hours days compressed
     * ("Monday–Friday: 7:00 AM–5:00 PM"). Stores without structured hours
     * fall back to the store_hours_fallback setting text.
     */
    public static function storeHoursLines(): array
    {
        $store = self::activeStores()->firstWhere('is_primary', 'Yes')
            ?? self::activeStores()->first();

        $days  = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $hours = $store?->hours()->get()->keyBy('day_name');

        if (!$hours || $hours->isEmpty()) {
            $fallback = (string) self::setting('store_hours_fallback');

            return array_values(array_filter(array_map('trim', explode("\n", $fallback))));
        }

        // Format each day, then compress consecutive days sharing hours.
        $formatted = [];
        foreach ($days as $day) {
            $row = $hours->get($day);

            $formatted[$day] = (!$row || $row->is_closed)
                ? 'Closed'
                : Carbon::parse($row->start_time)->format('g:i A')
                    . '–' . Carbon::parse($row->end_time)->format('g:i A');
        }

        $lines = [];
        $runStart = null;
        $runValue = null;

        foreach ($days as $i => $day) {
            if ($formatted[$day] === $runValue) {
                continue;
            }
            if ($runStart !== null) {
                $lines[] = self::hoursLine($days, $runStart, $i - 1, $runValue);
            }
            $runStart = $i;
            $runValue = $formatted[$day];
        }
        $lines[] = self::hoursLine($days, $runStart, count($days) - 1, $runValue);

        return $lines;
    }

    private static function hoursLine(array $days, int $from, int $to, string $value): string
    {
        $label = $from === $to ? $days[$from] : $days[$from] . '–' . $days[$to];

        return $label . ': ' . $value;
    }

    // ── Internals ──────────────────────────────────────────────────

    private static function setting(string $key): ?string
    {
        $value = ConfigurationHelper::getSettings('Company Settings', $key);

        return $value !== null ? trim((string) $value) : null;
    }

    private static function activeStores()
    {
        return Store::query()
            ->where('status', 'Active')
            ->orderByDesc('is_primary')
            ->orderBy('store_name')
            ->get(['id', 'store_name', 'phone', 'address', 'city', 'zip_code', 'is_primary']);
    }
}
