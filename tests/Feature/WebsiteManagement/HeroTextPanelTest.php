<?php

namespace Tests\Feature\WebsiteManagement;

use App\Models\WebsiteManagement\WebsitePage;
use App\Models\WebsiteManagement\WebsitePageSection;
use App\Services\Website\HomePageService;
use App\Services\Website\PageRevisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Hero text-readability panel (Home Builder → Hero → Text Readability).
 *
 * Fields live in website_page_sections.content JSON:
 *   text_background_enabled | text_background_style | text_background_strength
 * Surfaced through HomePageService::buildHero() and rendered by the public
 * hero partial + the admin live preview using one shared style mapping.
 */
class HeroTextPanelTest extends TestCase
{
    use RefreshDatabase;

    private function homeWithHero(array $content = [], ?string $title = 'THE RIGHT EQUIPMENT.'): WebsitePage
    {
        $page = WebsitePage::create([
            'page_key' => 'home', 'title' => 'Home', 'slug' => 'home',
            'status' => 'Active', 'publish_status' => WebsitePage::PUBLISH_STATUS_PUBLISHED,
        ]);
        WebsitePageSection::create([
            'website_page_id' => $page->id, 'section_key' => 'hero', 'section_name' => 'Hero',
            'title' => $title, 'subtitle' => 'Ready when you are.',
            'content' => $content, 'display_order' => 1, 'status' => 'Active',
        ]);
        return $page;
    }

    private function hero(): object
    {
        Cache::forget(HomePageService::CACHE_KEY);
        return app(HomePageService::class)->getData()->hero;
    }

    /** @test */
    public function existing_hero_without_fields_defaults_to_enabled_dark_medium(): void
    {
        $this->homeWithHero([]); // legacy section, no panel keys

        $hero = $this->hero();

        $this->assertTrue($hero->textBgEnabled, 'default must be an enabled panel (backward-compatible intentional default)');
        $this->assertSame('dark', $hero->textBgStyle);
        $this->assertSame('medium', $hero->textBgStrength);
    }

    /** @test */
    public function saved_settings_are_read_back_from_content(): void
    {
        $this->homeWithHero([
            'text_background_enabled'  => false,
            'text_background_style'    => 'light',
            'text_background_strength' => 'strong',
        ]);

        $hero = $this->hero();

        $this->assertFalse($hero->textBgEnabled);
        $this->assertSame('light', $hero->textBgStyle);
        $this->assertSame('strong', $hero->textBgStrength);
    }

    /** @test */
    public function invalid_stored_values_fall_back_to_safe_defaults(): void
    {
        $this->homeWithHero([
            'text_background_style'    => 'rainbow',
            'text_background_strength' => 'insane',
        ]);

        $hero = $this->hero();

        $this->assertSame('dark', $hero->textBgStyle);
        $this->assertSame('medium', $hero->textBgStrength);
    }

    /** @test */
    public function public_hero_renders_the_dark_panel_treatment(): void
    {
        $this->homeWithHero([
            'text_background_enabled' => true, 'text_background_style' => 'dark',
            'text_background_strength' => 'medium',
        ]);
        $hero = $this->hero();

        $html = view('front.home.partials.hero', ['hp' => (object) ['hero' => $hero]])->render();

        $this->assertStringContainsString('rgba(0,0,0,0.42)', $html, 'dark medium → 0.42 black');
        $this->assertStringContainsString('color: #ffffff', $html, 'dark panel → white text');
        $this->assertStringContainsString('backdrop-filter:blur(6px)', $html);
        $this->assertStringContainsString('THE RIGHT EQUIPMENT.', $html);
        $this->assertStringContainsString('Ready when you are.', $html);
    }

    /** @test */
    public function public_hero_renders_the_light_panel_treatment(): void
    {
        $this->homeWithHero([
            'text_background_enabled' => true, 'text_background_style' => 'light',
            'text_background_strength' => 'medium',
        ]);
        $html = view('front.home.partials.hero', ['hp' => (object) ['hero' => $this->hero()]])->render();

        $this->assertStringContainsString('rgba(255,255,255,0.42)', $html);
        $this->assertStringContainsString('color: #111827', $html, 'light panel → dark text');
    }

