@extends('admin.layouts.app')

@section('title', 'Create User')

@section('content')

    @include('flash::message')



   <div class="bg-gray-50 px-4 py-4 border-b border-gray-200">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <!-- Left Section -->
            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                <!-- Back to Employees -->
                <a href="{{ route('admin.hrm.users.index') }}" class="flex items-center text-gray-600 hover:text-gray-800 transition">
                    <svg class="w-5 h-5 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"></path>
                    </svg>
                    <span class="text-sm font-medium">Back to Employees</span>
                </a>

                <!-- Divider -->
                <div class="hidden sm:block h-6 border-l border-gray-300"></div>

                <!-- Customer Info -->
                <div>
                    <h1 class="text-2xl font-bold text-gray-900"> Add New Employee</h1>
                    <p class="text-sm text-gray-500">Create a new employee profile</p>
                </div>
            </div>
        </div>
    </div>

       <!-- user Form -->

                        {{ html()->form()->attributes([
                            'autocomplete' => 'off',
                            'data-parsley-validate' => true,
                            'class' => 'space-y-8',
                        ])->acceptsFiles()->open() }}

                            @include('admin.hrm.users.partials._form')

                            {{ html()->form()->close() }}

                        @endsection


@push('js')

<script>
    window.APP_DATE_FORMAT = @json(config('app.aire_datepicker_format', 'MM/dd/yyyy'));
</script>


@endpush
