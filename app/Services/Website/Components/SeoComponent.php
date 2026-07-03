<?php

namespace App\Services\Website\Components;

class SeoComponent extends AbstractSectionComponent
{
    public function key(): string         { return 'seo'; }
    public function displayName(): string { return 'SEO'; }
    public function icon(): string        { return 'heroicon-o-magnifying-glass'; }
    public function description(): string { return 'Meta title, description, and Open Graph settings.'; }

    public function adminView(): string   { return 'admin.website_management.home_page_builder.partials._seo'; }

    public function viewData(array $context): array
    {
        return ['page' => $context['page']];
    }

    public function showInPicker(): bool { return false; }
}
