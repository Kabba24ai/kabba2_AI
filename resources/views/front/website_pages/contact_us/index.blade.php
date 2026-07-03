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
@endphp

@section('content')

{{-- DB-ordered sections (respects builder drag-drop order) --}}
@foreach($allSectionsOrdered as $sec)
    @php $key = $sec->section_type ?? $sec->section_key; @endphp
    @if(isset($sectionPartialMap[$key]) && $sec->status === 'Active')
        @include($sectionPartialMap[$key], ['section' => $sec, 'items' => $sec->items])
        @php $renderedKeys[] = $key; @endphp
    @endif
@endforeach

{{-- Fallback: any mapped partial not yet in DB still renders --}}
@foreach($sectionPartialMap as $key => $partial)
    @if(!in_array($key, $renderedKeys) && \Illuminate\Support\Facades\View::exists($partial))
        @include($partial, ['section' => null, 'items' => collect()])
    @endif
@endforeach

@endsection
