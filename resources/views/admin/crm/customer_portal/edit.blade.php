@extends('admin.layouts.app')

@section('title', 'Edit Customer')

@push('css')
@endpush

@section('content')

    <div class="mb-6 flex items-center justify-between">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">Edit Customer</h3>
        <a href="{{ route('admin.crm.customer_portal.index') }}" class="inline-flex items-center px-4 py-2 rounded-md bg-red-600 text-white text-sm font-medium hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 transition">
            <svg class="w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"></path>
            </svg> Back
        </a>
    </div>

                @include('flash::message')
                @include('admin.partials.formErrors')

                       <!-- Customer Form -->
                       {{ html()->modelForm($customer, 'PUT')->attributes([
                            'autocomplete' => 'off',
                            'data-parsley-validate' => true,
                            'class' => 'space-y-8',
                        ])->acceptsFiles()->open() }}

                        @include('admin.crm.customer_portal.partials._form')

                        <div class="flex justify-center mt-8 space-x-4">
                        <!-- Save As Dropdown -->
                        {{-- <div x-data="{ open: false }" class="relative flex items-center">
                            <button type="button" @click="open = !open"
                                class="inline-flex items-center px-6 py-2 rounded-md text-white bg-teal-600 hover:bg-teal-700 text-sm font-semibold shadow transition">
                                Save As
                                <x-heroicon-o-chevron-right class="w-4 h-4 ml-2" />
                            </button>

                            <!-- Horizontal Dropdown (Flyout) -->
                            <div x-show="open" @click.away="open = false" x-transition
                                class="absolute left-full top-1/2 -translate-y-1/2 ml-2  w-36 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-md shadow-lg z-50 text-left">
                                <!-- Draft -->
                                <button type="submit" name="action" value="save" @click="$refs.statusField.value = 'Draft'; open = false"
                                    class="w-full px-4 py-2 text-sm text-gray-700 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 text-left">
                                    Draft
                                </button>

                                <!-- Pending -->
                                <button type="submit" name="action" value="save" @click="$refs.statusField.value = 'Pending'; open = false"
                                    class="w-full px-4 py-2 text-sm text-gray-700 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 text-left">
                                    Pending
                                </button>

                                <!-- Published -->
                                <button type="submit" name="action" value="save" @click="$refs.statusField.value = 'Published'; open = false"
                                    class="w-full px-4 py-2 text-sm text-gray-700 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 text-left">
                                    Published
                                </button>
                            </div>
                        </div> --}}

                        <!-- Save -->
                        <button type="submit" name="action" value="save"
                            class="inline-flex items-center px-6 py-2 rounded-md text-white bg-teal-600 hover:bg-teal-700 text-sm font-semibold shadow transition">
                            Save
                            <x-heroicon-o-check class="w-4 h-4 ml-2" />
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



           <!-- Hidden Delete Form -->
<form id="delete-media-form-{{ $customer->id }}" method="POST" action="{{ route('admin.crm.customer_portal.tax-document.delete', $customer->unique_id) }}" style="display: none;">
    @csrf
    @method('DELETE')
</form>         
                    
        @include('admin.crm.customer_portal.partials._add_addreshh_form')
                    

                    <script>
                        document.addEventListener('DOMContentLoaded', () => {
                document.getElementById('openAddressModal').addEventListener('click', () => {
                    const modalRoot = document.querySelector('[x-ref="addressRoot"]');
                    if (modalRoot?._x_dataStack?.[0]) {
                        modalRoot._x_dataStack[0].showAddressModal = true;
                    }
                });
            
            });
            
            
function confirmAndDelete(id) {
    if (confirm("Are you sure you want to delete this document?")) {
        document.getElementById(`delete-media-form-${id}`).submit();
    }
}
                    </script>

@endsection


