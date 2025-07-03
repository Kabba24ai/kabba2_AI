@extends('admin.layouts.app')

@section('title', 'Create Part')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="p-6">
        {{-- Header --}}
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-6">
            <div class="flex items-center space-x-3 mb-2">
                <a href="{{ route('admin.maintenance-management.parts.index') }}" class="p-2 text-gray-400 hover:text-gray-600 transition-colors rounded-lg">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                <h1 class="text-3xl font-bold text-gray-900">Create New Part</h1>
            </div>
            <p class="text-gray-600 ml-14">Add a new part with up to 3 alternative suppliers</p>
        </div>

        {{-- Form --}}
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <form action="{{ route('admin.maintenance-management.parts.create') }}" method="POST">
                @csrf
                
                @include('admin.maintenance_management.parts.partials._form')

                {{-- Form Actions --}}
                <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200">
                    <a href="{{ route('admin.maintenance-management.parts.index') }}" 
                       class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 flex items-center gap-2 transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Cancel
                    </a>
                    <button type="submit" 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Create Part
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
