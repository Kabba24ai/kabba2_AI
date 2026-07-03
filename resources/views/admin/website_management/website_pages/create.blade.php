@extends('admin.layouts.app')

@section('title', 'Create Website Page')

@section('content')

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.website-management.pages.index') }}"
           class="text-gray-400 hover:text-gray-600 transition-colors">
            <x-heroicon-o-arrow-left class="w-5 h-5"/>
        </a>
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Create New Page</h1>
            <p class="text-sm text-gray-500">Set up the page details. You can manage sections after creating it.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.website-management.pages.store') }}" data-parsley-validate>
        @csrf

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5 mb-6">
            <h2 class="text-sm font-semibold text-gray-700 pb-2 border-b border-gray-100">Page Details</h2>

            {{-- Title --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Title <span class="text-red-500">*</span>
                </label>
                {!! html()->text('title', old('title'))
                    ->class('w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:outline-none' . ($errors->has('title') ? ' border-red-400' : ''))
                    ->id('page-title')
                    ->attributes(['placeholder' => 'e.g. About Us', 'required' => 'required']) !!}
                @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Slug --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Slug <span class="text-red-500">*</span>
                </label>
                {!! html()->text('slug', old('slug'))
                    ->class('w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:outline-none font-mono' . ($errors->has('slug') ? ' border-red-400' : ''))
                    ->id('page-slug')
                    ->attributes(['placeholder' => 'about-us', 'required' => 'required']) !!}
                <p class="text-xs text-gray-400 mt-1">URL-friendly identifier — auto-filled from title.</p>
                @error('slug') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Page Key --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Page Key <span class="text-gray-400 font-normal">(optional)</span></label>
                {!! html()->text('page_key', old('page_key'))
                    ->class('w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:outline-none font-mono' . ($errors->has('page_key') ? ' border-red-400' : ''))
                    ->attributes(['placeholder' => 'about']) !!}
                <p class="text-xs text-gray-400 mt-1">Used to identify the page in code. Defaults to slug if blank.</p>
                @error('page_key') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Status --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                {!! html()->select('status', ['Active' => 'Active', 'Inactive' => 'Inactive'], old('status', 'Inactive'))
                    ->class('w-48 border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:outline-none') !!}
            </div>
        </div>

        {{-- SEO --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5 mb-6">
            <h2 class="text-sm font-semibold text-gray-700 pb-2 border-b border-gray-100">SEO <span class="text-gray-400 font-normal">(optional)</span></h2>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Meta Title</label>
                {!! html()->text('meta_title', old('meta_title'))
                    ->class('w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:outline-none') !!}
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Meta Description</label>
                {!! html()->textarea('meta_description', old('meta_description'))
                    ->class('w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:outline-none resize-none')
                    ->rows(3) !!}
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Meta Keywords</label>
                {!! html()->text('meta_keywords', old('meta_keywords'))
                    ->class('w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:outline-none')
                    ->attributes(['placeholder' => 'keyword1, keyword2']) !!}
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-md text-sm font-medium shadow-sm transition-colors">
                Create Page
            </button>
            <a href="{{ route('admin.website-management.pages.index') }}"
               class="px-4 py-2.5 border border-gray-300 rounded-md text-sm text-gray-600 hover:bg-gray-50 transition-colors">
                Cancel
            </a>
        </div>
    </form>

</div>

@endsection

@push('js')
<script>
(function () {
    var titleEl = document.getElementById('page-title');
    var slugEl  = document.getElementById('page-slug');
    if (!titleEl || !slugEl) return;

    titleEl.addEventListener('input', function () {
        if (slugEl.dataset.manual) return;
        slugEl.value = titleEl.value
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
    });

    slugEl.addEventListener('input', function () {
        slugEl.dataset.manual = '1';
    });
})();
</script>
@endpush
