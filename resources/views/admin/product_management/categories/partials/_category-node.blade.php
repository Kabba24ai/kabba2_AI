<li class="sortable-category-item" data-id="{{ $category->id }}">
    <div
        class="flex items-center justify-between bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-3 shadow-sm hover:shadow-md transition group">
        <div class="flex items-center gap-3">
            <!-- Drag Handle -->
            <div data-drag-handle class="text-gray-400 hover:text-gray-600 cursor-move">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 group-hover:text-gray-600"
                    fill="currentColor" viewBox="0 0 20 20">
                    <circle cx="5" cy="5" r="1.5" />
                    <circle cx="10" cy="5" r="1.5" />
                    <circle cx="5" cy="10" r="1.5" />
                    <circle cx="10" cy="10" r="1.5" />
                    <circle cx="5" cy="15" r="1.5" />
                    <circle cx="10" cy="15" r="1.5" />
                </svg>
            </div>

            <!-- Title -->
            <span class="text-gray-800 dark:text-white font-medium truncate max-w-[200px]">
                {{ $category->title }}
            </span>

            <!-- Status -->
            <span class="ml-2 text-xs px-2 py-0.5 rounded bg-green-100 text-green-700">
                Published
            </span>
        </div>

        <!-- Action Icons -->
        <div class="flex gap-2 text-gray-400 dark:text-gray-500 text-sm">
            <a href="#" title="View" class="hover:text-blue-500"><x-heroicon-o-eye class="w-5 h-5" /></a>

            <a href="{{ route('admin.product-management.categories.create', ['categoryId' => $category->id]) }}"
                title="Add Subcategory" class="hover:text-green-500">
                <x-heroicon-o-plus-circle class="w-5 h-5" />
            </a>

            <a href="{{ route('admin.product-management.categories.edit', $category->unique_id) }}" title="Edit"
                class="hover:text-blue-500">
                <x-heroicon-s-pencil-square class="w-5 h-5" />
            </a>

            <form method="POST"
                action="{{ route('admin.product-management.categories.delete', $category->unique_id) }}"
                onsubmit="return confirm('Are you sure you want to delete this category?');">
                @csrf
                @method('DELETE')
                <button type="submit" title="Delete" class="hover:text-red-500">
                    <x-heroicon-s-trash class="w-5 h-5" />
                </button>
            </form>
        </div>
    </div>

    @if ($category->childCategories->isNotEmpty())
        <ul class="nested-sortable mt-2 ml-6 space-y-3 border-l-2 border-gray-200 dark:border-gray-700 pl-4">
            @foreach ($category->childCategories as $child)
                @include('admin.product_management.categories.partials._category-node', [
                    'category' => $child,
                ])
            @endforeach
        </ul>
    @endif
</li>
