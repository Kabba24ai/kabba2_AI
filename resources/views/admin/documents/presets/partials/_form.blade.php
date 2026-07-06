{{-- Shared preset form fields. Expects: $categories (alphabetical), optional $preset, $selectedIds --}}
@php
    $selected = collect(old('category_ids', $selectedIds ?? []))->map(fn ($id) => (int) $id)->all();
@endphp

<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Preset Name <span class="text-red-500">*</span></label>
            <input type="text" name="name" id="name" maxlength="120" required
                value="{{ old('name', $preset->name ?? '') }}"
                class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="e.g. Dirt Work / Grading">
            @error('name') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-1.5">Display Order</label>
            <input type="number" name="sort_order" id="sort_order" min="0" max="65535"
                value="{{ old('sort_order', $preset->sort_order ?? '') }}"
                class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="Optional — lower shows first; blank sorts alphabetically">
            @error('sort_order') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="lg:col-span-2">
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1.5">Description</label>
            <input type="text" name="description" id="description" maxlength="500"
                value="{{ old('description', $preset->description ?? '') }}"
                class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="Shown under the preset name on the generator, e.g. the equipment types it covers">
            @error('description') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="lg:col-span-2">
            <label for="thumbnail" class="block text-sm font-medium text-gray-700 mb-1.5">
                Thumbnail Image <span class="text-xs text-gray-400">(optional — shown on the preset card)</span>
            </label>
            <div class="flex items-center gap-4">
                @if (!empty(($preset->thumbnail_url ?? null)))
                    <img src="{{ $preset->thumbnail_url }}" alt="Current thumbnail"
                        class="w-20 h-14 object-cover rounded-lg border border-gray-200 shrink-0">
                @endif
                <div>
                    <input type="file" name="thumbnail" id="thumbnail" accept="image/jpeg,image/png,image/webp"
                        class="block text-sm text-gray-600 file:mr-3 file:px-3 file:py-1.5 file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 file:text-sm file:font-medium hover:file:bg-blue-100 cursor-pointer">
                    <p class="mt-1 text-xs text-gray-400">JPG, PNG, or WebP up to 4 MB. Uploading replaces the current image.</p>
                    @if (!empty(($preset->thumbnail_url ?? null)))
                        <label class="inline-flex items-center gap-2 text-xs text-gray-600 cursor-pointer mt-1">
                            <input type="checkbox" name="remove_thumbnail" value="1" class="rounded border-gray-300">
                            Remove current thumbnail
                        </label>
                    @endif
                </div>
            </div>
            @error('thumbnail') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="lg:col-span-2">
            <label class="inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300"
                    @checked((bool) old('is_active', $preset->is_active ?? true))>
                Active — show this preset on the Customer Price List generator
            </label>
            @error('is_active') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
    </div>
</div>

<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
    <div class="flex items-center justify-between gap-3 mb-1">
        <h2 class="text-sm font-semibold text-gray-800">Categories <span class="text-red-500">*</span></h2>
        <span id="preset-selected-count" class="text-xs text-gray-400"></span>
    </div>
    <p class="text-xs text-gray-400 mb-4">
        Selecting this preset on the generator pre-checks these categories. Staff can still add or
        remove categories before generating.
    </p>

    @error('category_ids') <p class="text-sm text-red-600 mb-3">{{ $message }}</p> @enderror
    @error('category_ids.*') <p class="text-sm text-red-600 mb-3">{{ $message }}</p> @enderror

    @if ($categories->isEmpty())
        <p class="text-sm text-gray-400 italic">No published categories were found.</p>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
            @foreach ($categories as $category)
                <label class="flex items-center gap-2.5 px-3 py-2 rounded-lg border border-gray-200 hover:bg-gray-50 cursor-pointer text-sm text-gray-800">
                    <input type="checkbox" name="category_ids[]" value="{{ $category->id }}"
                        class="preset-category rounded border-gray-300"
                        @checked(in_array($category->id, $selected, true))>
                    {{ $category->title }}
                </label>
            @endforeach
        </div>
    @endif
</div>

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const boxes = Array.from(document.querySelectorAll('.preset-category'));
    const count = document.getElementById('preset-selected-count');

    function refreshCount() {
        const n = boxes.filter(b => b.checked).length;
        if (count) count.textContent = n ? n + ' selected' : '';
    }

    boxes.forEach(b => b.addEventListener('change', refreshCount));
    refreshCount();
});
</script>
@endpush
