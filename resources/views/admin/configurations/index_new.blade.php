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
        <div class="p-6">
            @include('admin.partials.formErrors')
            {{ html()->form()->attributes([
                    'action' => route('admin.configurations.new.index'),
                    'method' => 'POST',
                    'autocomplete' => 'off',
                    'data-parsley-validate' => true,
                ])->open() }}

                @csrf
                <div class="mb-6 flex justify-end">
                    <button type="submit" class="flex items-center space-x-2 px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                        <span>Save</span>
                    </button>
                </div>

                @include('admin.configurations.partials._form')

                <div class="mt-6 flex justify-end">
                    <button type="submit" class="flex items-center space-x-2 px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                        <span>Save</span>
                    </button>
                </div>
            {{ html()->form()->close() }}
        </div>
    </div>
</div>
@endsection

@push('js')

@endpush
