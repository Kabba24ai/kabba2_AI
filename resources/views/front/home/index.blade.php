@extends('front.layouts.app')

{{-- ── SEO: title, meta tags, OG, canonical ─────────────────────────── --}}
@if($hp->seo->metaTitle)
@section('full_title', $hp->seo->metaTitle)
@else
@section('title', $title)
@endif

@if($hp->seo->canonicalUrl)
@section('canonical', $hp->seo->canonicalUrl)
@endif

@push('meta')
    {{-- Blank OG fields inherit the Meta values; og:image falls back to the
         homepage hero, then the site's default social image (SeoResolver) --}}
    @include('front.partials.page_meta', [
        'seoRaw' => [
            'meta_title'       => $hp->seo->metaTitle,
            'meta_description' => $hp->seo->metaDescription,
            'meta_keywords'    => $hp->seo->metaKeywords,
            'og_title'         => $hp->seo->ogTitle,
            'og_description'   => $hp->seo->ogDescription,
            'og_image_url'     => $hp->seo->ogImage,
            'canonical_url'    => $hp->seo->canonicalUrl,
        ],
        'fallbackImage' => $hp->hero->imageUrl,
    ])
@endpush

@section('content')

@php
/**
 * Section key → partial path mapping.
 * Sections in the DB builder render in their saved display_order.
 * Any registered partials that have no DB record yet render afterward
 * in their original order, so the homepage never silently loses content.
 */
$sectionPartialMap = [
    'hero'               => 'front.home.partials.hero',
    'contact_strip'      => 'front.home.partials.contact-strip',
    'rental_specialists' => 'front.home.partials.rental-specialists',
    'featured_rentals'   => 'front.home.partials.featured-categories',
    // feature_strip intentionally absent: it is a global component now,
    // rendered by the layout directly above the global footer
    'newsletter'         => 'front.home.partials.newsletter',
];
$renderedKeys = [];
@endphp

{{-- DB-ordered sections (active, sorted by display_order from builder) --}}
@foreach($hp->orderedSections as $sectionKey)
    @if(isset($sectionPartialMap[$sectionKey]))
        @include($sectionPartialMap[$sectionKey])
        @php $renderedKeys[] = $sectionKey; @endphp
    @endif
@endforeach

{{-- Fallback: partials not yet managed by the builder render at the end --}}
@foreach($sectionPartialMap as $key => $partial)
    @if(!in_array($key, $renderedKeys) && \Illuminate\Support\Facades\View::exists($partial))
        @include($partial)
    @endif
@endforeach

@endsection
