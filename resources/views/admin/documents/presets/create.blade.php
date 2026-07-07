@extends('admin.layouts.app')

@section('title', 'New Price List Preset')

@push('css')
<style>
    main { background-color: #f8fafc; flex: 1 1 auto; }
</style>
@endpush

@section('content')

    @include('flash::message')

    <div class="max-w-[1440px] mx-auto">

    @include('admin.documents.partials._price_list_nav', [
        'plSubtitle' => 'New industry preset — a selection shortcut on the Generate tab, nothing more.',
    ])

    <form method="POST" action="{{ route('admin.documents.presets.store') }}" enctype="multipart/form-data">
        @csrf

        @include('admin.documents.presets.partials._form')

        <div class="flex items-center gap-3 mb-6">
            <button type="submit"
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium text-sm transition">
                <x-heroicon-o-check class="w-4 h-4" />
                Create Preset
            </button>
            <a href="{{ route('admin.documents.presets.index') }}"
                class="text-sm font-medium text-gray-500 hover:text-gray-700">Cancel</a>
        </div>
    </form>

    </div>

@endsection
