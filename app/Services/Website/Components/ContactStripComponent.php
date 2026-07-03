<?php

namespace App\Services\Website\Components;

class ContactStripComponent extends AbstractSectionComponent
{
    public function key(): string         { return 'contact_strip'; }
    public function displayName(): string { return 'Contact Strip'; }
    public function icon(): string        { return 'heroicon-o-phone'; }
    public function description(): string { return 'Horizontal strip with phone, store locations, and equipment search.'; }

    public function adminView(): string   { return 'admin.website_management.home_page_builder.partials._contact_strip'; }

    public function sortableIds(): array  { return ['sortable-contact-strip']; }

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
            'section' => ['title' => 'Contact Our Specialists', 'subtitle' => 'Need help finding the right equipment?'],
            'items'   => [],
        ];
    }
}
