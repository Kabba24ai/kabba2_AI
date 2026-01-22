@extends('admin.layouts.app')

@section('title', 'Edit Product Category')

@section('content')
    {{-- Page header --}}
    {{-- <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">Edit Product Category</h3>

        <a href="{{ route('admin.product-management.categories.create') }}"
            class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500">
            + Create
        </a>
    </div> --}}

    {{-- Flash  --}}
    @include('flash::message')

    <div class="flex flex-col 2xl:flex-row gap-4">
        {{-- Left: Category Hierarchy --}}
        <div class="w-full 2xl:w-1/3 ">
            @include('admin.product_management.categories.partials._hierarchy')
        </div>

        {{-- Right: Form --}}
        <div class="w-full 2xl:w-2/3 ">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
                <div class="px-6 py-6">


                    {{ html()->modelForm($objProductCategory, 'PUT')->attributes([
                            'autocomplete' => 'off',
                            'data-parsley-validate' => true,
                            'class' => 'space-y-8',
                        ])->acceptsFiles()->open() }}
                    <div class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-gray-200 dark:border-gray-800">
                        {{-- Left side title --}}
                        <h4 class="text-lg font-semibold text-brand-400 dark:text-white/80 capitalize">Update {{ $objProductCategory->title }}</h4>
                        {{-- Button row --}}
                        <div class="flex flex-wrap justify-end gap-4">
                            <button type="submit" name="action" value="save"
                                class="inline-flex items-center px-5 py-2 bg-brand-500 text-white text-sm font-medium rounded-md hover:bg-brand-600 transition">
                                Save <x-heroicon-m-check-circle class="w-5 h-5 ml-2" />
                            </button>
                            <button type="submit" name="action" value="save_new"
                                class="inline-flex items-center px-5 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 transition">
                                Save & New <x-heroicon-s-plus-circle class="w-5 h-5 ml-2" />
                            </button>
                            {{-- <button type="submit" name="action" value="save_exit"
                                class="inline-flex items-center px-5 py-2 bg-blue-500 text-white text-sm font-medium rounded-md hover:bg-blue-600 transition">
                                Save & Exit <x-heroicon-s-arrow-right-on-rectangle class="w-5 h-5 ml-2" />
                            </button> --}}
                        </div>
                    </div>

                    @include('admin.product_management.categories.partials._form')

                    {{-- Button row --}}
                    <div class="flex flex-wrap justify-end gap-4 pt-4 border-t border-gray-200 dark:border-gray-800">
                        <button type="submit" name="action" value="save"
                            class="inline-flex items-center px-5 py-2 bg-brand-500 text-white text-sm font-medium rounded-md hover:bg-brand-600 transition">
                            Save <x-heroicon-m-check-circle class="w-5 h-5 ml-2" />
                        </button>
                        <button type="submit" name="action" value="save_new"
                            class="inline-flex items-center px-5 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 transition">
                            Save & New <x-heroicon-s-plus-circle class="w-5 h-5 ml-2" />
                        </button>
                        {{-- <button type="submit" name="action" value="save_exit"
                            class="inline-flex items-center px-5 py-2 bg-blue-500 text-white text-sm font-medium rounded-md hover:bg-blue-600 transition">
                            Save & Exit <x-heroicon-s-arrow-right-on-rectangle class="w-5 h-5 ml-2" />
                        </button> --}}
                    </div>

                    {{ html()->form()->close() }}
                </div>
            </div>
        </div>
    </div>


@endsection
