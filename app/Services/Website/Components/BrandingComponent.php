<?php

namespace App\Services\Website\Components;

class BrandingComponent extends AbstractSectionComponent
{
    public function key(): string         { return 'branding'; }
    public function displayName(): string { return 'Branding'; }
    public function icon(): string        { return 'heroicon-o-swatch'; }
    public function description(): string { return 'Logo and favicon for Home Page V2.'; }

    public function adminView(): string   { return 'admin.website_management.home_page_builder.partials._branding'; }

    public function viewData(array $context): array
    {
        return [];
    }

    public function showInPicker(): bool { return false; }
}
