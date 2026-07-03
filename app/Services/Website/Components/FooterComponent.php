<?php

namespace App\Services\Website\Components;

class FooterComponent extends AbstractSectionComponent
{
    public function key(): string         { return 'footer'; }
    public function displayName(): string { return 'Footer'; }
    public function icon(): string        { return 'heroicon-o-minus'; }
    public function description(): string { return 'Page footer with copyright, quick links, other links, and social links.'; }

    public function adminView(): string   { return 'admin.website_management.home_page_builder.partials._footer'; }

    public function sortableIds(): array
    {
        return ['sortable-footer-quick', 'sortable-footer-other', 'sortable-footer-social'];
    }

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
            'section' => [
                'content' => ['copyright_text' => '© ' . date('Y') . ' Your Company. All rights reserved.', 'powered_by_text' => ''],
            ],
            'items' => [],
        ];
    }
}
