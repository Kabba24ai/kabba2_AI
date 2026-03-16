@extends('admin.layouts.app')

@section('title', 'Branding Page')

@section('content')

@include('flash::message')
@include('admin.partials.formErrors')

<form method="POST" action="{{route('admin.website-management.branding.update')}}" enctype="multipart/form-data" data-parsley-validate>
@csrf

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
   {{-- Page Header --}}
   <div class="bg-white rounded-md p-5 shadow-sm border border-gray-100 mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
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
            Manage your website branding, logo and homepage content
         </p>
      </div>
   </div>
   {{-- Branding Settings --}}
   <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
      @php
      $siteLogo = \App\Helpers\ConfigurationHelper::getBrandingLogo();
      $homeImage = \App\Helpers\ConfigurationHelper::getHomePageImage();
      @endphp
      {{-- Profile Section --}}
      <div class="md:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
         <div class="flex items-center space-x-2 mb-6">
            <x-heroicon-o-user-circle class="h-5 w-5 text-blue-600"/>
            <h3 class="text-lg font-bold text-gray-900">Profile Section</h3>
         </div>
         <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Site Logo --}}
            <div >
               <label class="block text-sm font-medium text-gray-700 mb-1">
               Upload Site Logo
               </label>
               <div class="flex items-center space-x-4">
                  @if(!empty($siteLogo))
                  <img src="{{ $siteLogo }}"
                     class="h-12 w-12 object-contain border rounded-md bg-gray-50 p-1">
                  @endif
                  {!! html()->file('site_logo')
                  ->class([
                  'block w-full text-sm text-gray-500',
                  'file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0',
                  'file:text-sm file:font-semibold',
                  'file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100',
                  'border border-gray-300 rounded-md',
                  ])
                  ->attributes([
                  'accept' => 'image/*'
                  ]) !!}
               </div>
            </div>
            {{-- Site Name --}}
            <div>
               <label class="block text-sm font-medium text-gray-700 mb-1">
               Site Name
               </label>
               {!! html()->text('site_name', old('site_name', $settings['site_name'] ?? ''))
               ->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm shadow-sm focus:ring-2 focus:outline-none')
               ->attributes([
               'placeholder' => 'Example: Rent n King'
               ]) !!}
            </div>
            {{-- Phone --}}
            <div>
               <label class="block text-sm font-medium text-gray-700 mb-1">
               Phone Number
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
            </div>
            {{-- Email --}}
            <div >
               <label class="block text-sm font-medium text-gray-700 mb-1">
               Email Address
               </label>
               {!! html()->email('site_email', old('site_email', $settings['site_email'] ?? ''))
               ->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm shadow-sm focus:ring-2 focus:outline-none')
               ->attributes([
               'placeholder' => 'example@email.com'
               ]) !!}
            </div>
            {{-- Powered By --}}
            <div >
               <label class="block text-sm font-medium text-gray-700 mb-1">
               Footer -  All rights reserved.
               </label>
               {!! html()->text('all_rights_reserved', old('all_rights_reserved', $settings['all_rights_reserved'] ?? ''))
               ->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm shadow-sm focus:ring-2 focus:outline-none')
               ->attributes([
               'placeholder' => 'Example: Rent `n King'
               ]) !!}
            </div>
            {{-- Powered By --}}
            <div >
               <label class="block text-sm font-medium text-gray-700 mb-1">
               Footer - Powered By
               </label>
               {!! html()->text('powered_by', old('powered_by', $settings['powered_by'] ?? ''))
               ->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm shadow-sm focus:ring-2 focus:outline-none')
               ->attributes([
               'placeholder' => 'Example: Kabba.ai'
               ]) !!}
            </div>
         </div>
      </div>
      <div class="md:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
         <div class="flex items-center space-x-2 mb-6">
            <x-heroicon-o-paint-brush class="h-5 w-5 text-blue-600"/>
            <h3 class="text-lg font-bold text-gray-900">Home Page Settings</h3>
         </div>
         <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Home Page Image --}}
            <div class="md:col-span-2">
               <label class="block text-sm font-medium text-gray-700 mb-1">
               Upload Home Page Image
               <span class="text-red-600 text-xs">(Recommended Size: 500px × 1200px)</span>
               </label>
               <div class="flex items-center space-x-4">
                  @if(!empty($homeImage))
                  <img src="{{ $homeImage }}"
                     class="h-16 w-28 object-cover border rounded-md bg-gray-50">
                  @endif
                  {!! html()->file('home_page_image')
                  ->class([
                  'block w-full text-sm text-gray-500',
                  'file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0',
                  'file:text-sm file:font-semibold',
                  'file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100',
                  'border border-gray-300 rounded-md',
                  ])
                  ->attributes([
                  'accept' => 'image/*'
                  ]) !!}
               </div>
            </div>
            {{-- Top Text --}}
            <div >
               <label class="block text-sm font-medium text-gray-700 mb-1">
               Top Text
               </label>
               {!! html()->text('top_text', old('top_text', $settings['top_text'] ?? ''))
               ->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm shadow-sm focus:ring-2 focus:outline-none')
               ->attributes([
               'placeholder' => 'Call for Live Assistance from a Real Person'
               ]) !!}
            </div>
            {{-- Phone Number --}}
            <div>
               <label class="block text-sm font-medium text-gray-700 mb-1">
               Phone Number for Top Text
               </label>
               {!! html()->text('top_phone', old('top_phone', old('top_phone', $settings['top_phone'] ?? '')))->class([
               'masked-phone w-full border rounded-md px-3 py-3 text-sm shadow-sm focus:outline-none focus:ring-2',
               'border-red-500' => $errors->has('top_phone'),
               'border-gray-300' => !$errors->has('top_phone'),
               ])->attributes([
               'maxlength' => 14,
               'data-parsley-pattern' => '^\(\d{3}\)\s\d{3}-\d{4}$',
               'data-parsley-error-message' => 'Please enter phone number in format (xxx) xxx-xxxx',
               'placeholder' => '(xxx) xxx-xxxx',
               'id' => 'top_phone',
               'autocomplete' => 'tel',
               ]) !!}
            </div>
            {{-- Bottom Title --}}
            <div >
               <label class="block text-sm font-medium text-gray-700 mb-1">
               Bottom Text Title
               </label>
               {!! html()->text('bottom_title', old('bottom_title', old('bottom_title', $settings['bottom_title'] ?? '')))
               ->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm shadow-sm focus:ring-2 focus:outline-none') !!}
            </div>
            {{-- Bottom Text --}}
            <div >
               <label class="block text-sm font-medium text-gray-700 mb-1">
               Bottom Text
               </label>
               {!! html()->textarea('bottom_text', old('bottom_text', $settings['bottom_text'] ?? ''))
               ->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm shadow-sm focus:ring-2 focus:outline-none')
               ->attributes([
               'rows' => 4
               ]) !!}
            </div>
         </div>
      </div>
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