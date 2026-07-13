<?php

namespace Tests\Feature\WebsiteManagement;

use Tests\TestCase;

/**
 * Defect 2 — hero must not be force-stretched to a fixed 1920×450.
 *
 * The hero renders through an <img> element (verified), so the correct
 * behavior is expressed in CSS on that element: proportional height,
 * capped at 450px, cropped (never stretched). These are static markup
 * assertions against the compiled Blade partials — deploys do not run an
 * asset build, so the rule lives inline in the view, which is what we check.
 */
class HeroRenderingTest extends TestCase
{
    private string $publicHero;
    private string $adminHero;

    protected function setUp(): void
    {
        parent::setUp();
        $this->publicHero = file_get_contents(resource_path('views/front/home_v2/partials/hero.blade.php'));
        $this->adminHero  = file_get_contents(resource_path('views/admin/website_management/home_page_builder/partials/_hero.blade.php'));
    }

    /** @test */
    public function public_hero_is_not_forced_to_a_fixed_height(): void
    {
        // A fixed `height: 450px` / `min-height: 450px` — but NOT `max-height` — is the defect.
        $this->assertDoesNotMatchRegularExpression('/(?<!max-)height:\s*450px/', $this->publicHero,
            'public hero must not hard-set a 450px height');
        $this->assertStringNotContainsString('h-[450', $this->publicHero,
            'public hero must not use a fixed Tailwind height class');
    }

    /** @test */
    public function public_hero_uses_max_height_cap_and_natural_proportions(): void
    {
        // Proportional height (h-auto) + a 450px ceiling.
        $this->assertStringContainsString('h-auto', $this->publicHero);
        $this->assertStringContainsString('max-height: 450px', $this->publicHero);
    }

    /** @test */
    public function public_hero_never_uses_distorting_object_fit_fill(): void
    {
        $this->assertStringNotContainsString('object-fit: fill', $this->publicHero);
        $this->assertStringNotContainsString('object-fill', $this->publicHero);
        // Cropping (cover) is the approved non-distorting cap behavior.
        $this->assertStringContainsString('object-fit: cover', $this->publicHero);
    }

    /** @test */
    public function public_hero_preserves_text_and_overlay(): void
    {
        $this->assertStringContainsString('$hero->title', $this->publicHero);
        $this->assertStringContainsString('$hero->subtitle', $this->publicHero);
        $this->assertStringContainsString('overlayEnabled', $this->publicHero);
    }

    /** @test */
    public function admin_live_preview_matches_the_public_cap_rule(): void
    {
        // The builder preview must reflect what the site actually renders.
        $this->assertStringContainsString('max-height: 450px', $this->adminHero);
        $this->assertDoesNotMatchRegularExpression('/(?<!max-)height:\s*450px/', $this->adminHero);
    }
}
