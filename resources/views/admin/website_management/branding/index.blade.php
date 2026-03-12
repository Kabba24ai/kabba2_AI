@extends('admin.layouts.app', ['contentClass' => 'max-w-(--breakpoint-2xl)'])

@section('title', 'Branding Section')

@section('content')
    <div class="bg-gray-50 flex flex-col">
        <div class="flex-1 overflow-auto">
            {{-- Header --}}
            <div class=" border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div>
                            <x-heroicon-o-swatch class="w-8 h-8" />
                        </div>
                        <div class="h-6 border-l border-gray-300"></div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Branding Section</h1>
                            <p class="text-sm text-gray-600">Manage your website branding and visual identity</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Main Content --}}
            @include('admin.partials.formErrors')
            @include('flash::message')

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
                @include('admin.website_management.branding._form')
            </div>
        </div>
    </div>
@endsection
