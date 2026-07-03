<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\ThemeBuilder;

use App\Http\Controllers\Controller;
use App\Services\Website\ThemeService;

class CssVariablesController extends Controller
{
    public function __construct(private ThemeService $theme) {}

    /**
     * Returns a CSS file containing :root custom property declarations.
     * Include in any HTML document:
     *   <link rel="stylesheet" href="{{ route('admin.website-management.theme-builder.css-variables') }}">
     * Or inject inline:
     *   <style>{!! app(ThemeService::class)->cssVariables() !!}</style>
     */
    public function __invoke()
    {
        $css = $this->theme->cssVariables();

        return response($css, 200)
            ->header('Content-Type', 'text/css; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
