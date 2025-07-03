@extends('admin.layouts.app')

@section('title', 'Dashboard')

@push('css')
@endpush

@section('content')
    <div
        class="min-h-screen rounded-2xl border border-gray-200 bg-white px-5 py-7 dark:border-gray-800 dark:bg-white/[0.03] xl:px-10 xl:py-12">
        <div class="mx-auto w-full max-w-[630px] text-center">
            <h3 class="mb-4 font-semibold text-gray-800 text-brand-xl dark:text-white/90 sm:text-2xl">
                Welcome to the Admin Dashboard
            </h3>

            <p class="text-sm text-gray-500 dark:text-gray-400 sm:text-base">
                This is the admin dashboard where you can manage your application. Use the navigation menu to access different sections such as user management, product management, and more. If you have any questions or need assistance, please refer to the documentation or contact support.
            </p>
        </div>
    </div>
@endsection

@push('js')
@endpush
