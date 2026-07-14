@extends('front.layouts.app')

@section('title', $currentPage->meta_title ?: $currentPage->title)

@if($currentPage->canonical_url)
@section('canonical', $currentPage->canonical_url)
@endif

@push('meta')
    {{-- Blank OG fields inherit the Meta values; og:image falls back to the
         site's default social image (Theme Builder → Default OG Image, else logo) --}}
    @include('front.partials.page_meta', [
        'seoRaw' => [
            'meta_title'       => $currentPage->meta_title,
            'meta_description' => $currentPage->meta_description,
            'meta_keywords'    => $currentPage->meta_keywords,
            'og_title'         => $currentPage->og_title,
            'og_description'   => $currentPage->og_description,
            'og_image_url'     => $currentPage->og_image
                                    ? \App\Models\Global\Media::find($currentPage->og_image)?->url
                                    : null,
            'canonical_url'    => $currentPage->canonical_url,
        ],
    ])
@endpush

@php
$sectionPartialMap = [
    // hero intentionally absent: the contact page has no header band —
    //   historical hero rows stay in the DB but never render.
    // contact_strip is the GLOBAL shared strip (content from Home Page
    //   Builder → Contact Strip); the contact page only toggles it.
    'locations'    => 'front.website_pages.contact_us.partials.locations',
    'question_cta' => 'front.website_pages.contact_us.partials.question-cta',
    // feature_strip intentionally absent: it is a global component now,
    // rendered by the layout directly above the global footer
];
$renderedKeys = [];
$hasStripRow = collect($allSectionsOrdered)
    ->contains(fn($s) => ($s->section_type ?? $s->section_key) === 'contact_strip');
@endphp

@section('content')

{{-- Clearance for the fixed navbar only — the first section starts immediately below it --}}
<div class="pt-[4.2rem] lg:pt-16">

{{-- Fallback: pages without a contact_strip row show the shared strip by default, first --}}
@if(!$hasStripRow)
    @include('front.partials.contact_strip')
@endif

{{-- DB-ordered sections (respects builder drag-drop order) --}}
@foreach($allSectionsOrdered as $sec)
    @php
        $key = $sec->section_type ?? $sec->section_key;
        // Always mark a DB-known section as handled — even when Inactive — so the
        // fallback loop below never re-renders a section that the admin turned off.
        if (isset($sectionPartialMap[$key]) || $key === 'contact_strip') {
            $renderedKeys[] = $key;
        }
    @endphp
    @if($key === 'contact_strip' && $sec->status === 'Active')
        {{-- Shared global strip — same component and data source as the homepage --}}
        @include('front.partials.contact_strip')
    @elseif(isset($sectionPartialMap[$key]) && $sec->status === 'Active')
        @include($sectionPartialMap[$key], ['section' => $sec, 'items' => $sec->items])
    @endif
@endforeach

{{-- Fallback: any mapped partial not yet in DB still renders --}}
@foreach($sectionPartialMap as $key => $partial)
    @if(!in_array($key, $renderedKeys) && \Illuminate\Support\Facades\View::exists($partial))
        @include($partial, ['section' => null, 'items' => collect()])
    @endif
@endforeach

</div>

@endsection
