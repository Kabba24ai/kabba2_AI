<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm p-6">
    <h3 class="text-md font-semibold text-gray-700 dark:text-white mb-4">Category Hierarchy</h3>

    @if ($category_tree->isNotEmpty())
        <ul class="space-y-4 text-sm text-gray-700 dark:text-gray-300">
            @foreach ($category_tree as $category)
                @include('admin.product_management.categories.partials._category-node', ['category' => $category, 'isRoot' => true])
            @endforeach
        </ul>
    @else
        <p class="text-sm text-gray-500 dark:text-gray-400">No categories available at the moment.</p>
    @endif
</div>
