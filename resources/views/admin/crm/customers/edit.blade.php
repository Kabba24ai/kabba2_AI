@extends('admin.layouts.app')

@section('title', 'Edit Customer')

@push('css')
@endpush

@section('content')



                @include('flash::message')
                @include('admin.partials.formErrors')

                       <!-- Customer Form -->
                       {{ html()->modelForm($customer, 'PUT')->attributes([
                            'autocomplete' => 'off',
                            'data-parsley-validate' => true,
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
                <h1 class="text-2xl font-bold text-gray-900">jhh hhh</h1>
                <p class="text-sm text-gray-500">CUS-4IVT-XR5N</p>
            </div>
        </div>

         <!-- Right Section: Buttons -->
        <div class="flex flex-wrap gap-2">
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

                        @include('admin.crm.customers.partials._order_table')

                    

                    {{ html()->form()->close() }}



           <!-- Hidden Delete Form -->
<form id="delete-media-form-{{ $customer->id }}" method="POST" action="{{ route('admin.crm.customers.tax-document.delete', $customer->unique_id) }}" style="display: none;">
    @csrf
    @method('DELETE')
</form>         
                    
        @include('admin.crm.customers.partials._add_addreshh_form')
                    

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
    if (confirm("Are you sure you want to delete this document?"+id)) {
        document.getElementById(`delete-media-form-${id}`).submit();
    }
}
                    </script>

@endsection


