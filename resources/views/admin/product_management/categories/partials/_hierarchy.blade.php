<div x-data x-init="initCategorySortable()" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm p-6">
    <h3 class="text-md font-semibold text-gray-700 dark:text-white mb-4">Category Hierarchy</h3>

    @if ($category_tree->isNotEmpty())
        <ul id="category-sort-root" class="space-y-3">
            @foreach ($category_tree as $category)
                @include('admin.product_management.categories.partials._category-node', ['category' => $category])
            @endforeach
        </ul>
    @else
        <p class="text-sm text-gray-500 dark:text-gray-400">No categories available.</p>
    @endif
</div>

@push('js')
<script>
    function initCategorySortable() {
        // Helper to apply sortable to a single container
        const applySortable = (element) => {
            Sortable.create(element, {
                animation: 150,
                handle: '.cursor-move',
                draggable: '.sortable-category-item',
                group: 'nested', // Important for nested sorting
                fallbackOnBody: true,
                onEnd: function (evt) {
                    const sortedIds = Array.from(evt.to.children).map(item => item.dataset.id);
                    console.log('Sorted IDs in container:', sortedIds);
                    // TODO: send to server if needed
                }
            });
        };

        // Apply to root list
        const root = document.getElementById('category-sort-root');
        if (root) {
            applySortable(root);
        }

        // Apply to all nested sortable lists
        const nestedLists = document.querySelectorAll('.nested-sortable');
        nestedLists.forEach(applySortable);
    }

    // Auto-init if Alpine or other framework uses it
    document.addEventListener('DOMContentLoaded', () => {
        initCategorySortable();
    });
</script>
@endpush

