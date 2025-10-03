@extends('admin.layouts.app', ['contentClass' => 'max-w-(--breakpoint-2xl)'])

@section('title', 'System Configuration')

@section('content')
    <div class="h-screen bg-gray-50 flex flex-col overflow-hidden">
        <div class="flex-1 overflow-auto">
            {{-- Header --}}
            <div class=" border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <x-heroicon-o-cog-8-tooth class="h-10 w-10" />
                        <div class="h-6 border-l border-gray-300"></div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">System Configuration</h1>
                            <p class="text-sm text-gray-600">Manage system settings and configurations</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Main Content --}}

            <div>
                {{-- Tabs --}}
                @include('admin.partials.formErrors')
                @include('flash::message')
                @include('admin.configurations.partials._modules')
            </div>
        </div>
    </div>
@endsection

@push('js')
<script src="{{ asset('tinymce/tinymce.min.js') }}"></script>
@vite('resources/admin/js/tinymce.js')

@endpush
