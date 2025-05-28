@extends('admin.layouts.app')

@section('title', 'Create Product Category')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <a href="{{ route('admin.product-management.categories.create') }}"
            class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500">
            + Create
        </a>

        <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">Product Categories</h3>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left: Category Hierarchy --}}
        <div class="lg:col-span-1">
            @include('admin.product_management.categories._hierarchy')
        </div>

        {{-- Right: Form --}}
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
                <div class="px-6 py-6">
                    {{-- Flash + Error --}}
                    @include('flash::message')
                    @include('admin.partials.formErrors')

                    {{ html()->form()->attributes([
                            'autocomplete' => 'off',
                            'data-parsley-validate' => true,
                            'class' => 'space-y-8',
                        ])->acceptsFiles()->open() }}

                    @include('admin.product_management.categories._form')

                    {{-- Button Row --}}
                    <div class="flex flex-wrap justify-end gap-4 pt-4 border-t border-gray-200 dark:border-gray-800">
                        <button type="submit" name="action" value="save"
                            class="inline-flex items-center px-5 py-2 bg-brand-500 text-white text-sm font-medium rounded-md hover:bg-brand-600 transition">
                            Save <x-heroicon-s-check class="w-5 h-5 ml-2" />
                        </button>
                        <button type="submit" name="action" value="save_new"
                            class="inline-flex items-center px-5 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 transition">
                            Save & New <x-heroicon-s-plus-circle class="w-5 h-5 ml-2" />
                        </button>
                        <button type="submit" name="action" value="save_exit"
                            class="inline-flex items-center px-5 py-2 bg-blue-500 text-white text-sm font-medium rounded-md hover:bg-blue-600 transition">
                            Save & Exit <x-heroicon-s-arrow-right-on-rectangle class="w-5 h-5 ml-2" />
                        </button>
                    </div>

                    {{ html()->form()->close() }}
                </div>
            </div>
        </div>
    </div>
@endsection
