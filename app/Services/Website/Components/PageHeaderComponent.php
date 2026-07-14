<?php

namespace App\Services\Website\Components;

/**
 * Simple title/subtitle page header for interior pages.
 *
 * Shares the `hero` section_key so existing page data (title, subtitle,
 * content.description) is preserved — but the hero IMAGE belongs to the
 * homepage only, so this component exposes no image, overlay, or
 * readability controls.
 */
class PageHeaderComponent extends HeroComponent
{
    public function displayName(): string { return 'Page Header'; }
    public function icon(): string        { return 'heroicon-o-bars-3-bottom-left'; }
    public function description(): string { return 'Page title band with optional subtitle and description. No hero image — that is homepage-only.'; }

    public function adminView(): string   { return 'admin.website_management.contact_page_builder.partials._hero'; }

    public function showInPicker(): bool  { return false; }
}
