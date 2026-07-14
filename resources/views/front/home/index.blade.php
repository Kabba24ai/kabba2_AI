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
    @if($hp->seo->metaDescription)
    <meta name="description" content="{{ $hp->seo->metaDescription }}">
    @endif
    @if($hp->seo->metaKeywords)
    <meta name="keywords" content="{{ $hp->seo->metaKeywords }}">
    @endif
    @if($hp->seo->ogTitle)
    <meta property="og:title" content="{{ $hp->seo->ogTitle }}">
    @endif
    @if($hp->seo->ogDescription)
    <meta property="og:description" content="{{ $hp->seo->ogDescription }}">
    @endif
    @if($hp->seo->ogImage)
    <meta property="og:image" content="{{ $hp->seo->ogImage }}">
    @endif
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $hp->seo->canonicalUrl ?? url()->current() }}">
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
