@extends('admin.layouts.app')

@section('title', 'Terms')

@push('css')
@endpush

@section('content')
    {{-- Flash --}}
    @include('flash::message')
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">Add New Terms</h3>
        {{-- <div class="flex flex-wrap justify-end gap-4 pt-4 ">
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
        </div> --}}
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
        {{-- Left: Category Hierarchy --}}

        <div class="lg:col-span-4">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
                <div class="px-4 py-4">
                    <!-- terms Form -->
                    {{ html()->form()->attributes([
                            'autocomplete' => 'off',
                            'data-parsley-validate' => true,
                            'class' => 'space-y-8',
                        ])->acceptsFiles()->open() }}

                    @include('admin.terms_and_condition.terms.partials._form')

                    <div class="flex justify-center mt-8 space-x-4">
                        <!-- Save As Dropdown -->
                        <div x-data="{ open: false }" class="relative flex items-center">
                            <button type="button" @click="open = !open"
                                class="inline-flex items-center px-6 py-2 rounded-md text-white bg-teal-600 hover:bg-teal-700 text-sm font-semibold shadow transition">
                                Save As
                                <x-heroicon-o-chevron-right class="w-4 h-4 ml-2" />
                            </button>

                            <!-- Horizontal Dropdown (Flyout) -->
                            <div x-show="open" @click.away="open = false" x-transition
                                class="absolute left-full top-1/2 -translate-y-1/2 ml-2  w-36 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-md shadow-lg z-50 text-left">
                                <button type="button"
                                    class="w-full px-4 py-2 text-sm text-gray-700 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 text-left">
                                    Draft
                                </button>
                                <button type="button"
                                    class="w-full px-4 py-2 text-sm text-gray-700 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 text-left">
                                    Pending
                                </button>
                                <button type="button"
                                    class="w-full px-4 py-2 text-sm text-gray-700 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 text-left">
                                    Published
                                </button>
                            </div>
                        </div>

                        <!-- Save & New Button -->
                        <button type="submit"
                            class="inline-flex items-center px-6 py-2 rounded-md text-white bg-green-600 hover:bg-green-700 text-sm font-semibold shadow transition">
                            Save & New
                            <x-heroicon-o-plus class="w-4 h-4 ml-2" />
                        </button>

                        <!-- Save & Exit Button -->
                        <button type="submit"
                            class="inline-flex items-center px-6 py-2 rounded-md text-white bg-blue-600 hover:bg-blue-700 text-sm font-semibold shadow transition">
                            Save & Exit
                            <x-heroicon-o-arrow-right-on-rectangle class="w-4 h-4 ml-2" />
                        </button>
                    </div>


                    {{ html()->form()->close() }}
                </div>
            </div>
        </div>

        
    </div>
@endsection

@push('js')
@endpush
