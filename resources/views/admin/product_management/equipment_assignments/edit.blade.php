@extends('admin.layouts.app')

@section('title', 'Edit Equipment Assignment')

@section('content')

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">Edit Equipment Assignment</h3>
        <a href="{{ route('admin.product-management.equipment-assignments.index') }}" class="inline-flex items-center justify-center rounded-lg bg-gray-200 dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-800 dark:text-white shadow hover:bg-gray-300 dark:hover:bg-gray-600">
            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
            <h6 class="text-red-800 dark:text-red-200 font-semibold mb-2">Please fix the following errors:</h6>
            <ul class="list-disc list-inside text-red-700 dark:text-red-300 text-sm space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.product-management.equipment-assignments.update', $assignment->id) }}" method="POST">
        @csrf
        @method('PUT')

        @include('admin.product_management.equipment_assignments.partials._form')

        <div class="flex gap-2 justify-between mt-6">
            <a href="{{ route('admin.product-management.equipment-assignments.index') }}" class="inline-flex items-center justify-center rounded-lg bg-gray-200 dark:bg-gray-700 px-6 py-2 text-sm font-medium text-gray-800 dark:text-white shadow hover:bg-gray-300 dark:hover:bg-gray-600">
                Cancel
            </a>
            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-6 py-2 text-sm font-medium text-white shadow hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Update Assignment
                </button>
        </div>
    </form>


@endsection
