@extends('admin.layouts.app')

@section('title', 'New Price List Preset')

@push('css')
<style>
    main { background-color: #f8fafc; flex: 1 1 auto; }
</style>
@endpush

@section('content')

    @include('flash::message')

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
        <h1 class="text-2xl font-semibold text-gray-900 flex items-center gap-2">
            <x-heroicon-o-squares-2x2 class="w-6 h-6 text-blue-600" />
            New Price List Preset
        </h1>
        <p class="text-sm text-gray-500 mt-1.5">
            Presets are selection shortcuts on the Customer Price List generator — nothing more.
        </p>
    </div>

    <form method="POST" action="{{ route('admin.documents.presets.store') }}">
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

@endsection
