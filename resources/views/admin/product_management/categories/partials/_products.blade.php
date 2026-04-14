@php
    $initialSelectedItems = [];

    if (isset($objProductCategory)) {
        $children = $objProductCategory->categoryChildren()
            ->with([
                'product:id,product_name,media_id',
                'subCategory:id,title,media_id,parent_id',
            ])
            ->get();

        foreach ($children as $child) {
            if (!is_null($child->product_id) && !is_null($child->product)) {
                $initialSelectedItems[] = [
                    'id' => $child->product->id,
                    'name' => $child->product->product_name,
                    'image_url' => $child->product->image_url,
                    '_type' => 'product',
                ];
                continue;
            }

            if (!is_null($child->sub_category_id) && !is_null($child->subCategory)) {
                $initialSelectedItems[] = [
                    'id' => $child->subCategory->id,
                    'name' => $child->subCategory->title,
                    'image_url' => $child->subCategory->image_url,
                    '_type' => 'subcategory',
                ];
            }
        }
    }
@endphp

<!-- 1. Add Products Component -->
<div id="related-products"
    class="border border-gray-200 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 p-4 space-y-4">

    <h3 class="text-sm font-semibold text-gray-800 dark:text-white border-b pb-2">
        Add Products / Subcategories
    </h3>

    <!-- Search Inputs + Suggestions -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="relative">
            <label for="rp-search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Search products
            </label>
            <input type="text" id="rp-search"
                class="w-full border border-gray-300 rounded px-3 py-2 text-sm
                 dark:bg-gray-900 dark:text-white dark:border-gray-700
                 focus:outline-none focus:ring-2 focus:ring-brand-500"
                placeholder="Type to search..." autocomplete="off" />

            <!-- suggestions dropdown -->
            <ul id="rp-suggestions"
                class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-900
                 border border-gray-300 dark:border-gray-700 rounded
                 shadow-lg overflow-auto max-h-60 hidden">
            </ul>
        </div>

        <div class="relative">
            <label for="rc-search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Search subcategories
            </label>
            <input type="text" id="rc-search"
                class="w-full border border-gray-300 rounded px-3 py-2 text-sm
                 dark:bg-gray-900 dark:text-white dark:border-gray-700
                 focus:outline-none focus:ring-2 focus:ring-brand-500"
                placeholder="Type to search..." autocomplete="off" />

            <ul id="rc-suggestions"
                class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-900
                 border border-gray-300 dark:border-gray-700 rounded
                 shadow-lg overflow-auto max-h-60 hidden">
            </ul>
        </div>
    </div>

    <!-- Selected Products -->
    <div>
        {{-- <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
            Selected products:
            <span class="italic text-xs text-gray-500">Click and Drag to Reorder Products</span>
        </label> --}}
        <ul id="rp-selected" class="flex flex-wrap gap-4"></ul>
    </div>

    <!-- New container for array fields -->
    <div id="rp-values"></div>

</div>

