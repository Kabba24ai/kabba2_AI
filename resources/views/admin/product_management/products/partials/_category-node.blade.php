@php
    $checkboxId = "category_{$category->id}";

    // Safely handle when $objProduct is not set (e.g., during create)
    $selectedCategories = isset($objProduct)
        ? $objProduct->categories?->pluck('id')->toArray() ?? []
        : [];

    $isChecked = in_array($category->id, old('categories', $selectedCategories));
@endphp


<li>
    <div class="flex items-center gap-2">
        <input
            type="checkbox"
            name="categories[]"
            value="{{ $category->id }}"
            id="{{ $checkboxId }}"
            @checked($isChecked)
            class="h-4 w-4 text-blue-600 border-gray-300 rounded dark:bg-gray-800 dark:border-gray-600"
        >
        <label for="{{ $checkboxId }}" class="text-sm text-gray-800 dark:text-gray-200">
            {{ $category->title }}
        </label>
    </div>

    @if ($category->childCategories && $category->childCategories->count())
        <ul class="ml-5 mt-2 space-y-2 border-l border-gray-300 dark:border-gray-700 pl-3">
            @foreach ($category->childCategories as $child)
                @include('admin.product_management.products.partials._category-node', ['category' => $child])
            @endforeach
        </ul>
    @endif
</li>
