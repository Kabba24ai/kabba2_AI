@extends('admin.layouts.app',['contentClass' => 'max-w-(--breakpoint-2xl)'])

@section('title', 'Edit Store')

@section('content')
{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">Edit Store</h3>
</div>

{{-- Flash  --}}
@include('flash::message')

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    {{-- Right: Form --}}
    <div class="col-span-2">
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
            <div class="px-6 py-6">

                {{ html()->modelForm($store, 'PUT')->attributes([
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
