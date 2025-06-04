@extends('admin.layouts.app')

@section('title', 'Edit New Rental Options')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">Edit New Rental Options</h3>
        <a href="{{ route('admin.product-management.options.index') }}"
            class="inline-flex items-center px-4 py-2 rounded-md bg-red-600 text-white text-sm font-medium hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 transition">
            <x-heroicon-o-arrow-left class="w-4 h-4 mr-2" />
            Back
        </a>
    </div>

    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm px-6 py-8">

        {{-- Flash + Error --}}
        @include('flash::message')
        @include('admin.partials.formErrors')
        {{ html()->modelForm($objProductOption, 'PUT')->attributes([
                            'autocomplete' => 'off',
                            'data-parsley-validate' => true,
                            'class' => 'space-y-8',
                        ])->acceptsFiles()->open() }}
        @include('admin.product_management.options.partials._form')

        <div class="mt-6 flex justify-end gap-4">
            <a href="{{ route('admin.product-management.options.index') }}"
                class="px-5 py-2 rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600 text-sm">
                Cancel
            </a>
            <!-- Save & New Button -->
            <button type="submit"
                name="action"
                value="save"
                class="inline-flex items-center px-6 py-2 rounded-md text-white bg-brand-600 hover:bg-brand-700 text-sm font-semibold shadow transition">
                Save
                <x-heroicon-o-plus class="w-4 h-4 ml-2" />
            </button>

            <!-- Save & New Button -->
            <button type="submit"
                name="action"
                value="save_and_new"
                class="inline-flex items-center px-6 py-2 rounded-md text-white bg-green-600 hover:bg-green-700 text-sm font-semibold shadow transition">
                Save & New
                <x-heroicon-o-plus class="w-4 h-4 ml-2" />
            </button>

            <!-- Save & Exit Button -->
            <button type="submit"
                name="action"
                value="save_and_exit"
                class="inline-flex items-center px-6 py-2 rounded-md text-white bg-blue-600 hover:bg-blue-700 text-sm font-semibold shadow transition">
                Save & Exit
                <x-heroicon-o-arrow-right-on-rectangle class="w-4 h-4 ml-2" />
            </button>
        </div>
        {{ html()->form()->close() }}
    </div>
@endsection
