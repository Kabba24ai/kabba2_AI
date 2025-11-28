@extends('admin.layouts.app')

@section('title', 'Create Parts List')

@section('content')


@include('flash::message')
@include('admin.partials.formErrors')

<div class="min-h-screen bg-gray-50">
    <div>
        {{-- Header --}}
        <div class="mb-6">
            <div class="flex items-center space-x-3 ">
                <a href="{{ route('admin.maintenance-management.parts.index') }}" class="p-2 text-gray-400 hover:text-gray-600 transition-colors rounded-lg">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                <h1 class="text-2xl font-semibold text-gray-900">Create New Parts List</h1>
            </div>
            <p class="text-gray-600 ml-14">Create a reusable parts list for equipment maintenance</p>
        </div>

        {{-- Form --}}
         <form action="{{ route('admin.maintenance-management.parts.parts-list.create') }}" method="POST">
            @csrf
                @include('admin.maintenance_management.parts.parts_list.partials._form')


           {{-- Form Actions --}}
            <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200">
                <a href="{{ route('admin.maintenance-management.parts.index') }}"
                    class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 flex items-center gap-2 transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Cancel
                </a>
                <button type="submit"
                    class="bg-blue-600 text-white px-6 py-3 rounded-lg flex items-center gap-2 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-save h-4 w-4"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                    Save List
                </button>
            </div>
        </form>
    </div>
</div>


@endsection
