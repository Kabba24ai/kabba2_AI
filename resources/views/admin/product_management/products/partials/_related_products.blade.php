@php
    // Map your relation into exactly the shape your JS expects
    $initialRelated = old(
        'related_products',
        isset($objProduct)
            // edit-mode: pull from the relationship
            ? $objProduct->relatedProducts->map(fn($p) => [
                  'id'           => $p->id,
                  'product_name' => $p->product_name,
                  'image_url'    => $p->image_url,
              ])->toArray()
            // create-mode: empty array
            : []
    );
@endphp

<!-- 1. Related Products Component -->
<div id="related-products"
    class="border border-gray-200 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 p-4 space-y-4">

    <h3 class="text-sm font-semibold text-gray-800 dark:text-white border-b pb-2">
        Related products
    </h3>

    <!-- Search Input + Suggestions -->
    <div class="relative">
        <label for="rp-search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Search products
        </label>
        <input type="text" id="rp-search"
            class="w-full border border-gray-300 rounded px-3 py-2 text-sm
             dark:bg-gray-900 dark:text-white dark:border-gray-700
             focus:outline-none focus:ring-2 focus:ring-brand-500"
            placeholder="Type to search…" autocomplete="off" />

        <!-- suggestions dropdown -->
        <ul id="rp-suggestions"
            class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-900
             border border-gray-300 dark:border-gray-700 rounded
             shadow-lg overflow-auto max-h-60 hidden">
        </ul>
    </div>

    <!-- Selected Products -->
    <div>
        <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
            Selected products:
            {{-- <span class="italic text-xs text-gray-500">Click and Drag to Reorder Products</span> --}}
        </label>
        <ul id="rp-selected" class="flex flex-wrap gap-4"></ul>
    </div>

    <!-- New container for array fields -->
    <div id="rp-values"></div>

</div>

@push('js')
    <script>
        const initialRelated = @json($initialRelated);

        document.addEventListener('DOMContentLoaded', () => {
            // <-- use the pre-loaded ones (or empty array)
            let selectedProducts = Array.isArray(initialRelated) ? initialRelated.slice() : [];

            const searchInput = document.getElementById('rp-search');
            const suggestionsEl = document.getElementById('rp-suggestions');
            const selectedEl = document.getElementById('rp-selected');
            const hiddenInput = document.getElementById('rp-value');
            let controller = null;
            let debounceTimer = null;

            // update hidden input value
            const valuesContainer = document.getElementById('rp-values');

            const updateHidden = () => {
                // Clear previous
                valuesContainer.innerHTML = '';
                selectedProducts.forEach((p) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'related_products[]';
                    input.value = p.id;
                    valuesContainer.appendChild(input);
                });
            };


            // render selected product “chips”
            const renderSelected = () => {
                selectedEl.innerHTML = '';
                selectedProducts.forEach((prod, i) => {
                    const li = document.createElement('li');
                    li.className = 'relative w-32 text-center';
                    li.innerHTML = `
            <div class="aspect-square border rounded overflow-hidden">
              <img src="${prod.image_url}" alt="" class="w-full h-full object-cover" />
            </div>
            <p class="mt-1 text-sm truncate text-gray-700 dark:text-gray-200">
              ${prod.product_name}
            </p>
            <button data-index="${i}" type="button"
                    class="absolute -top-2 -right-2  w-6 h-6
      flex items-center justify-center
      bg-red-500 text-white
      rounded-full shadow
      hover:bg-red-600
      focus:outline-none focus:ring-2 focus:ring-red-300 dark:focus:ring-red-600
      transition"
                    aria-label="Remove ${prod.product_name}">
              ✕
            </button>
          `;
                    selectedEl.appendChild(li);
                });
                updateHidden();
            };

            // fetch & filter suggestions
            const fetchSuggestions = async (term) => {
                if (controller) controller.abort();
                controller = new AbortController();
                try {
                    const url = '{{ route('admin.product-management.products.search', [':search', $objProduct->id ?? 0]) }}';
                    const res = await fetch(
                        url.replace(':search', encodeURIComponent(term)),
                        {
                            signal: controller.signal
                        }
                    );
                    const matches = await res.json();
                    return matches.filter(p =>
                        !selectedProducts.some(sp => sp.id === p.id)
                    );
                } catch (err) {
                    if (err.name !== 'AbortError') console.error(err);
                    return [];
                }
            };

            // debounce + render suggestions
            const renderSuggestions = () => {
                const term = searchInput.value.trim();
                clearTimeout(debounceTimer);

                if (!term) {
                    suggestionsEl.classList.add('hidden');
                    return;
                }

                debounceTimer = setTimeout(async () => {
                    const list = await fetchSuggestions(term);
                    suggestionsEl.innerHTML = '';

                    if (!list.length) {
                        suggestionsEl.classList.add('hidden');
                        return;
                    }

                    const frag = document.createDocumentFragment();
                    list.forEach(prod => {
                        const li = document.createElement('li');
                        li.className =
                            'flex items-center px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer';
                        li.dataset.id = prod.id;
                        li.dataset.name = prod.product_name;
                        li.dataset.img = prod.image_url;
                        li.innerHTML = `
              <img src="${prod.image_url}" alt="" class="w-8 h-8 object-cover rounded mr-2" />
              <span class="text-sm text-gray-800 dark:text-gray-200 truncate">
                ${prod.product_name}
              </span>
            `;
                        frag.appendChild(li);
                    });

                    suggestionsEl.appendChild(frag);
                    suggestionsEl.classList.remove('hidden');
                }, 300);
            };

            // close when clicking outside
            document.addEventListener('click', e => {
                if (!searchInput.contains(e.target) && !suggestionsEl.contains(e.target)) {
                    suggestionsEl.classList.add('hidden');
                }
            });

            // pick a suggestion
            suggestionsEl.addEventListener('click', e => {
                const li = e.target.closest('li');
                if (!li) return;

                const id = li.dataset.id.toString();
                // ↙︎ guard against duplicates
                if (selectedProducts.some(p => p.id.toString() === id)) {
                    // hide + clear
                    suggestionsEl.classList.add('hidden');
                    searchInput.value = '';
                    return;
                }

                selectedProducts.push({
                    id,
                    product_name: li.dataset.name,
                    image_url: li.dataset.img
                });
                renderSelected();
                suggestionsEl.classList.add('hidden');
                searchInput.value = '';
            });


            // remove button
            selectedEl.addEventListener('click', e => {
                if (!e.target.matches('button')) return;
                const idx = +e.target.dataset.index;
                selectedProducts.splice(idx, 1);
                renderSelected();
            });

            // make list sortable
            Sortable.create(selectedEl, {
                animation: 150,
                onEnd: (evt) => {
                    const [moved] = selectedProducts.splice(evt.oldIndex, 1);
                    selectedProducts.splice(evt.newIndex, 0, moved);
                    updateHidden();
                }
            });

            // wire up the search box
            searchInput.addEventListener('input', renderSuggestions);
            searchInput.addEventListener('focus', renderSuggestions);

            // initial render
            renderSelected();
        });
    </script>
@endpush
