@extends('admin.layouts.app')

@section('title', 'Create Menu')

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Header --}}
    <div class="mb-6 flex items-center gap-3">
        <a href="{{ route('admin.website-management.navigation-builder.index') }}"
           class="text-gray-400 hover:text-gray-600 transition-colors">
            <x-heroicon-o-arrow-left class="w-5 h-5"/>
        </a>
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Create New Menu</h1>
            <p class="text-sm text-gray-500">After creating, you'll be taken to the item builder.</p>
        </div>
    </div>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-md px-4 py-3 text-sm mb-6">
        <ul class="list-disc pl-4 space-y-0.5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('admin.website-management.navigation-builder.store') }}"
          class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">
        @csrf

        {{-- Name --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Menu Name <span class="text-red-500">*</span>
            </label>
            {!! html()->text('name', old('name'))
                ->class('w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none')
                ->attributes(['placeholder' => 'e.g. Primary Header', 'id' => 'menu-name']) !!}
            <p class="text-xs text-gray-400 mt-1">Displayed in the admin panel only.</p>
        </div>

        {{-- Menu Key --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Menu Key <span class="text-red-500">*</span>
            </label>
            <div class="flex items-center border border-gray-300 rounded-md overflow-hidden focus-within:ring-2 focus-within:ring-blue-400">
                <span class="px-3 py-2.5 bg-gray-50 text-gray-400 text-sm border-r border-gray-300 select-none">key:</span>
                {!! html()->text('menu_key', old('menu_key'))
                    ->class('flex-1 px-3 py-2.5 text-sm font-mono focus:outline-none bg-white')
                    ->attributes(['placeholder' => 'primary_header', 'id' => 'menu-key', 'pattern' => '[a-z0-9_]+']) !!}
            </div>
            <p class="text-xs text-gray-400 mt-1">Lowercase, numbers, underscores only. Used in code: <code class="bg-gray-100 px-1 rounded">getTree('primary_header')</code></p>
        </div>

        {{-- Description --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            {!! html()->textarea('description', old('description'))
                ->class('w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none resize-none')
                ->rows(2)
                ->attributes(['placeholder' => 'Optional — describe where this menu is used.']) !!}
        </div>

        {{-- Status + Display Order --}}
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                {!! html()->select('status', ['Active' => 'Active', 'Inactive' => 'Inactive'], old('status', 'Active'))
                    ->class('w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none bg-white') !!}
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Display Order</label>
                {!! html()->number('display_order', old('display_order', 0))
                    ->class('w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none')
                    ->attributes(['min' => 0]) !!}
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('admin.website-management.navigation-builder.index') }}"
               class="px-4 py-2 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors">
                Cancel
            </a>
            <button type="submit"
                    class="px-5 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors shadow-sm">
                Create Menu & Build
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const nameInput = document.getElementById('menu-name');
    const keyInput  = document.getElementById('menu-key');
    let manualKey   = false;

    keyInput.addEventListener('input', () => { manualKey = true; });

    nameInput.addEventListener('input', function () {
        if (manualKey) return;
        keyInput.value = this.value
            .toLowerCase()
            .replace(/[^a-z0-9_\s]/g, '')
            .replace(/\s+/g, '_')
            .replace(/_+/g, '_')
            .replace(/^_|_$/g, '');
    });
});
</script>

@endsection