@push('js')
    <script>
        const initialSelectedItems = @json($initialSelectedItems);

        document.addEventListener('DOMContentLoaded', () => {
            // Unified item list: each entry has { id, name, image_url, _type }
            let selectedItems = Array.isArray(initialSelectedItems) ? initialSelectedItems.slice() : [];

            const searchInput          = document.getElementById('rp-search');
            const suggestionsEl        = document.getElementById('rp-suggestions');
            const categorySearchInput  = document.getElementById('rc-search');
            const categorySuggestionsEl = document.getElementById('rc-suggestions');
            const selectedEl           = document.getElementById('rp-selected');
            const valuesContainer      = document.getElementById('rp-values');

            let controller         = null;
            let categoryController = null;
            let debounceTimer      = null;
            let categoryDebounceTimer = null;

            const toStr = (v) => v?.toString();

            // Emit ordered rows where each row contains either product_id or sub_category_id
            const updateHidden = () => {
                valuesContainer.innerHTML = '';
                let productRowIndex = 0;
                let sortOrder = 1;

                selectedItems.forEach((item) => {
                    const currentSortOrder = sortOrder++;

                    const productInput = document.createElement('input');
                    productInput.type = 'hidden';
                    productInput.name = `products[${productRowIndex}][product_id]`;
                    productInput.value = item._type === 'product' ? item.id : '';
                    valuesContainer.appendChild(productInput);

                    const subcategoryInput = document.createElement('input');
                    subcategoryInput.type = 'hidden';
                    subcategoryInput.name = `products[${productRowIndex}][sub_category_id]`;
                    subcategoryInput.value = item._type === 'subcategory' ? item.id : '';
                    valuesContainer.appendChild(subcategoryInput);

                    const sortOrderInput = document.createElement('input');
                    sortOrderInput.type = 'hidden';
                    sortOrderInput.name = `products[${productRowIndex}][sort_order]`;
                    sortOrderInput.value = currentSortOrder;
                    valuesContainer.appendChild(sortOrderInput);

                    productRowIndex += 1;
                });
            };

            // Render chip grid
            const renderSelected = () => {
                selectedEl.innerHTML = '';
                selectedItems.forEach((item, i) => {
                    const isSubcat = item._type === 'subcategory';
                    const li = document.createElement('li');
                    li.className = 'relative w-32 text-center';
                    li.innerHTML = `
                        <div class="aspect-square border-2 ${isSubcat ? 'border-brand-500' : 'border-gray-200 dark:border-gray-600'} rounded overflow-hidden">
                            <img src="${item.image_url}" alt="" class="w-full h-full object-cover" />
                        </div>
                        ${isSubcat ? `<span class="absolute top-0 left-0 text-[10px] bg-brand-500 text-white px-1 rounded-br leading-tight">Subcat</span>` : ''}
                        <p class="mt-1 text-sm truncate text-gray-700 dark:text-gray-200">${item.name}</p>
                        <button data-index="${i}" type="button"
                                class="absolute -top-2 -right-2 w-6 h-6
                                       flex items-center justify-center
                                       bg-red-500 text-white rounded-full shadow
                                       hover:bg-red-600 focus:outline-none focus:ring-2
                                       focus:ring-red-300 dark:focus:ring-red-600 transition"
                                aria-label="Remove ${item.name}">
                            X
                        </button>
                    `;
                    selectedEl.appendChild(li);
                });
                updateHidden();
            };

            // Product search
            const fetchSuggestions = async (term) => {
                suggestionsEl.innerHTML = `
                    <li class="flex items-center justify-center py-4">
                        <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        <span class="ml-2 text-gray-500 text-sm">Loading...</span>
                    </li>`;
                suggestionsEl.classList.remove('hidden');

                if (controller) controller.abort();
                controller = new AbortController();
                try {
                    const url = "{{ route('admin.product-management.products.search', [':search', '']) }}";
                    const res = await fetch(url.replace(':search', encodeURIComponent(term)), { signal: controller.signal });
                    const matches = await res.json();
                    return (matches.products || []).filter(p =>
                        !selectedItems.some(s => s._type === 'product' && toStr(s.id) === toStr(p.id))
                    );
                } catch (err) {
                    if (err.name !== 'AbortError') console.error(err);
                    return [];
                }
            };

            const renderSuggestions = () => {
                const term = searchInput.value.trim();
                clearTimeout(debounceTimer);
                if (!term) { suggestionsEl.classList.add('hidden'); return; }

                debounceTimer = setTimeout(async () => {
                    const list = await fetchSuggestions(term);
                    suggestionsEl.innerHTML = '';

                    if (!list.length) {
                        suggestionsEl.innerHTML = `<li class="px-3 py-2 text-gray-500 text-sm">No products available</li>`;
                        suggestionsEl.classList.remove('hidden');
                        return;
                    }

                    const frag = document.createDocumentFragment();
                    list.forEach(prod => {
                        const li = document.createElement('li');
                        li.className = 'flex items-center px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer';
                        li.dataset.id   = prod.id;
                        li.dataset.name = prod.product_name;
                        li.dataset.img  = prod.image_url;
                        li.innerHTML = `
                            <img src="${prod.image_url}" alt="" class="w-8 h-8 object-cover rounded mr-2" />
                            <span class="text-sm text-gray-800 dark:text-gray-200 truncate">${prod.product_name}</span>`;
                        frag.appendChild(li);
                    });
                    suggestionsEl.appendChild(frag);
                    suggestionsEl.classList.remove('hidden');
                }, 300);
            };

            // Subcategory search
            const fetchSubcategorySuggestions = async (term) => {
                categorySuggestionsEl.innerHTML = `
                    <li class="flex items-center justify-center py-4">
                        <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        <span class="ml-2 text-gray-500 text-sm">Loading...</span>
                    </li>`;
                categorySuggestionsEl.classList.remove('hidden');

                if (categoryController) categoryController.abort();
                categoryController = new AbortController();
                try {
                    const url = "{{ route('admin.product-management.categories.search-subcategories', [':search']) }}";
                    const res = await fetch(url.replace(':search', encodeURIComponent(term || '')), { signal: categoryController.signal });
                    const matches = await res.json();
                    return (matches.subcategories || []).filter(c =>
                        !selectedItems.some(s => s._type === 'subcategory' && toStr(s.id) === toStr(c.id))
                    );
                } catch (err) {
                    if (err.name !== 'AbortError') console.error(err);
                    return [];
                }
            };

            const renderSubcategorySuggestions = () => {
                const term = categorySearchInput.value.trim();
                clearTimeout(categoryDebounceTimer);
                if (!term) { categorySuggestionsEl.classList.add('hidden'); return; }

                categoryDebounceTimer = setTimeout(async () => {
                    const list = await fetchSubcategorySuggestions(term);
                    categorySuggestionsEl.innerHTML = '';

                    if (!list.length) {
                        categorySuggestionsEl.innerHTML = `<li class="px-3 py-2 text-gray-500 text-sm">No subcategories available</li>`;
                        categorySuggestionsEl.classList.remove('hidden');
                        return;
                    }

                    const frag = document.createDocumentFragment();
                    list.forEach(cat => {
                        const li = document.createElement('li');
                        li.className = 'flex items-center px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer';
                        li.dataset.id  = cat.id;
                        li.dataset.name = cat.title;
                        li.dataset.img  = cat.image_url;
                        li.innerHTML = `
                            <img src="${cat.image_url}" alt="" class="w-8 h-8 object-cover rounded mr-2 flex-shrink-0" />
                            <div class="min-w-0">
                                <div class="text-sm text-gray-800 dark:text-gray-200 truncate">${cat.title}</div>
                                ${cat.parent_title ? `<div class="text-xs text-gray-500 dark:text-gray-400 truncate">${cat.parent_title}</div>` : ''}
                            </div>`;
                        frag.appendChild(li);
                    });
                    categorySuggestionsEl.appendChild(frag);
                    categorySuggestionsEl.classList.remove('hidden');
                }, 300);
            };

            // Close on outside click
            document.addEventListener('click', e => {
                if (!searchInput.contains(e.target) && !suggestionsEl.contains(e.target)) {
                    suggestionsEl.classList.add('hidden');
                }
                if (!categorySearchInput.contains(e.target) && !categorySuggestionsEl.contains(e.target)) {
                    categorySuggestionsEl.classList.add('hidden');
                }
            });

            // Pick product suggestion
            suggestionsEl.addEventListener('click', e => {
                const li = e.target.closest('li[data-id]');
                if (!li) return;
                const id = toStr(li.dataset.id);
                if (selectedItems.some(s => s._type === 'product' && toStr(s.id) === id)) {
                    suggestionsEl.classList.add('hidden');
                    searchInput.value = '';
                    return;
                }
                selectedItems.push({ id, name: li.dataset.name, image_url: li.dataset.img, _type: 'product' });
                renderSelected();
                suggestionsEl.classList.add('hidden');
                searchInput.value = '';
            });

            // Pick subcategory suggestion (add subcategory itself as chip)
            categorySuggestionsEl.addEventListener('click', e => {
                const li = e.target.closest('li[data-id]');
                if (!li) return;
                const id = toStr(li.dataset.id);
                if (selectedItems.some(s => s._type === 'subcategory' && toStr(s.id) === id)) {
                    categorySuggestionsEl.classList.add('hidden');
                    categorySearchInput.value = '';
                    return;
                }
                selectedItems.push({ id, name: li.dataset.name, image_url: li.dataset.img, _type: 'subcategory' });
                renderSelected();
                categorySuggestionsEl.classList.add('hidden');
                categorySearchInput.value = '';
            });

            // Remove chip
            selectedEl.addEventListener('click', e => {
                const btn = e.target.closest('button[data-index]');
                if (!btn) return;
                selectedItems.splice(+btn.dataset.index, 1);
                renderSelected();
            });

            // Sortable
            Sortable.create(selectedEl, {
                animation: 150,
                onEnd: (evt) => {
                    const [moved] = selectedItems.splice(evt.oldIndex, 1);
                    selectedItems.splice(evt.newIndex, 0, moved);
                    updateHidden();
                }
            });

            searchInput.addEventListener('input', renderSuggestions);
            searchInput.addEventListener('focus', renderSuggestions);
            categorySearchInput.addEventListener('input', renderSubcategorySuggestions);
            categorySearchInput.addEventListener('focus', renderSubcategorySuggestions);

            renderSelected();
        });
    </script>
@endpush
