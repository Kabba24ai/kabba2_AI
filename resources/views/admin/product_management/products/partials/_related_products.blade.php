<div class="border border-gray-200 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 p-4 space-y-4">
    <h3 class="text-sm font-semibold text-gray-800 dark:text-white border-b pb-2">Related products</h3>

    <!-- Search Input -->
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search products</label>
        <input
            type="text"
            placeholder="Search products"
            x-model="search"
            class="w-full border border-gray-300 rounded px-3 py-2 text-sm dark:bg-gray-900 dark:text-white dark:border-gray-700"
        />
    </div>

    <!-- Selected Products -->
    <div>
        <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
            Selected products:
            <span class="italic text-xs text-gray-500">Click and Drag to Reorder Products</span>
        </label>

        <ul
            x-ref="sortable"
            x-init="initSortable()"
            class="flex flex-wrap gap-4"
        >
            <template x-for="(product, index) in filteredProducts" :key="product.id">
                <li class="cursor-move w-32 text-center space-y-2">
                    <div class="aspect-square border border-gray-300 dark:border-gray-700 rounded overflow-hidden">
                        <img :src="product.image" alt="" class="w-full h-full object-cover" />
                    </div>
                    <div class="text-sm text-gray-700 dark:text-white truncate" x-text="product.name"></div>
                    <button
                        @click="remove(index)"
                        class="text-gray-400 hover:text-red-600"
                        title="Remove"
                    >
                        <x-heroicon-o-trash class="w-5 h-5 mx-auto" />
                    </button>
                </li>
            </template>
        </ul>
    </div>
</div>

@push('js')
<script>
    function relatedProducts() {
        return {
            search: '',
            products: @json($relatedProducts ?? []),
            get filteredProducts() {
                return this.search
                    ? this.products.filter(p => p.name.toLowerCase().includes(this.search.toLowerCase()))
                    : this.products;
            },
            remove(index) {
                this.products.splice(index, 1);
            },
            initSortable() {
                Sortable.create(this.$refs.sortable, {
                    animation: 150,
                    handle: '.cursor-move',
                    onEnd: (evt) => {
                        const moved = this.products.splice(evt.oldIndex, 1)[0];
                        this.products.splice(evt.newIndex, 0, moved);
                    }
                });
            }
        }
    }
</script>
@endpush
