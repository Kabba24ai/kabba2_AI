{{-- Equipment Form --}}
<div class="space-y-6">

    {{-- <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @include('admin.configurations.partials._admin_settings')

        @include('admin.configurations.partials._allocated_setttings')
    </div> --}}

    {{-- Second Row --}}
    {{-- <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @include('admin.configurations.partials._communication_settings')

        @include('admin.configurations.partials._contact_us_settings')
    </div> --}}

    {{-- Second Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @include('admin.configurations.partials._product_rate_settings')
    </div>
</div>



@push('js')

@endpush
