<?php

namespace App\Services\DocumentGenerator\Documents;

use App\Models\ProductManagement\ProductCategory;
use App\Models\Stores\Store;
use App\Services\DocumentGenerator\AbstractDocument;

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
        return 'Rental Price List';
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
        return 'Pricing shown is for reference only — current pricing on RentnKing.com governs all rentals.';
    }

    public function disclaimer(): ?string
    {
        return 'This price list is provided for general informational purposes only and is intended to help '
            . 'customers compare rental products and pricing at the time it was generated. Because rental '
            . 'equipment pricing can vary based on rental duration, delivery location, attachments, accessories, '
            . 'optional protection plans, taxes, fees, fuel, cleaning, damage, and other rental options, this '
            . 'printed price list cannot reflect every possible rental scenario.'
            . "\n\n"
            . 'Prices are subject to change at any time without notice. The pricing displayed on RentnKing.com '
            . 'at the time a reservation is created is the official rental price and supersedes any previously '
            . 'printed price list. This document is not a quote, estimate, reservation, or price guarantee and '
            . 'should not be relied upon as the final cost of a rental.'
            . "\n\n"
            . 'Final rental charges may vary based on the options selected and the specific details of the '
            . 'rental. For current pricing, product specifications, photos, and complete rental information, '
            . "visit RentnKing.com or contact Rent 'n King.";
    }

    public function generalInfo(): ?array
    {
        // Live store data — nothing hard-coded. Primary store carries the
        // main sales number; every active store is listed as a location.
        $stores = Store::query()
            ->where('status', 'Active')
            ->orderByDesc('is_primary')
            ->orderBy('store_name')
            ->get(['store_name', 'phone', 'address', 'city', 'zip_code', 'is_primary']);

        return [
            'phone_label'   => 'Main Sales',
            'phone'         => $stores->firstWhere('is_primary', 1)?->phone ?? $stores->first()?->phone,
            'stores'        => $stores,
            'hours'         => [
                'Monday–Friday' => '7:00 AM–5:00 PM',
                'Saturday'      => '7:00 AM–12:00 PM',
                'Sunday'        => 'Closed',
            ],
            'value_message' => 'Have a longer project? Ask about weekly and monthly rental options. In many '
                . 'cases, renting for the week gives you the best value — three days of rental often gets you '
                . 'seven days of use.',
        ];
    }
}
