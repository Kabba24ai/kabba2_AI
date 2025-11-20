<div class="space-y-6">
    <h3 class="text-lg font-semibold text-gray-800">Template Information</h3>

    {{-- Unified Row: Basic + Stock Information --}}
    <div class="grid grid-cols-2 gap-4 mb-4">

        {{-- Part Name --}}
        <div>
            <label for="part_name" class="block text-sm font-medium text-gray-700 mb-1 required">
                Template Name
            </label>
            <input type="text" id="part_name" name="part_name"
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md @error('part_name') border-red-500 @enderror"
                placeholder="Enter Template Name " required
                value="{{ old('part_name', $part->part_name ?? '') }}">
            @error('part_name')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Category --}}
        <div>
            <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">
                Category <span class="text-blue-600"> (Auto-assigned from Equipment) </span>
            </label>
            <input type="text" id="category_name" name="category_name"
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md @error('part_name') border-red-500 @enderror"
                readonly value="Not Assigned">
        </div>
    </div>

    <div class="mb-4">
        {{-- Description --}}
        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
        <textarea id="description" name="description" rows="4"
            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
            placeholder="Enter part description">{{ old('description', $part->description ?? '') }}</textarea>
    </div>

    <div class="grid grid-cols-5 gap-4">
        <!-- First column: Search -->
        <div class="col-span-4">
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1 required">
                Assigned Equipment IDS 
            </label>
            <div class="relative w-full">
                <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-5 w-5"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input type="text" name="search" value=""
                    placeholder="Search parts, equipment, suppliers..."
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md text-sm">
            </div>
        </div>

        <!-- Second column: Category dropdown -->
        <div class="col-span-1">
            <label for="categories_selected" class="block text-sm font-medium text-gray-700 mb-1">
                All Category
            </label>
            <select id="categories_selected" name="categories"
                class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm bg-white">
                <option value="">All Categories</option>
                @foreach($categories as $category)
                <option value="{{ $category->id }}">{{ $category->title }}</option>
                @endforeach
            </select>
        </div>
    </div>


    <!--  Scrollable Product List -->
    <div id="productList" class="mt-4 mb-3 border border-gray-200 rounded-lg h-64 overflow-y-auto p-3 bg-gray-50">
        @foreach($categories as $category)
        <div class="mb-3" data-category-id="{{ $category->id }}">
            <!-- Category title -->
            <h3 class="text-sm font-semibold text-gray-700 mb-2">
                {{ $category->title }}
            </h3>

            <!-- Products under this category -->
            <ul class="space-y-1">
                @foreach($category->products as $product)
                <li class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer rounded pl-3">
                    <input
                        type="checkbox"
                        name="selected_product"
                        value="{{ $product->id }}"
                        id="product_{{ $product->id }}"
                        data-category="{{ $category->title }}"
                        class="product-checkbox rounded text-gray-600 h-4 w-4 ">
                    <label for="product_{{ $product->id }}" class="cursor-pointer flex-1">
                        {{ $product->product_name }} ({{ $product->unique_id }})
                    </label>
                </li>
                @endforeach
            </ul>
        </div>
        @endforeach
    </div>
    <p class="text-sm text-gray-500">Select one or more equipment. All equipment must be from the same category.</p>
</div>





@push('js')
 

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkboxes = document.querySelectorAll('.product-checkbox');
        const categoryInput = document.getElementById('category_name');
        const searchInput = document.querySelector('input[name="search"]');
        const productList = document.getElementById('productList');
        const categoryDropdown = document.getElementById('categories_selected');

        /* -------------------------------
             SINGLE PRODUCT SELECTION
        --------------------------------*/
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    checkboxes.forEach(cb => {
                        if (cb !== this) cb.checked = false;
                    });

                    const categoryName = this.dataset.category;
                    categoryInput.value = categoryName;
                } else {
                    categoryInput.value = 'Not Assigned';
                }
            });
        });

        /* -------------------------------
            ADVANCED SEARCH FUNCTIONALITY
        --------------------------------*/
        searchInput.addEventListener('input', filterProducts);
        categoryDropdown.addEventListener('change', filterProducts);

        function filterProducts() {
            const searchValue = searchInput.value.toLowerCase();
            const selectedCategoryId = categoryDropdown.value;

            // Loop through all category sections
            productList.querySelectorAll('div.mb-3').forEach(categoryBlock => {
                const categoryTitle = categoryBlock.querySelector('h3').textContent.toLowerCase();
                const categoryId = categoryBlock.getAttribute('data-category-id');
                let hasVisibleProduct = false;

                // Show/hide based on category dropdown
                const categoryVisible = !selectedCategoryId || selectedCategoryId === categoryId;

                categoryBlock.querySelectorAll('li').forEach(productItem => {
                    const productName = productItem.textContent.toLowerCase();
                    const uniqueId = productItem.textContent.match(/\(([^)]+)\)/)?.[1]?.toLowerCase() || '';
                    const combinedText = `${productName} ${uniqueId} ${categoryTitle}`; // combined searchable fields

                    const matchesSearch = combinedText.includes(searchValue);
                    const shouldShow = matchesSearch && categoryVisible;

                    productItem.style.display = shouldShow ? 'flex' : 'none';
                    if (shouldShow) hasVisibleProduct = true;
                });

                // Hide entire category block if no visible products match
                categoryBlock.style.display = hasVisibleProduct ? 'block' : 'none';
            });
        }

        // Initialize data-category-id attributes for filtering
        productList.querySelectorAll('div.mb-3').forEach((categoryBlock, index) => {
            const category = categoryBlock.querySelector('h3').textContent.trim();
            const matchedOption = Array.from(categoryDropdown.options).find(opt => opt.text.trim() === category);
            if (matchedOption) {
                categoryBlock.setAttribute('data-category-id', matchedOption.value);
            }
        });
    });
</script>


@endpush
