@extends('admin.layouts.app', ['contentClass' => 'max-w-(--breakpoint-2xl)'])

@section('title', 'System Configuration')

@section('content')
<div class="h-screen bg-gray-50 flex flex-col overflow-hidden">
    <div class="flex-1 overflow-auto">
        {{-- Header --}}
        <div class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <a href="{{ route('admin.maintenance-management.equipment.index') }}" class="flex items-center space-x-2 text-gray-600 hover:text-gray-800 transition-colors">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        <span>Back to Equipment</span>
                    </a>
                    <div class="h-6 border-l border-gray-300"></div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">System Configuration</h1>
                        <p class="text-sm text-gray-600">Manage system settings and configurations</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Content --}}

        <div class="mt-6">
            {{-- Tabs --}}
            @include('admin.partials.formErrors')
            @include('flash::message')
            @include('admin.configurations.partials._modules')
        </div>
    </div>
</div>
@endsection

@push('js')

@endpush
