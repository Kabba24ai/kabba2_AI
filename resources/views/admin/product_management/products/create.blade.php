@extends('admin.layouts.app')

@section('title', 'Products')

@push('css')
@endpush

@section('content')
    @include('flash::message')
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">Add New Product</h3>
        <div class="flex flex-wrap justify-end gap-4 pt-4 ">
            <button type="submit" name="action" value="save"
                class="inline-flex items-center px-5 py-2 bg-brand-500 text-white text-sm font-medium rounded-md hover:bg-brand-600 transition">
                Save <x-heroicon-m-check-circle class="w-5 h-5 ml-2" />
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
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left: Category Hierarchy --}}

        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
                <div class="px-6 py-6">
                    <!-- Product Form -->
                    {{ html()->form()->attributes([
                            'autocomplete' => 'off',
                            'data-parsley-validate' => true,
                            'class' => 'space-y-8',
                        ])->acceptsFiles()->open() }}

                    @include('admin.product_management.products.partials._form')

                    {{ html()->form()->close() }}
                </div>
            </div>
        </div>

        {{-- Right: Form --}}
        <div class="lg:col-span-1">
            <div class="space-y-6">
                <!-- Categories Card -->
                <div class="border border-gray-200 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 p-4">
                    <h4 class="text-sm font-semibold text-gray-800 dark:text-white mb-3">Categories</h4>
                    <div class="max-h-64 overflow-y-auto pr-1 custom-scrollbar space-y-3">
                        @foreach ($categories as $category)
                            @php
                                $checkboxId = "category_{$category->id}";
                                $isChecked = in_array($category->id, old('categories', []));
                            @endphp

                            <div x-data="{ checked: {{ $isChecked ? 'true' : 'false' }} }">
                                <label for="{{ $checkboxId }}"
                                    class="flex items-center text-sm font-medium text-gray-700 cursor-pointer select-none dark:text-gray-400">
                                    <div class="relative">
                                        {!! html()->checkbox('categories[]', $isChecked)->value($category->id)->id($checkboxId)->class('sr-only')->attribute('@change', 'checked = !checked') !!}

                                        <div :class="checked ? 'border-blue-500 bg-blue-500' :
                                            'bg-transparent border-gray-300 dark:border-gray-700'"
                                            class="mr-2 flex h-5 w-5 items-center justify-center rounded-md border-[1.25px]">
                                            <span :class="checked ? '' : 'opacity-0'">
                                                <x-heroicon-o-check class="w-3.5 h-3.5 text-white" />
                                            </span>
                                        </div>
                                    </div>
                                    {{ $category->title }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>


                <!-- Sales Funnels Card -->
                <div class="border border-gray-200 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 p-4">
                    <h4 class="text-sm font-semibold text-gray-800 dark:text-white mb-3">Sales Funnels</h4>
                    <div class="space-y-2">
                        @foreach ($funnels as $funnel)
                            <div class="flex items-center space-x-2 text-sm">
                                {!! html()->checkbox('funnels[]', in_array($funnel->id, old('funnels', [])))->value($funnel->id)->id("funnel_{$funnel->id}") !!}
                                <label for="funnel_{{ $funnel->id }}" class="text-gray-700 dark:text-gray-300">
                                    {{ $funnel->name }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Taxes Card -->
                <div class="border border-gray-200 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 p-4">
                    <h4 class="text-sm font-semibold text-gray-800 dark:text-white mb-3">Taxes</h4>
                    <div class="space-y-2">
                        @foreach ($taxes as $tax)
                            <div class="flex items-center space-x-2 text-sm">
                                {!! html()->radio('tax_id', old('tax_id') == $tax->id)->value($tax->id)->id("tax_{$tax->id}") !!}
                                <label for="tax_{{ $tax->id }}" class="text-gray-700 dark:text-gray-300">
                                    {{ $tax->name }} ({{ $tax->rate }}%)
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
@endpush
