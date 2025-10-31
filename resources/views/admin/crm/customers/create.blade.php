@extends('admin.layouts.app')

@section('title', 'Create Customer')

@push('css')
@endpush

@section('content')

    <!-- <div class="mb-6 flex items-center justify-between">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">Add New Customer</h3>
        <a href="{{ route('admin.crm.customers.index') }}" class="inline-flex items-center px-4 py-2 rounded-md bg-red-600 text-white text-sm font-medium hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 transition">
            <svg class="w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"></path>
            </svg> Back
        </a>
    </div> -->

                @include('flash::message')
                @include('admin.partials.formErrors')

                       <!-- Customer Form -->
                        {{ html()->form()->attributes([
                            'autocomplete' => 'off',
                            'data-parsley-validate' => true,
                                'id' => 'customerForm',

                            'class' => 'space-y-8',
                        ])->acceptsFiles()->open() }}


                        <div class="bg-gray-50 px-4 py-4 border-b border-gray-200">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                <!-- Left Section -->
                                <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                                    <!-- Back to Customers -->
                                    <a href="{{ route('admin.crm.customers.index') }}" class="flex items-center text-gray-600 hover:text-gray-800 transition">
                                        <x-heroicon-o-arrow-left class="w-5 h-5 mr-1" />
                                        <span class="text-sm font-medium">Back to Customers</span>
                                    </a>

                                    <!-- Divider -->
                                    <div class="hidden sm:block h-6 border-l border-gray-300"></div>

                                    <!-- Customer Info -->
                                    <div>
                                        <h3 class="text-xl font-semibold text-gray-800">Add New Customer</h3>
                                    </div>
                                </div>

                                <!-- Right Section: Buttons -->
                                <div class="flex flex-wrap gap-2">
                                    <div class="flex-1">
                                        <label for="category" class="block text-sm font-medium text-gray-700 mt-2">Status</label>
                                    </div>
                                    <!-- Field 2: Status -->
                                    <div class="w-40 min-w-[150px]">
                                        <select id="status" name="status" required
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option value="Active">Active</option>
                                            <option value="Inactive">Inactive</option>
                                            <option value="Archived">Archived</option>
                                        </select>
                                    </div>
                                    <button type="submit" name="action" value="save" class="inline-flex items-center px-6 py-2 rounded-md text-white bg-teal-600 hover:bg-teal-700 text-sm font-semibold shadow transition"> Save
                                        <x-heroicon-o-check class="w-4 h-4 ml-2" />
                                    </button>

                                    <!-- Save & New Button -->
                                    <button type="submit" name="action" value="save_new" class="inline-flex items-center px-6 py-2 rounded-md text-white bg-green-600 hover:bg-green-700 text-sm font-semibold shadow transition"> Save & New
                                        <x-heroicon-o-plus class="w-4 h-4 ml-2" />
                                    </button>

                                    <!-- Save & Exit Button -->
                                    <button type="submit" name="action" value="save_exit" class="inline-flex items-center px-6 py-2 rounded-md text-white bg-blue-600 hover:bg-blue-700 text-sm font-semibold shadow transition"> Save & Exit
                                        <x-heroicon-o-arrow-right-on-rectangle class="w-4 h-4 ml-2" />
                                    </button>
                                </div>
                            </div>
                        </div>



                        @include('admin.crm.customers.partials._form')

                   
                    {{ html()->form()->close() }}

                  

@endsection

@push('js')

@endpush

