@extends('front.layouts.app')

@section('title', $title)

@section('content')

    @include('front.home_v2.partials.hero')

    @include('front.home_v2.partials.contact-strip')

    @include('front.home_v2.partials.rental-specialists')

    @include('front.home_v2.partials.featured-categories')

    @include('front.home_v2.partials.features')

    @include('front.home_v2.partials.newsletter')

@endsection
