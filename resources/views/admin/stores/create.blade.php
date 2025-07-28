@extends('admin.layouts.app')

@section('title', 'CreateStore')

@push('css')
@endpush

@section('content')
    {{-- Flash --}}
    @include('flash::message')
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">Add New Store</h3>
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

                    @include('admin.stores.partials._form')

                    <div class="flex justify-center mt-8 space-x-4">
                        <button type="submit" name="action" value="save"
                            class="inline-flex items-center px-5 py-2 bg-brand-500 text-white text-sm font-medium rounded-md hover:bg-brand-600 transition">
                            Save <x-heroicon-m-check-circle class="w-5 h-5 ml-2" />
                        </button>

                        <!-- Save & New Button -->
                        <button type="submit" name="action" value="save_new"
                            class="inline-flex items-center px-6 py-2 rounded-md text-white bg-green-600 hover:bg-green-700 text-sm font-semibold shadow transition">
                            Save & New
                            <x-heroicon-o-plus class="w-4 h-4 ml-2" />
                        </button>

                        <!-- Save & Exit Button -->
                        <button type="submit" name="action" value="save_exit"
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
