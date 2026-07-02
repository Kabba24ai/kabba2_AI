@extends('front.layouts.app')

@section('title', $title)

@section('content')

@php
/**
 * Section key → partial path mapping.
 * Sections in the DB builder render in their saved display_order.
 * Any registered partials that have no DB record yet render afterward
 * in their original order, so the homepage never silently loses content.
 */
$sectionPartialMap = [
    'hero'               => 'front.home_v2.partials.hero',
    'contact_strip'      => 'front.home_v2.partials.contact-strip',
    'rental_specialists' => 'front.home_v2.partials.rental-specialists',
    'featured_rentals'   => 'front.home_v2.partials.featured-categories',
    'feature_strip'      => 'front.home_v2.partials.features',
    'newsletter'         => 'front.home_v2.partials.newsletter',
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
