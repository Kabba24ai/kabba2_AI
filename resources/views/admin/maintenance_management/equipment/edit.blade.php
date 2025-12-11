@extends('admin.layouts.app')

@section('title', 'Update Equipment')

@section('content')
<div class="h-screen bg-gray-50 flex flex-col overflow-hidden">
    <div class="flex-1 overflow-auto">
        {{-- Header --}}
        <div class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <a href="{{ route('admin.maintenance-management.equipment.index') }}" class="flex items-center space-x-2 text-gray-600 hover:text-gray-800 transition-colors">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        <span>Back to Equipment</span>
                    </a>
                    <div class="h-6 border-l border-gray-300"></div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Equipment Details</h1>
                        <p class="text-sm text-gray-600">Edit equipment information</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Content --}}
        <div class="p-6">
            <!-- Form -->
            {{ html()->modelForm($equipment, 'PUT')->attributes([
                    'action' => route('admin.maintenance-management.equipment.edit', $equipment->unique_id),
                    'id' => 'productForm',
                    'autocomplete' => 'off',
                    'data-parsley-validate' => true,
                    'class' => 'max-w-5xl',
                ])->acceptsFiles()->open() }}
                @csrf
                @method('PUT')

                <div class="mb-6 flex justify-end">
                    <button type="button"
        onclick="window.location='{{ route('admin.maintenance-management.equipment.copy', $equipment->unique_id) }}'"
        class="inline-flex items-center px-6 py-2 mr-3 rounded-md text-white bg-green-600 hover:bg-green-700 text-sm font-semibold shadow transition">
    Save &amp; New
                <svg class="w-4 h-4 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
  <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"></path>
</svg>
</button>


                    <button type="submit" class="inline-flex items-center px-6 py-2 rounded-md text-white bg-blue-600 hover:bg-blue-700 text-sm font-semibold shadow transition">
                        Save &amp; Exit
                <svg class="w-4 h-4 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
  <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"></path>
</svg>
                    </button>
                </div>

                @include('admin.maintenance_management.equipment.partials._form')

                <div class="mt-6 flex justify-end">
                    <button type="submit" class="flex-shrink-0 inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                        <span>Save Changes</span>
                    </button>
                </div>
            {{ html()->form()->close() }}
        </div>
    </div>
</div>
@endsection
