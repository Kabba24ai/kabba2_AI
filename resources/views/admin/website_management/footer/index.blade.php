@extends('admin.layouts.app', ['contentClass' => 'max-w-(--breakpoint-2xl)'])

@section('title', 'Footer & Global Sections')

@section('content')
<div class="bg-gray-50 flex flex-col">
    <div class="flex-1 overflow-auto">
        {{-- Header --}}
        <div class=" border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <x-heroicon-o-cog-8-tooth class="h-10 w-10" />
                    <div class="h-6 border-l border-gray-300"></div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Footer &amp; Global Sections</h1>
                        <p class="text-sm text-gray-600">The footer and feature strip shown on every public page, plus related site settings</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Content --}}

        <div>
            {{-- Tabs --}}
            @include('admin.partials.formErrors')
            @include('flash::message')
            @include('admin.website_management.footer.partials._modules')
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="{{ asset('tinymce/tinymce.min.js') }}"></script>
@vite('resources/admin/js/tinymce.js')

@include('admin.website_management.home_page_builder.partials._builder_js')
@endpush
