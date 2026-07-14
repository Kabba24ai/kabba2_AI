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
