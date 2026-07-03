<?php

namespace App\Services\Website\Components;

class HeroComponent extends AbstractSectionComponent
{
    public function key(): string         { return 'hero'; }
    public function displayName(): string { return 'Hero Banner'; }
    public function icon(): string        { return 'heroicon-o-photo'; }
    public function description(): string { return 'Full-width hero image with headline, subtitle, overlay, and CTA button.'; }

    public function adminView(): string   { return 'admin.website_management.home_page_builder.partials._hero'; }

    public function viewData(array $context): array
    {
        return ['section' => $this->section($context)];
    }

    public function defaultData(): array
    {
        return [
            'section' => [
                'title'    => 'Your Page Title',
                'subtitle' => 'Your page description here.',
                'content'  => ['overlay_enabled' => true, 'overlay_opacity' => 65, 'button_enabled' => false],
            ],
            'items' => [],
        ];
    }
}
