@extends('admin.layouts.app')

@section('title', 'Create Product Category')

@section('content')
    {{-- Page header --}}
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Create Product Category</h2>
        <a href="{{ route('admin.product-management.categories.index') }}"
            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            Go Back
        </a>
    </div>

    {{-- Main content layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Left: Form card --}}
        <div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
                <div class="px-6 py-6">
                    {{-- Flash messages and form errors --}}
                    @include('flash::message')
                    @include('admin.partials.formErrors')

                    {{ html()->modelForm($objProduct, 'PUT')->attributes([
                            'autocomplete' => 'off',
                            'data-parsley-validate' => true,
                            'class' => 'space-y-8',
                        ])->acceptsFiles()->open() }}

                    @include('admin.product_management.categories._form')

                    {{-- Button row --}}
                    <div class="flex flex-wrap justify-between gap-4 pt-4 border-t border-gray-200 dark:border-gray-800">
                        {{-- Save --}}
                        <button type="submit" name="action" value="save"
                            class="inline-flex items-center px-5 py-2 bg-brand-500 text-white text-sm font-medium rounded-md hover:bg-brand-600 transition">
                            Save
                            <x-heroicon-s-check class="w-5 h-5 ml-2" />
                        </button>

                        {{-- Save & New --}}
                        <button type="submit" name="action" value="save_new"
                            class="inline-flex items-center px-5 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 transition">
                            Save & New
                            <x-heroicon-s-plus-circle class="w-5 h-5 ml-2" />
                        </button>

                        {{-- Save & Exit --}}
                        <button type="submit" name="action" value="save_exit"
                            class="inline-flex items-center px-5 py-2 bg-blue-500 text-white text-sm font-medium rounded-md hover:bg-blue-600 transition">
                            Save & Exit
                            <x-heroicon-s-arrow-right-on-rectangle class="w-5 h-5 ml-2" />
                        </button>
                    </div>


                    {{ html()->form()->close() }}
                </div>
            </div>
        </div>

        {{-- Right: Category hierarchy (placeholder content) --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm p-6">
            <h3 class="text-md font-semibold text-gray-700 dark:text-white mb-4">Category Hierarchy</h3>

            @if ($category_tree->isNotEmpty())
                <ul class="text-sm space-y-2 text-gray-600 dark:text-gray-300 list-disc list-inside">
                    @foreach ($category_tree as $category)
                        <li>
                            {{ $category->title }}
                            @if ($category->childCategories->isNotEmpty())
                                <ul class="ml-4 list-inside list-disc mt-1">
                                    @foreach ($category->childCategories as $child)
                                        <li>{{ $child->title }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">No categories available at the moment.</p>
            @endif

        </div>
    </div>
@endsection
