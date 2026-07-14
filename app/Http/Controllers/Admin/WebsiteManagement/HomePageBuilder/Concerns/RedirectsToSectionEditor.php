<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\HomePageBuilder\Concerns;

use Illuminate\Http\RedirectResponse;

trait RedirectsToSectionEditor
{
    /**
     * Footer and Feature Strip are global site components — their editor lives
     * on Website Management → Footer, not in the Home Page Builder tabs.
     * Every other section returns to its Home Page Builder tab.
     */
    protected function redirectToSectionEditor(?string $sectionKey): RedirectResponse
    {
        if (in_array($sectionKey, ['footer', 'feature_strip'], true)) {
            return redirect()->route('admin.website-management.footer.index');
        }

        return redirect()->route('admin.website-management.home-builder.index', ['tab' => $sectionKey ?? 'hero']);
    }
}