    /** @test */
    public function strength_only_changes_opacity(): void
    {
        $page = $this->homeWithHero(['text_background_strength' => 'light']);

        foreach (['light' => '0.28', 'medium' => '0.42', 'strong' => '0.6'] as $strength => $op) {
            WebsitePageSection::where('website_page_id', $page->id)->where('section_key', 'hero')
                ->update(['content' => ['text_background_strength' => $strength]]);

            $html = view('front.home.partials.hero', ['hp' => (object) ['hero' => $this->hero()]])->render();
            $this->assertStringContainsString("rgba(0,0,0,{$op})", $html, "strength {$strength} → opacity {$op}");
            // layout dimensions are constant across strengths
            $this->assertStringContainsString('padding:16px 20px', $html);
        }
    }

    /** @test */
    public function disabled_panel_leaves_no_panel_background_and_keeps_configured_colors(): void
    {
        $this->homeWithHero([
            'text_background_enabled' => false,
            'title_color' => '#ff0000', 'subtitle_color' => '#00ff00',
        ]);
        $html = view('front.home.partials.hero', ['hp' => (object) ['hero' => $this->hero()]])->render();

        $this->assertStringNotContainsString('rgba(0,0,0,0.42)', $html, 'no panel background when disabled');
        $this->assertStringNotContainsString('backdrop-filter:blur', $html);
        $this->assertStringContainsString('color: #ff0000', $html, 'disabled → keep configured title color');
        $this->assertStringContainsString('color: #00ff00', $html, 'disabled → keep configured subtitle color');
    }

    /** @test */
    public function public_hero_never_restores_a_button(): void
    {
        $this->homeWithHero(['button_text' => 'Should Not Appear', 'button_url' => '/nope']);
        $html = view('front.home.partials.hero', ['hp' => (object) ['hero' => $this->hero()]])->render();

        $this->assertStringNotContainsString('Should Not Appear', $html);
        $this->assertStringNotContainsString('<a ', $html, 'hero must not render a button/link');
        $this->assertStringNotContainsString('<button', $html);
    }

    /** @test */
    public function revision_snapshot_and_restore_preserve_panel_settings(): void
    {
        $page = $this->homeWithHero([
            'text_background_enabled' => true, 'text_background_style' => 'light',
            'text_background_strength' => 'strong',
        ]);
        $revisions = app(PageRevisionService::class);

        // Snapshot the light/strong config.
        $rev = $revisions->createRevision($page->fresh()->load('sections.items'), 'light/strong');
        $this->assertStringContainsString('light', json_encode($rev->snapshot));
        $this->assertStringContainsString('strong', json_encode($rev->snapshot));

        // Change to dark/light, then restore the snapshot.
        WebsitePageSection::where('website_page_id', $page->id)->where('section_key', 'hero')
            ->update(['content' => ['text_background_style' => 'dark', 'text_background_strength' => 'light']]);

        $revisions->restoreRevision($rev->fresh());

        $content = WebsitePageSection::where('website_page_id', $page->id)
            ->where('section_key', 'hero')->value('content');
        $this->assertSame('light', $content['text_background_style'], 'restore reinstates the revision panel style');
        $this->assertSame('strong', $content['text_background_strength']);
    }

    /** @test */
    public function public_and_preview_share_the_same_opacity_and_color_mapping(): void
    {
        $public  = file_get_contents(resource_path('views/front/home/partials/hero.blade.php'));
        $preview = file_get_contents(resource_path('views/admin/website_management/home_page_builder/partials/_hero.blade.php'));

        foreach (['0.28', '0.42', '0.60'] as $op) {
            $this->assertStringContainsString($op, $public,  "public must define opacity {$op}");
            $this->assertStringContainsString($op, $preview, "preview must define opacity {$op}");
        }
        foreach (['#111827', '#ffffff'] as $c) {
            $this->assertStringContainsString($c, $public);
            $this->assertStringContainsString($c, $preview);
        }
    }
}
