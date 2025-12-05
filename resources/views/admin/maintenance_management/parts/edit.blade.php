@extends('admin.layouts.app')

@section('title', 'Edit Part')

@section('content')

@include('flash::message')
<!-- @include('admin.partials.formErrors') -->
@include('admin.partials.notify')


<div class="min-h-screen bg-gray-50">
    <div class="">
      {{-- Header --}}
<div class="mb-6">

    <!-- FLEX ROW: left + right -->
    <div class="flex items-center justify-between">

        <!-- LEFT SIDE -->
        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.maintenance-management.parts.index') }}"
                class="p-2 text-gray-400 hover:text-gray-600 transition-colors rounded-lg">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>

            <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
            </svg>

            <h1 class="text-2xl font-semibold text-gray-900">Edit Part</h1>
        </div>

        <!-- RIGHT SIDE BUTTON -->
        <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto justify-end">

            <a href="javascript:void(0)" onclick="openModal('BrandModalWrapper')"
            class="flex items-center justify-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 text-md rounded-lg transition-colors">

                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2">
                    <path d="M6 3v12l6 6 6-6V3H6z"/>
                </svg>

                Manage Brand
            </a>

        </div>

    </div>

    <!-- Subtext -->
    <p class="text-gray-600 ml-14">Update part information with alternative suppliers</p>

</div>

        {{-- Form --}}
        <div class="bg-white rounded-md p-6 shadow-sm border border-gray-100">
            <!-- <form action="{{ route('admin.maintenance-management.parts.edit', $part->id) }}" method="POST"> -->

            {{-- Form Start --}}

            {!! html()->form('POST', route('admin.maintenance-management.parts.edit', $part->unique_id))
            ->attributes([
            'autocomplete' => 'off',
            'data-parsley-validate' => true,
            'class' => 'space-y-8',
            ])
            ->acceptsFiles()
            ->open() !!}

            @csrf
            @method('PUT')

            @include('admin.maintenance_management.parts.partials._form')

            {{-- Form Actions --}}
            <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200">
                <a href="{{ route('admin.maintenance-management.parts.index') }}"
                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50 flex items-center gap-2 transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Cancel
                </a>
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm flex items-center gap-2 transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Update Part
                </button>
            </div>

            {!! html()->form()->close() !!}


        </div>
    </div>
</div>
            @include('admin.maintenance_management.parts.partials._manage_brand_modal')

@endsection

@push('js')

<script>

     // === Common Modal Functions ===
            function openModal(modalId) {
                const modal = document.getElementById(modalId);
                if (modal) modal.classList.remove('hidden');
            }

            function closeModal(modalId) {
                const modal = document.getElementById(modalId);
                if (modal) modal.classList.add('hidden');
            }



    function openBrandAddModal() {
    document.getElementById("BrandAddModal").classList.remove("hidden");
    document.getElementById("newBrandInputModal").value = "";
    document.getElementById("brandDuplicateWarning").classList.add("hidden");
}

function closeBrandAddModal() {
    document.getElementById("BrandAddModal").classList.add("hidden");
}
</script>


@endpush