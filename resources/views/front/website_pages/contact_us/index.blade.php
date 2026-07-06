@extends('front.layouts.app')

@section('title', $currentPage->meta_title ?: $currentPage->title)

@push('meta')
    @if($currentPage->meta_description)
        <meta name="description" content="{{ $currentPage->meta_description }}">
    @endif
    @if($currentPage->og_title)
        <meta property="og:title"       content="{{ $currentPage->og_title }}">
        <meta name="twitter:title"      content="{{ $currentPage->og_title }}">
    @endif
    @if($currentPage->canonical_url)
        <link rel="canonical" href="{{ $currentPage->canonical_url }}">
    @endif
@endpush

@php
$sectionPartialMap = [
    'hero'         => 'front.website_pages.contact_us.partials.hero',
    'contact_strip'=> 'front.website_pages.contact_us.partials.contact-strip',
    'locations'    => 'front.website_pages.contact_us.partials.locations',
    'question_cta' => 'front.website_pages.contact_us.partials.question-cta',
    'feature_strip'=> 'front.website_pages.contact_us.partials.feature-strip',
];
$renderedKeys = [];

// Determine if the Locations/Stores section is active so the contact strip
// can hide store cards when the Stores section is turned off.
$locationsSection = collect($allSectionsOrdered)
    ->first(fn($s) => ($s->section_type ?? $s->section_key) === 'locations');
$locationsActive = $locationsSection && $locationsSection->status === 'Active';
@endphp

@section('content')

{{-- DB-ordered sections (respects builder drag-drop order) --}}
@foreach($allSectionsOrdered as $sec)
    @php
        $key = $sec->section_type ?? $sec->section_key;
        // Always mark a DB-known section as handled — even when Inactive — so the
        // fallback loop below never re-renders a section that the admin turned off.
        if (isset($sectionPartialMap[$key]) || $key === 'feature_strip') {
            $renderedKeys[] = $key;
        }
    @endphp
    @if($key === 'feature_strip')
        {{-- Feature strip always uses home builder data, not contact_v2 --}}
        @include('front.website_pages.contact_us.partials.feature-strip', [
            'section' => $homeFeatureSection,
            'items'   => $homeFeatureItems,
        ])
    @elseif($key === 'contact_strip' && $sec->status === 'Active')
        @include($sectionPartialMap[$key], [
            'section'        => $sec,
            'items'          => $sec->items,
            'showStoreCards' => $locationsActive,
        ])
    @elseif(isset($sectionPartialMap[$key]) && $sec->status === 'Active')
        @include($sectionPartialMap[$key], ['section' => $sec, 'items' => $sec->items])
    @endif
@endforeach

{{-- Fallback: any mapped partial not yet in DB still renders --}}
@foreach($sectionPartialMap as $key => $partial)
    @if(!in_array($key, $renderedKeys) && \Illuminate\Support\Facades\View::exists($partial))
        @if($key === 'feature_strip')
            @include($partial, ['section' => $homeFeatureSection, 'items' => $homeFeatureItems])
        @else
            @include($partial, ['section' => null, 'items' => collect()])
        @endif
    @endif
@endforeach

@endsection
