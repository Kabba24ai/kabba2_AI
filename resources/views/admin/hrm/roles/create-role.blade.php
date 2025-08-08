
@extends('admin.layouts.app')

@section('title', 'Create Roles')

@section('content')

    @include('flash::message')

    <div class="bg-gray-50 px-4 py-4 border-b border-gray-200">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <!-- Left Section -->
            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                <!-- Back to Roles -->
                <a href="{{ route('admin.hrm.roles.manage.role') }}" class="flex items-center text-gray-600 hover:text-gray-800 transition">
                    <svg class="w-5 h-5 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"></path>
                    </svg>                
                    <span class="text-sm font-medium">Back to Roles</span>
                </a>

                <!-- Divider -->
                <div class="hidden sm:block h-6 border-l border-gray-300"></div>

                <!-- Customer Info -->
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Add New Role</h1>
                    <p class="text-sm text-gray-500">Create a new role with specific permissions</p>
                </div>
            </div>
        </div>
    </div>

    
                        {{ html()->form()->attributes([
                            'autocomplete' => 'off',
                            'data-parsley-validate' => true,
                            'class' => 'space-y-8',
                        ])->acceptsFiles()->open() }}

                            @include('admin.hrm.roles.partials._role_form')
                        {{ html()->form()->close() }}

@endsection


@push('js')
  <script>
    const colorSelect = document.getElementById('colorSelect');
    const colorPreview = document.getElementById('colorPreview');

    colorSelect.addEventListener('change', function () {
      colorPreview.style.backgroundColor = this.value;
    });
  </script>

@endpush

