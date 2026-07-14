<?php

namespace App\Services\Website;

use App\Helpers\ConfigurationHelper;

/**
 * Resolves the FINAL SEO / Open Graph values for a public page.
 *
 * Admins enter Meta values once; the OG fields are optional overrides:
 *   og:title        → OG Title,        else Meta Title
 *   og:description  → OG Description,  else Meta Description
 *   og:image        → OG Image,        else the page's fallback image
 *                     (e.g. homepage hero), else the site's designated
 *                     Default OG Image (Theme Builder), else the logo.
 *
 * Fallbacks are resolved at RENDER time — stored values are never
 * copied into the OG columns, so later Meta edits flow through.
 */
class SeoResolver
{
    public function __construct(private ThemeService $theme) {}

    /**
     * @param array $raw  Raw stored values:
     *        meta_title, meta_description, meta_keywords,
     *        og_title, og_description, og_image_url, canonical_url
     * @param string|null $pageFallbackImage  Page-specific og:image fallback
     *        (absolute URL), used before the site-wide default.
     */
    public function resolve(array $raw, ?string $pageFallbackImage = null): object
    {
        $metaTitle       = $raw['meta_title'] ?? null;
        $metaDescription = $raw['meta_description'] ?? null;

        return (object) [
            'metaTitle'       => $metaTitle,
            'metaDescription' => $metaDescription,
            'metaKeywords'    => $raw['meta_keywords'] ?? null,
            'ogTitle'         => filled($raw['og_title'] ?? null) ? $raw['og_title'] : $metaTitle,
            'ogDescription'   => filled($raw['og_description'] ?? null) ? $raw['og_description'] : $metaDescription,
            'ogImage'         => filled($raw['og_image_url'] ?? null)
                                    ? $raw['og_image_url']
                                    : ($pageFallbackImage ?: $this->defaultSocialImage()),
            'canonicalUrl'    => $raw['canonical_url'] ?? null,
        ];
    }

    /**
     * The site's canonical social-sharing fallback image:
     * Theme Builder's "Default OG Image", else the approved site logo.
     */
    public function defaultSocialImage(): ?string
    {
        return $this->theme->imageUrl('og_image_default')
            ?: ConfigurationHelper::getHpBuilderLogo()
            ?: ConfigurationHelper::getBrandingLogo();
    }
}
