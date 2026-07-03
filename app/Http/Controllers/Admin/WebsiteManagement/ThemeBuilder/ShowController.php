<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\ThemeBuilder;

use App\Http\Controllers\Controller;
use App\Services\Website\ThemeService;

class ShowController extends Controller
{
    public function __construct(private ThemeService $theme) {}

    public function __invoke(string $tab = 'branding')
    {
        $validTabs = ThemeService::groups();
        if (!in_array($tab, $validTabs, true)) {
            return redirect()->route('admin.website-management.theme-builder.show', 'branding');
        }

        $theme     = $this->theme->all();
        $schema    = ThemeService::$schema;
        $activeTab = $tab;

        return view('admin.website_management.theme_builder.index',
            compact('theme', 'schema', 'activeTab'));
    }
}
