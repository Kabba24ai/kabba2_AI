<?php

namespace App\Services\DocumentGenerator\Documents;

use App\Helpers\ConfigurationHelper;
use App\Models\ProductManagement\ProductCategory;
use App\Models\Stores\Store;
use App\Services\DocumentGenerator\AbstractDocument;
use App\Services\DocumentGenerator\DocumentMergeCodes;

/**
 * Dynamic Customer Price List — a take-home reference for walk-in customers
 * researching pricing. Informational only: never a quote, reservation,
 * estimate, or price guarantee. Website pricing governs.
 *
 * Rules: company-wide prices straight from live product records (nothing
 * hard-coded), all published products in the selected categories regardless
 * of availability, attachments treated as normal products, grouped by
 * category in website display order (category sort_order, then the
 * category's pivot product sort order). Names only — no models, brands,
 * images, or specs in Phase 1.
 */
class CustomerPriceListDocument extends AbstractDocument
{
    public const KEY = 'customer-price-list';

    public function key(): string
    {
        return self::KEY;
    }

    public function title(): string
    {
        // Admin-editable (Products → Price List → Document Text).
        $title = ConfigurationHelper::getSettings('Price List Settings', 'price_list_title');

        return trim((string) $title) !== '' ? $title : 'Rental Price List';
    }

    public function bodyView(): string
    {
        return 'admin.documents.bodies.price_list';
    }

    /** @param array{category_ids: int[]} $options */
    public function build(array $options): array
    {
        $categories = ProductCategory::query()
            ->whereIn('id', $options['category_ids'] ?? [])
            ->sortOrder()
            ->get()
            ->map(function (ProductCategory $category) {
                // Pivot sort order = the website/category display order.
                // Published products only; availability is deliberately ignored.
                $products = $category->products()->published()->get([
                    'products.id',
                    'products.product_name',
                    'products.rental_daily',
                    'products.rental_weekend',
                    'products.rental_weekly',
                    'products.rental_monthly',
                    'products.hide_rental_daily',
                    'products.hide_rental_weekend',
                    'products.hide_rental_weekly',
                    'products.hide_rental_monthly',
                    'products.standard_delivery_fee',
                    'products.extended_delivery_fee',
                ]);

                return ['category' => $category, 'products' => $products];
            })
            ->filter(fn ($group) => $group['products']->isNotEmpty())
            ->values();

        return ['groups' => $categories];
    }

    public function pageNotice(): ?string
    {
        // Notice repeats on every printed page; the website value is dynamic.
        return DocumentMergeCodes::apply(
            'Pricing shown is for reference only — current pricing on {{ main_url }} governs all rentals.',
            $this->generatedAt()
        );
    }

    public function disclaimer(): ?string
    {
        // Admin-editable text (Products → Price List → Document Text)
        // with merge codes resolved at generation time.
        $text = ConfigurationHelper::getSettings('Price List Settings', 'price_list_disclaimer');

        return DocumentMergeCodes::apply($text, $this->generatedAt()) ?: null;
    }

    public function generalInfo(): ?array
    {
        // Live store data — nothing hard-coded. Company phone comes from
        // Company Settings; hours come from the primary store's structured
        // hours of operation (fallback: the store_hours_fallback setting).
        $stores = Store::query()
            ->where('status', 'Active')
            ->orderByDesc('is_primary')
            ->orderBy('store_name')
            ->get(['store_name', 'phone', 'address', 'city', 'zip_code', 'is_primary']);

        $valueMessage = ConfigurationHelper::getSettings('Price List Settings', 'price_list_value_message');

        return [
            'phone_label'   => 'Main Sales',
            'phone'         => DocumentMergeCodes::mainPhone(),
            'stores'        => $stores,
            'hours'         => DocumentMergeCodes::storeHoursLines(),
            'value_message' => DocumentMergeCodes::apply($valueMessage, $this->generatedAt()),
        ];
    }
}
