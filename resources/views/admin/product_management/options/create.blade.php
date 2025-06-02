@extends('admin.layouts.app')

@section('title', 'Create New Rental Options')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">Create New Rental Options</h3>
        <a href="{{ route('admin.product-management.options.index') }}"
            class="inline-flex items-center px-4 py-2 rounded-md bg-red-600 text-white text-sm font-medium hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 transition">
            <x-heroicon-o-arrow-left class="w-4 h-4 mr-2" />
            Back
        </a>
    </div>

    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm px-6 py-8">
        {{ html()->form('POST', route('admin.product-management.options.create'))->attribute('autocomplete', 'off')->open() }}
        @include('admin.product_management.options.partials._form')

        <div class="mt-6 flex justify-end gap-4">
            <a href="{{ route('admin.product-management.options.index') }}"
                class="px-5 py-2 rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600 text-sm">
                Cancel
            </a>
            <button type="submit"
                class="px-5 py-2 rounded-md bg-green-600 text-white hover:bg-green-700 text-sm font-medium inline-flex items-center">
                <x-heroicon-o-plus class="w-4 h-4 mr-1" />
                Save Options
            </button>
        </div>
        {{ html()->form()->close() }}
    </div>
@endsection
