<?php

namespace App\Services\Website\Components;

class FeaturedRentalsComponent extends AbstractSectionComponent
{
    public function key(): string         { return 'featured_rentals'; }
    public function displayName(): string { return 'Featured Rentals'; }
    public function icon(): string        { return 'heroicon-o-tag'; }
    public function description(): string { return 'Curated grid of rental equipment categories.'; }

    public function adminView(): string   { return 'admin.website_management.home_page_builder.partials._featured_rentals'; }

    public function viewData(array $context): array
    {
        return [
            'section' => $this->section($context),
            'items'   => $this->items($context),
        ];
    }

    public function defaultData(): array
    {
        return [
            'section' => ['title' => 'FEATURED RENTALS', 'subtitle' => 'Browse our rental categories'],
            'items'   => [],
        ];
    }
}
