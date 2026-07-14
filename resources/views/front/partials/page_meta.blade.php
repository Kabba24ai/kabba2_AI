{{-- ============================================================
     Shared page metadata — description, Open Graph, Twitter card.
     Include inside @push('meta') with:
       'seoRaw'        => [meta_title, meta_description, meta_keywords,
                           og_title, og_description, og_image_url,
                           canonical_url]  (raw stored values)
       'fallbackImage' => page-specific og:image fallback URL (optional)
     Fallbacks resolve via SeoResolver at render time — blank OG fields
     inherit the Meta values, and og:image always resolves to a real
     image (never empty markup).
============================================================ --}}
@php
    $meta = app(\App\Services\Website\SeoResolver::class)
        ->resolve($seoRaw ?? [], $fallbackImage ?? null);
@endphp

@if($meta->metaDescription)
<meta name="description" content="{{ $meta->metaDescription }}">
@endif
@if($meta->metaKeywords)
<meta name="keywords" content="{{ $meta->metaKeywords }}">
@endif

@if($meta->ogTitle)
<meta property="og:title" content="{{ $meta->ogTitle }}">
@endif
@if($meta->ogDescription)
<meta property="og:description" content="{{ $meta->ogDescription }}">
@endif
@if($meta->ogImage)
<meta property="og:image" content="{{ $meta->ogImage }}">
@endif
<meta property="og:type" content="website">
<meta property="og:url" content="{{ $meta->canonicalUrl ?? url()->current() }}">

<meta name="twitter:card" content="summary_large_image">
@if($meta->ogTitle)
<meta name="twitter:title" content="{{ $meta->ogTitle }}">
@endif
@if($meta->ogDescription)
<meta name="twitter:description" content="{{ $meta->ogDescription }}">
@endif
@if($meta->ogImage)
<meta name="twitter:image" content="{{ $meta->ogImage }}">
@endif
