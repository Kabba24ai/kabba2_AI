<div x-data x-init="initCategorySortable()"
    class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-md font-semibold text-gray-700 dark:text-white">Category Hierarchy</h3>
        <a href="{{ route('admin.product-management.categories.create') }}"
            class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500">
            + Create
        </a>
    </div>

    @if ($category_tree->isNotEmpty())
        <ul id="category-sort-root" class="space-y-3">
            @foreach ($category_tree as $category)
                @include('admin.product_management.categories.partials._category-node', [
                    'category' => $category,
                    'addFlag' => true,
                ])
            @endforeach
        </ul>
    @else
        <p class="text-sm text-gray-500 dark:text-gray-400">No categories available.</p>
    @endif
</div>

@push('js')
    <script>
        const sortOrderRoute = '{{ route('admin.product-management.categories.sort-order') }}';

        function initCategorySortable() {
            // Helper to apply sortable to a single container
            const applySortable = (element) => {
                Sortable.create(element, {
                    animation: 150,
                    handle: '.cursor-move',
                    draggable: '.sortable-category-item',
                    group: {
                        name: 'nested',
                        pull: false, // Prevent moving out of this list
                        put: false // Prevent others from inserting into this list
                    },
                    fallbackOnBody: true,
                    onEnd: function(evt) {
                        const sortedIds = Array.from(evt.to.children)
                            .filter(item => item.dataset.id) // Ignore non-category nodes
                            .map(item => item.dataset.id);

                        // Send to server via fetch
                        fetch(sortOrderRoute, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                        .content
                                },
                                body: JSON.stringify({
                                    ids: sortedIds // array of category ids in new order
                                })
                            })
                            .then(res => res.json())
                            .then(data => {
                                notyf.success(data.message);
                            });
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
