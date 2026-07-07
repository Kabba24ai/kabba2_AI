@extends('admin.layouts.app')

@section('title', 'Price List Presets')

@push('css')
<style>
    main { background-color: #f8fafc; flex: 1 1 auto; }
</style>
@endpush

@section('content')

    @include('flash::message')

    <div class="max-w-[1440px] mx-auto">

    @include('admin.documents.partials._price_list_nav', [
        'plSubtitle' => 'Industry presets shown on the Generate tab. A preset only pre-checks its categories — '
            . 'staff can always adjust the selection before generating.',
    ])

    <div class="flex items-center justify-end mb-4">
        <a href="{{ route('admin.documents.presets.create') }}"
            class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium text-sm transition">
            <x-heroicon-o-plus class="w-4 h-4" />
            New Preset
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        @if ($presets->isEmpty())
            <p class="text-sm text-gray-400 italic p-5">No presets yet — create the first one.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                            <th class="px-5 py-3">Preset</th>
                            <th class="px-5 py-3">Categories</th>
                            <th class="px-5 py-3">Sort</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($presets as $preset)
                            <tr class="hover:bg-gray-50/60 {{ $preset->is_active ? '' : 'opacity-60' }}">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        @if ($preset->thumbnail_url)
                                            <img src="{{ $preset->thumbnail_url }}" alt=""
                                                class="w-12 h-9 object-cover rounded-md border border-gray-200 shrink-0">
                                        @else
                                            <span class="w-12 h-9 grid place-items-center rounded-md border border-dashed border-gray-200 text-gray-300 shrink-0">
                                                <x-heroicon-o-photo class="w-4 h-4" />
                                            </span>
                                        @endif
                                        <span>
                                            <span class="block font-semibold text-gray-800">{{ $preset->name }}</span>
                                            @if ($preset->description)
                                                <span class="block text-xs text-gray-400 mt-0.5">{{ $preset->description }}</span>
                                            @endif
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-3 text-gray-600">
                                    {{ $preset->categories_count }} {{ Str::plural('category', $preset->categories_count) }}
                                </td>
                                <td class="px-5 py-3 text-gray-600">{{ $preset->sort_order ?? '—' }}</td>
                                <td class="px-5 py-3">
                                    @if ($preset->is_active)
                                        <span class="inline-flex items-center rounded-full bg-green-50 text-green-700 text-xs font-medium px-2.5 py-0.5">Active</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-gray-100 text-gray-500 text-xs font-medium px-2.5 py-0.5">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="{{ route('admin.documents.presets.edit', $preset) }}"
                                            class="text-blue-600 hover:text-blue-800 font-medium">Edit</a>

                                        <form method="POST" action="{{ route('admin.documents.presets.toggle', $preset) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-gray-500 hover:text-gray-700 font-medium">
                                                {{ $preset->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.documents.presets.destroy', $preset) }}"
                                            onsubmit="return confirm('Delete the preset &quot;{{ $preset->name }}&quot;? The price list generator will no longer offer it.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-500 hover:text-red-700 font-medium">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    </div>

@endsection
