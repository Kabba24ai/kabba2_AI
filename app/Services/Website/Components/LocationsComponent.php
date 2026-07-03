<?php

namespace App\Services\Website\Components;

class LocationsComponent extends AbstractSectionComponent
{
    public function key(): string         { return 'locations'; }
    public function displayName(): string { return 'Locations'; }
    public function icon(): string        { return 'heroicon-o-map-pin'; }
    public function description(): string { return 'Location cards with address, hours of operation, maps, call & text buttons.'; }

    public function adminView(): string   { return 'admin.website_management.home_page_builder.partials._locations'; }

    public function sortableIds(): array  { return ['sortable-locations']; }

    public function viewData(array $context): array
    {
        return [
            'section' => $this->section($context),
            'items'   => $this->items($context),
            'stores'  => $context['stores'],
        ];
    }

    public function defaultData(): array
    {
        return [
            'section' => [
                'title'    => 'Our Locations',
                'subtitle' => 'Visit us at one of our locations',
            ],
            'items' => [
                [
                    'item_key'     => 'location_1',
                    'title'        => 'Main Location',
                    'display_order' => 1,
                    'status'       => 'Active',
                    'content'      => [
                        'badge_color' => '#1F1D4E',
                        'address'     => '',
                        'city'        => '',
                        'state'       => '',
                        'zip'         => '',
                        'phone'       => '',
                        'maps_url'    => '',
                        'latitude'    => '',
                        'longitude'   => '',
                        'description' => '',
                        'store_id'    => null,
                    ],
                ],
            ],
        ];
    }
}
