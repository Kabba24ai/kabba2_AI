<?php

namespace App\Services\Website\Components;

class FeatureStripComponent extends AbstractSectionComponent
{
    public function key(): string         { return 'feature_strip'; }
    public function displayName(): string { return 'Feature Strip'; }
    public function icon(): string        { return 'heroicon-o-star'; }
    public function description(): string { return 'Dark strip with icon + title + subtitle feature highlights.'; }

    public function adminView(): string   { return 'admin.website_management.home_page_builder.partials._feature_strip'; }

    public function sortableIds(): array  { return ['sortable-feature-strip']; }

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
            'section' => ['title' => 'Why Choose Us'],
            'items'   => [
                ['item_key' => 'feature_1', 'title' => 'Quality Equipment', 'subtitle' => 'Well maintained and job-ready.', 'icon' => 'heroicon-o-shield-check', 'display_order' => 1, 'status' => 'Active'],
                ['item_key' => 'feature_2', 'title' => 'Fast Delivery',     'subtitle' => 'On time, every time.',           'icon' => 'heroicon-o-truck',        'display_order' => 2, 'status' => 'Active'],
                ['item_key' => 'feature_3', 'title' => 'Expert Support',    'subtitle' => 'Team on call when you need us.',  'icon' => 'heroicon-o-users',        'display_order' => 3, 'status' => 'Active'],
            ],
        ];
    }
}
