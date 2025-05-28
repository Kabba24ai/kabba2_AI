<li>
    <div class="flex items-start justify-between group">
        <div class="flex flex-col">
            <span class="font-medium group-hover:text-brand-600 transition-colors">{{ $category->title }}</span>
        </div>

        <div class="flex gap-2 text-gray-500 dark:text-gray-400">
            {{-- View --}}
            <a href="#" target="_blank" class="hover:text-brand-600" title="View Front">
                <x-heroicon-m-arrow-top-right-on-square class="w-4 h-4" />
            </a>

            {{-- Add Subcategory (only show for root) --}}
            @if (!empty($isRoot))
                <a href="{{ route('admin.product-management.categories.create', ['categoryId' => $category->id]) }}"
                   class="hover:text-green-500" title="Add Subcategory">
                    <x-heroicon-o-plus-circle class="w-4 h-4" />
                </a>
            @endif

            {{-- Edit --}}
            <a href="{{ route('admin.product-management.categories.edit', $category->unique_id) }}"
               class="hover:text-blue-500" title="Edit">
                <x-heroicon-s-pencil-square class="w-4 h-4" />
            </a>

            {{-- Delete --}}
            <form action="{{ route('admin.product-management.categories.delete', $category->unique_id) }}"
                  method="POST"
                  onsubmit="return confirm('Are you sure you want to delete this category?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="hover:text-red-500" title="Delete">
                    <x-heroicon-s-trash class="w-4 h-4" />
                </button>
            </form>
        </div>
    </div>

    @if ($category->childCategories->isNotEmpty())
        <ul class="mt-2 space-y-2 ml-5 border-l-2 pl-3 border-gray-200 dark:border-gray-700">
            @foreach ($category->childCategories as $child)
                @include('admin.product_management.categories.partials._category-node', ['category' => $child, 'isRoot' => false])
            @endforeach
        </ul>
    @endif
</li>
