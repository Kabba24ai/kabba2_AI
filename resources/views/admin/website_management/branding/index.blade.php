@extends('admin.layouts.app')

@section('title', 'Branding Page')

@section('content')

    @include('flash::message')

    <form method="POST" action="{{ route('admin.website-management.branding.update') }}" enctype="multipart/form-data"
        data-parsley-validate>
        @csrf

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            {{-- Page Header --}}
            <div
                class="bg-white rounded-md p-5 shadow-sm border border-gray-100 mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-3 mb-2">
                        <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        <h1 class="text-2xl font-semibold text-gray-900">
                            Branding Settings
                        </h1>
                    </div>
                    <p class="text-gray-600">
                        Manage your company identity — logo, favicon, site name, and contact details
                    </p>
                </div>
            </div>
            {{-- Branding Settings --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                @php
                    $siteLogo    = \App\Helpers\ConfigurationHelper::getBrandingLogo();
                    $siteFavicon = \App\Helpers\ConfigurationHelper::getBrandingFavicon();
                @endphp
                {{-- Profile Section --}}
                <div class="md:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
                    <div class="flex items-center space-x-2 mb-6">
                        <x-heroicon-o-user-circle class="h-5 w-5 text-blue-600" />
                        <h3 class="text-lg font-bold text-gray-900">Profile Section</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {{-- Site Logo --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Upload Site Logo (Navbar)
                                <span class="block text-red-600 text-xs mt-1">(Required Size: 64px x 64px)</span>
                            </label>
                            <div class="flex items-center space-x-4">
                                @if (!empty($siteLogo))
                                    <img src="{{ $siteLogo }}"
                                        class="h-12 w-12 object-contain border rounded-md bg-gray-50 p-1">
                                @endif
                                {!! html()->file('site_logo')->class([
                                        'block w-full text-sm text-gray-500',
                                        'file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0',
                                        'file:text-sm file:font-semibold',
                                        'file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100',
                                        'border border-gray-300 rounded-md',
                                    ])->attributes([
                                        'accept' => 'image/*',
                                    ]) !!}
                            </div>
                            @error('site_logo')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        {{-- Site Favicon --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Upload Favicon (Browser Tab Icon)
                                <span class="block text-red-600 text-xs mt-1">(Required Size: 64px x 64px)</span>
                            </label>
                            <div class="flex items-center space-x-4">
                                @if (!empty($siteFavicon))
                                    <img src="{{ $siteFavicon }}"
                                        class="h-12 w-12 object-contain border rounded-md bg-gray-50 p-1">
                                @endif
                                {!! html()->file('site_favicon')->class([
                                        'block w-full text-sm text-gray-500',
                                        'file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0',
                                        'file:text-sm file:font-semibold',
                                        'file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100',
                                        'border border-gray-300 rounded-md',
                                    ])->attributes([
                                        'accept' => 'image/*',
                                    ]) !!}
                            </div>
                            @error('site_favicon')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        {{-- Site Name --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Site Name (opportunities page title, etc.)
                            </label>
                            {!! html()->text('site_name', old('site_name', $settings['site_name'] ?? ''))->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm shadow-sm focus:ring-2 focus:outline-none')->attributes([
                                    'placeholder' => 'Example: Rent n King',
                                ]) !!}
                            @error('site_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        {{-- Phone — the canonical company phone: structured data (schema),
                             the Custom Range delivery popup, and the fallback for popups
                             without their own number. The High Demand Alert phone is edited
                             on Website Mgt → High Demand Alert. --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Company Phone Number
                            </label>
                            {!! html()->text('site_phone', old('site_phone', old('site_phone', $settings['site_phone'] ?? '')))->class([
                                    'masked-phone w-full border rounded-md px-3 py-3 text-sm shadow-sm focus:outline-none focus:ring-2',
                                    'border-red-500' => $errors->has('site_phone'),
                                    'border-gray-300' => !$errors->has('site_phone'),
                                ])->attributes([
                                    'maxlength' => 14,
                                    'data-parsley-pattern' => '^\(\d{3}\)\s\d{3}-\d{4}$',
                                    'data-parsley-error-message' => 'Please enter phone number in format (xxx) xxx-xxxx',
                                    'placeholder' => '(xxx) xxx-xxxx',
                                    'id' => 'site_phone',
                                    'autocomplete' => 'tel',
                                ]) !!}
                            <p class="mt-1 text-xs text-gray-500">
                                Used across the website (delivery popups, search-engine business info).
                                The High Demand Alert popup phone is managed on
                                <a href="{{ route('admin.website-management.high-demand-alert.index') }}"
                                   class="text-blue-600 hover:underline">High Demand Alert</a>.
                            </p>
                            @error('site_phone')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        {{-- Email --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Email Address (Opportunities page contact email)
                            </label>
                            {!! html()->email('site_email', old('site_email', $settings['site_email'] ?? ''))->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm shadow-sm focus:ring-2 focus:outline-none')->attributes([
                                    'placeholder' => 'example@email.com',
                                ]) !!}
                            @error('site_email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        {{-- Footer copyright + "Powered by" are managed in
                             Website Management → Footer (Footer Content tab) —
                             the sole owner of footer content. --}}
                    </div>

                    {{-- Homepage SEO is managed in Home Page Builder → SEO — the single
                         editor for the public homepage title/description metadata. --}}
                </div>

                {{-- Retired: the "Home Page Settings" card (top_text / top_phone /
                     bottom_title / bottom_text). Those legacy homepage fields have no
                     front-end consumers — homepage content is managed in the Home Page
                     Builder, and structured-data phone now uses site_phone. --}}
                {{-- The Rental Agreement Header (order terms lines) is managed in
                     Settings → Terms & Conditions — not part of branding. --}}
                {{-- Save Button --}}
                <div class="mt-6 text-right">
                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md text-sm font-medium shadow-sm">
                        Save Branding Settings
                    </button>
                </div>
            </div>
        </div>
    </form>

@endsection
