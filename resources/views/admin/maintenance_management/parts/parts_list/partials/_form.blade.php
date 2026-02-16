<div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
<div class="space-y-6">
   <h3 class="text-lg font-semibold text-gray-800">Parts List Information</h3>
   <input type="hidden" id="category_id" name="category_id" value="">
   {{-- Unified Row: Basic + Stock Information --}}
   <div class="grid grid-cols-3 gap-4 mb-4">
      {{-- Part Name --}}
      <div>
         <label for="part_name" class="block text-sm font-medium text-gray-700 mb-1 required">
         Parts List Name
         </label>
         <input type="text" id="part_name" name="part_name"
            class="w-full px-3 py-3 text-sm border border-gray-300 rounded-md @error('part_name') border-red-500 @enderror"
            placeholder="Enter Parts List Name " required
            value="{{ old('part_name', $list->name ?? '') }}">
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
            class="w-full px-3 py-3 text-sm border border-gray-300 rounded-md @error('part_name') border-red-500 @enderror"
            readonly value="{{ old('category_name', $list->category->title ?? 'Not Assigned') }}">
      </div>
      <div>
         <label for="categories_selected" class="block text-sm font-medium text-gray-700 mb-1">
         All Category
         </label>
         <select id="categories_selected" name="categories"
            class="w-full px-3 py-3 border border-gray-300 rounded-md text-sm bg-white">
            <option value=""> -- Select Category -- </option>
            @foreach($categories as $category)
            <option value="{{ $category->id }}"   {{ old('categories', $list->category_id ?? '') == $category->id ? 'selected' : '' }} >{{ $category->hierarchy_title  }}</option>
            @endforeach
         </select>
      </div>
   </div>
   <div class="grid grid-cols-2 gap-6 mb-4">
      <!-- LEFT SIDE (50%) -->
      <div>
         <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
         Description
         </label>
         <textarea id="description" name="description" rows="5"
            class="w-full px-3 py-3 border border-gray-300 rounded-md text-sm"
            placeholder="Enter part description">{{ old('description', $list->description ?? '') }}</textarea>
      </div>
      <div id="selectCategoryMessage"
        class="text-center text-sm text-gray-500 py-4 mt-4 mb-3 border border-gray-200 rounded-md h-auto overflow-y-auto p-3 bg-gray-50">
            Please select a category to view equipment.

    </div>

      <div id="equipmentSection" class="hidden">
        
         <!-- First column: Search -->
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
               class="w-full pl-10 pr-4 py-3 px-3 border border-gray-300 rounded-md text-sm">
         </div>

         
            <div id="emptyMessage"
                class="hidden text-center text-sm text-gray-500 py-4">
            </div>

         <!--  Scrollable Product List -->
         <div id="productList" class="mt-4 mb-3 border border-gray-200 rounded-md h-auto overflow-y-auto p-3 bg-gray-50">
            @foreach($categories as $category)
            <div class="mb-3" data-category-id="{{ $category->id }}">
               <!-- Category title -->
               <h3 class="text-sm font-semibold text-gray-700 mb-2">
                  {{ $category->hierarchy_title }}
               </h3>
               <!-- Products under this category -->
               <ul class="space-y-1">
                  @foreach($category->equipments as $equipments)
                  <li class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer rounded pl-3">
                     <input
                        type="checkbox"
                        name="selected_products[]"
                        value="{{ $equipments->id }}"
                        id="product_{{ $equipments->id }}"
                        data-category-id="{{ $category->id }}"
                        data-category-name="{{ $category->title }}"
                        class="product-checkbox rounded text-gray-600 h-4 w-4">
                     <label for="product_{{ $equipments->id }}" class="cursor-pointer flex-1">
                     {{ $equipments->equipment_name }} ({{ $equipments->equipment_id }})
                     </label>
                  </li>
                  @endforeach
               </ul>
            </div>
            @endforeach
         </div>
      </div>
   </div>
</div>
<div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm mt-6">
   <div class="flex flex-wrap items-start justify-between gap-4">
      <div>
         <h3 class="text-lg font-semibold text-gray-900">List Parts</h3>
         <p class="text-sm text-gray-500">
            Parts included in this template (<span id="templatePartsCount">0</span> parts)
         </p>
      </div>
      <button  type="button" onclick="openPartsModal()"
         class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-3 text-md font-medium text-white shadow hover:bg-blue-700 transition">
         <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
         </svg>
         Add Existing Parts
      </button>
   </div>
   <!-- Empty state -->
   <div id="templateEmpty" class="mt-6 border-2 border-dashed border-gray-200 rounded-lg p-12 text-center py-12">
      <div class="flex flex-col items-center justify-center space-y-3">
         <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-package h-12 w-12 text-gray-400 mx-auto mb-4">
            <path d="m7.5 4.27 9 5.15"></path>
            <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path>
            <path d="m3.3 7 8.7 5 8.7-5"></path>
            <path d="M12 22V12"></path>
         </svg>
         <p class="text-gray-700 font-medium">No parts added yet</p>
         <p class="text-gray-500 text-sm">Add parts to this template.</p>
         <button  type="button" onclick="openPartsModal()"
            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-3 text-md font-medium text-white shadow hover:bg-blue-700 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
               <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            Add Parts
         </button>
      </div>
   </div>
   <!-- Filled: mobile cards -->
   <div id="templateMobile" class="hidden mt-6 md:hidden">
      <ul id="templatePartsCards" class="divide-y divide-gray-200 rounded-lg border border-gray-200 overflow-hidden">
         <!-- cards injected here -->
      </ul>
   </div>
   <!-- Filled: desktop table -->
   <div id="templateTableWrap" class="hidden mt-6  md:block">
      <div class="overflow-x-auto">
         <table class="min-w-full text-left">
            <thead id="templateTableHead" class="hidden bg-gray-50 border-b border-gray-200">
               <tr class="bg-gray-50 text-gray-700">
                  <th class="w-10 px-4 py-3"></th>
                  <th class="px-4 py-3 text-sm font-semibold">Part Name</th>
                  <th class="px-4 py-3 text-sm font-semibold">Part Number</th>
                  <!-- <th class="px-4 py-3 text-sm font-semibold">Category</th> -->
                  <th class="px-4 py-3 text-sm font-semibold">Unit Cost</th>
                  <th class="px-4 py-3 text-sm font-semibold text-right">Actions</th>
               </tr>
            </thead>
            <tbody id="templatePartsTbody" class="divide-y divide-gray-200">
               <!-- rows injected here -->
            </tbody>
         </table>
      </div>
   </div>
</div>
<!-- ===== Modal (simple JS open/close) ===== -->
<div id="partsModal" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10 hidden">
   <div class="modal-scrollable w-full mx-auto">
      <!-- Backdrop -->
      <div class="absolute inset-0 bg-black/50" onclick="closePartsModal()"></div>
      <!-- Dialog -->
      <div class="relative mx-auto w-[95%] max-w-3xl rounded-2xl bg-white shadow-xl">
         <!-- Header -->
         <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Add Parts to List</h2>
            <button  type="button" onclick="closePartsModal()" class="text-gray-400 hover:text-gray-600">
            ✕
            </button>
         </div>
         <!-- Content -->
         <div class="px-6 py-4 space-y-4 max-h-[60vh] overflow-y-auto">
            <!-- Search & Filter (static UI, not wired; you can hook later) -->
            <div class="flex flex-wrap items-center gap-3">
               <div class="relative flex-1 min-w-[220px]">
                  <input type="text" placeholder="Search parts..."
                     class="w-full rounded-md border border-gray-300 bg-white px-3 py-3 text-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500">
                  <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                     <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 19a8 8 0 1 1 0-16 8 8 0 0 1 0 16z"/>
                  </svg>
               </div>
            </div>
            <p id="selectedCountLabel" class="text-sm text-gray-500">0 parts selected</p>
            <!-- Parts list (example items) -->
            <div class="space-y-3">
               @foreach ($parts as $part)
               <!-- Part row -->
               <label class="flex items-center gap-3 rounded-xl border border-gray-200 p-4 cursor-pointer
                  {{ $part->assigned_other ? 'opacity-50 cursor-not-allowed bg-gray-100 border-gray-300' : 'hover:border-blue-300 hover:bg-blue-50/50' }}">
                  <input type="checkbox" class="part-checkbox h-5 w-5 accent-blue-600"
                     data-id="{{ $part->id }}"
                     data-name="{{ $part->part_name }}"
                     data-sku="{{ $part->primary_part_number }}"
                     data-brand="{{ $part->brand ?? '' }}"
                     data-category="{{ $part->category->name ?? '' }}"
                     data-price="{{ $part->primary_part_cost }}"
                     >
                  <div class="flex-1">
                     <div class="flex items-start">
                        <!--  FIRST LEFT DIV -->
                        <div class="mr-2">
                           <h3 class="text-sm font-medium text-gray-900">{{ $part->part_name }}</h3>
                           <p class="text-xs text-gray-500">{{ $part->primary_part_number }}</p>
                        </div>
                        <!--  SECOND LEFT DIV -->
                        <div class="mr-2">
                           @if($part->description)
                           <p class="text-xs text-gray-600 mt-1">{{ $part->description }}</p>
                           @endif
                        </div>
                        <!--  RIGHT SIDE DIV -->
                        <div class="ml-auto text-right">
                           <p class="text-sm font-medium text-green-600 mt-1">
                              ${{ $part->primary_part_cost }}
                           </p>
                           @if($part->assigned_other)
                           <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium bg-red-200 text-red-700">
                           Already in List
                           </span>
                           @endif
                        </div>
                     </div>
                  </div>
               </label>
               @endforeach
            </div>
         </div>
         <!-- Footer -->
         <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200">
            <button type="button" onclick="closePartsModal()" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm">
            Cancel
            </button>
            <button type="button" id="addSelectedBtn"
               onclick="addSelectedPartsToTemplate()"
               class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed"
               disabled>
            Add 0 Parts to List
            </button>
         </div>
      </div>
   </div>
</div>

@push('js')

  <script>
      document.addEventListener('DOMContentLoaded', function() {
          //  Backend-selected parts array
          const existingParts = @json($selectedPartIds ?? []);
          console.log(' Existing parts for template:', existingParts);

          const tbody = document.getElementById('templatePartsTbody');
          const cards = document.getElementById('templatePartsCards');

          //  Render existing parts from backend (Blade → JS)
          @if(!empty($list->parts))
          @foreach($list->parts as $part) {
              const preItem = {
                  id: '{{ $part->id }}',
                  name: @json($part->part_name),
                  sku: @json($part->primary_part_number),
                  brand: @json($part->brand ?? ''),
                  category: @json($part->category->name ?? ''),
                  price: '{{ $part->primary_part_cost }}'
              };

              tbody.insertAdjacentHTML('beforeend', renderTableRow(preItem));
              cards.insertAdjacentHTML('beforeend', renderMobileCard(preItem));
          }
          @endforeach
          @endif

          //  Hide empty state if parts exist
          syncTemplateViews();

          //  Pre-check modal checkboxes
          const modalChecks = document.querySelectorAll('.part-checkbox');
          modalChecks.forEach(cb => {
              if (existingParts.map(String).includes(cb.dataset.id.toString())) {
                  cb.checked = true;
              }
          });

          //  Update modal counter button
          updateSelectedCount();
      });
  </script>


<script>
document.addEventListener('DOMContentLoaded', function () {

    const checkboxes = document.querySelectorAll('.product-checkbox');
    const categoryInput = document.getElementById('category_name');
    const categoryIdInput = document.getElementById('category_id');
    const searchInput = document.querySelector('input[name="search"]');
    const productList = document.getElementById('productList');
    const categoryDropdown = document.getElementById('categories_selected');
    const equipmentSection = document.getElementById('equipmentSection');
    const emptyMessage = document.getElementById('emptyMessage');

    let selectedCategoryId = null;

    /* ================================
       SHOW / HIDE EQUIPMENT SECTION
    ================================== */
    function toggleEquipmentSection() {

            const selectMessage = document.getElementById('selectCategoryMessage');

            if (categoryDropdown.value) {
                equipmentSection.classList.remove('hidden');
                selectMessage.classList.add('hidden');
            } else {
                equipmentSection.classList.add('hidden');
                selectMessage.classList.remove('hidden');
            }
        }


    /* ================================
       LOAD EDIT MODE DATA
    ================================== */
    const selectedProducts = @json($list->selected_products ?? []);

    if (selectedProducts.length > 0) {

        checkboxes.forEach(cb => {
            if (selectedProducts.map(String).includes(cb.value.toString())) {

                cb.checked = true;
                selectedCategoryId = cb.dataset.categoryId;

                categoryInput.value = cb.dataset.categoryName;
                categoryIdInput.value = cb.dataset.categoryId;
            }
        });

        disableOtherCategories();
    }

    /* ================================
       CHECKBOX LOGIC (Same Category)
    ================================== */
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function () {

            const currentCategoryId = this.dataset.categoryId;
            const currentCategoryName = this.dataset.categoryName;

            // First selection
            if (this.checked && !selectedCategoryId) {
                selectedCategoryId = currentCategoryId;
                categoryInput.value = currentCategoryName;
                categoryIdInput.value = currentCategoryId;
                disableOtherCategories();
            }

            // If all unchecked
            if (![...checkboxes].some(cb => cb.checked)) {
                selectedCategoryId = null;
                categoryInput.value = 'Not Assigned';
                categoryIdInput.value = '';
                enableAllCategories();
            }

        });
    });

    function disableOtherCategories() {
        checkboxes.forEach(cb => {
            if (cb.dataset.categoryId !== selectedCategoryId) {
                cb.disabled = true;
                cb.parentElement.classList.add('opacity-50');
            }
        });
    }

    function enableAllCategories() {
        checkboxes.forEach(cb => {
            cb.disabled = false;
            cb.parentElement.classList.remove('opacity-50');
        });
    }

    /* ================================
       FILTER PRODUCTS
    ================================== */
    function filterProducts() {

        const searchValue = searchInput.value.toLowerCase();
        const selectedCategory = categoryDropdown.value;

        let hasAnyVisibleProduct = false;

        // No category selected
        if (!selectedCategory) {
            emptyMessage.textContent = "Please select a category.";
            emptyMessage.classList.remove('hidden');
            productList.style.display = 'none';
            return;
        }

        productList.style.display = 'block';

        productList.querySelectorAll('div.mb-3').forEach(categoryBlock => {

            const categoryId = categoryBlock.getAttribute('data-category-id');

            // Hide other categories
            if (categoryId !== selectedCategory) {
                categoryBlock.style.display = 'none';
                return;
            }

            let categoryHasVisibleProduct = false;

            categoryBlock.querySelectorAll('li').forEach(productItem => {

                const text = productItem.textContent.toLowerCase();
                const matches = text.includes(searchValue);

                productItem.style.display = matches ? 'flex' : 'none';

                if (matches) {
                    categoryHasVisibleProduct = true;
                    hasAnyVisibleProduct = true;
                }
            });

            categoryBlock.style.display = categoryHasVisibleProduct ? 'block' : 'none';
        });

        if (!hasAnyVisibleProduct) {
            emptyMessage.textContent = "This category has no equipment.";
            emptyMessage.classList.remove('hidden');
            productList.style.display = 'none';
        } else {
            emptyMessage.classList.add('hidden');
        }
    }

    /* ================================
       EVENTS
    ================================== */
    categoryDropdown.addEventListener('change', function () {
        toggleEquipmentSection();
        filterProducts();
    });

    searchInput.addEventListener('input', filterProducts);

    /* ================================
       INIT (Edit Mode Support)
    ================================== */
    toggleEquipmentSection();

    if (categoryDropdown.value) {
        filterProducts();
    }

});
</script>



<!-- JS -->
<script>
  // -------- Modal open/close --------
  function openPartsModal() {
    document.getElementById('partsModal').classList.remove('hidden');
    updateSelectedCount();
  }
  function closePartsModal() {
    document.getElementById('partsModal').classList.add('hidden');
  }

  // -------- Selected counter in modal --------
  function updateSelectedCount() {
    const checks = document.querySelectorAll('.part-checkbox:checked');
    const n = checks.length;
    const label = document.getElementById('selectedCountLabel');
    const btn = document.getElementById('addSelectedBtn');
    if (label) label.textContent = `${n} part${n !== 1 ? 's' : ''} selected`;
    if (btn) {
      btn.textContent = `Add ${n} Part${n !== 1 ? 's' : ''} to List`;
      btn.disabled = n === 0;
    }
  }

  // Watch modal checkboxes
      document.addEventListener('change', function(e) {
    if (e.target && e.target.classList.contains('part-checkbox')) {
      updateSelectedCount();
    }
  });

  // -------- Helpers for rendering table/mobile rows --------
  const fmt = v => `$${Number(v).toFixed(2)}`;

  // <td class="px-4 py-3">
        //   <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">${item.category}</span>
        // </td>

  // NOTE: drag handle added in first <td>, row made draggable by setupRowDragging()
  function renderTableRow(item) {
    return `
      <tr data-id="${item.id}">
        <td class="px-4 py-3">
          <span class="drag-handle inline-block text-gray-400 cursor-grab active:cursor-grabbing select-none" title="Drag to reorder">⋮⋮</span>
        </td>
        <td class="px-4 py-3 text-sm font-medium text-gray-900">${item.name}</td>
        <td class="px-4 py-3 text-sm text-gray-900">${item.sku}</td>

        <td class="px-4 py-3 text-sm font-medium text-green-600">${fmt(item.price)}</td>
        <td class="px-4 py-3 text-right">
          <button type="button" class="text-red-600 hover:text-red-700" onclick="removeTemplateRow('${item.id}')" title="Remove">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash2 h-4 w-4"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path><line x1="10" x2="10" y1="11" y2="17"></line><line x1="14" x2="14" y1="11" y2="17"></line></svg>
          </button>
        </td>
        <td class="hidden">
          <input type="hidden" name="template_all_part_ids[]" value="${item.id}">
        </td>
      </tr>`;
  }

  function renderMobileCard(item) {
    return `
      <li class="p-4" data-id="${item.id}">
        <div class="flex items-start justify-between gap-3">
          <div>
            <p class="font-medium text-gray-900">${item.name}</p>
            <p class="text-xs text-gray-500">${item.sku}</p>
            <div class="mt-2 flex items-center gap-2">
              <span class="rounded-full bg-blue-100 text-blue-700 text-xs px-2 py-0.5">${item.category}</span>
              <span class="rounded-full bg-green-100 text-green-700 text-xs px-2 py-0.5">${fmt(item.price)}</span>
            </div>
          </div>
          <button type="button" class="text-red-600 hover:text-red-700" onclick="removeTemplateRow('${item.id}')" title="Remove">✕</button>
        </div>
        <input type="hidden" name="template_part_ids[]" value="${item.id}">
      </li>`;
  }

  // -------- View sync (hide header when empty) --------
  function syncTemplateViews() {
    const tbody = document.getElementById('templatePartsTbody');
    const thead = document.getElementById('templateTableHead');
    const count = tbody ? tbody.querySelectorAll('tr').length : 0;
    const empty = document.getElementById('templateEmpty');
    const tableWrap = document.getElementById('templateTableWrap');
    const mobile = document.getElementById('templateMobile');
    const countEl = document.getElementById('templatePartsCount');

    if (countEl) countEl.textContent = count;

    if (count > 0) {
      empty?.classList.add('hidden');
      tableWrap?.classList.remove('hidden');
      mobile?.classList.remove('hidden');
      thead?.classList.remove('hidden'); // show header only when data
    } else {
      empty?.classList.remove('hidden');
      tableWrap?.classList.add('hidden');
      mobile?.classList.add('hidden');
      thead?.classList.add('hidden'); // hide header
    }
  }

  // -------- Add selected parts --------
  function addSelectedPartsToTemplate() {
    const checks = document.querySelectorAll('.part-checkbox:checked');
    if (!checks.length) return;

    const tbody = document.getElementById('templatePartsTbody');
    const cards = document.getElementById('templatePartsCards');
    if (!tbody || !cards) return;

    const existing = new Set(Array.from(tbody.querySelectorAll('tr')).map(tr => tr.getAttribute('data-id')));

    checks.forEach(cb => {
      const item = {
        id: cb.dataset.id,
        name: cb.dataset.name,
        sku: cb.dataset.sku,
        brand: cb.dataset.brand,
        category: cb.dataset.category,
        price: cb.dataset.price
      };
      if (!existing.has(item.id)) {
        tbody.insertAdjacentHTML('beforeend', renderTableRow(item));
        cards.insertAdjacentHTML('beforeend', renderMobileCard(item));
      }
    });

    closePartsModal();
    syncTemplateViews();
    setupRowDragging(); // <-- enable drag after adding rows
  }

  // -------- Remove rows --------

  function removeTemplateRow(id) {
          //  Remove from table
    const tr = document.querySelector(`#templatePartsTbody tr[data-id="${id}"]`);
    if (tr) tr.remove();

          //  Remove from mobile card list
    const card = document.querySelector(`#templatePartsCards li[data-id="${id}"]`);
    if (card) card.remove();

          //  Uncheck the checkbox in modal if exists
          const checkbox = document.querySelector(`.part-checkbox[data-id="${id}"]`);
          if (checkbox) {
              checkbox.checked = false;
          }

          //  Update UI states
    syncTemplateViews();
          updateSelectedCount(); // so modal button text updates properly
          setupRowDragging?.(); // rebind drag if that function exists
  }

  // -------- Drag & Drop (by handle) --------
  function setupRowDragging() {
    const tbody = document.getElementById('templatePartsTbody');
    if (!tbody) return;

    // Clean old listeners by cloning rows (optional but keeps things tidy)
    Array.from(tbody.querySelectorAll('tr')).forEach(tr => {
      tr.setAttribute('draggable', 'true');
    });

    let draggedRow = null;
    let dragStartOnHandle = false;

    // Delegate events on tbody for performance
    tbody.addEventListener('dragstart', (e) => {
      const tr = e.target.closest('tr');
      if (!tr) return;

      // allow dragging only if started on the handle
      dragStartOnHandle = !!e.target.closest('.drag-handle');
      if (!dragStartOnHandle) {
        e.preventDefault();
        return;
      }

      draggedRow = tr;
      tr.classList.add('opacity-50');
      e.dataTransfer.effectAllowed = 'move';
      e.dataTransfer.setData('text/plain', tr.dataset.id || ''); // Firefox needs data
    });

    tbody.addEventListener('dragend', () => {
      if (draggedRow) draggedRow.classList.remove('opacity-50');
      draggedRow = null;
      dragStartOnHandle = false;
    });

    tbody.addEventListener('dragover', (e) => {
      if (!draggedRow) return;
      e.preventDefault(); // allow drop
      const target = e.target.closest('tr');
      if (!target || target === draggedRow) return;

      const rect = target.getBoundingClientRect();
      const isAfter = (e.clientY - rect.top) / rect.height > 0.5;

      if (isAfter) {
        tbody.insertBefore(draggedRow, target.nextSibling);
      } else {
        tbody.insertBefore(draggedRow, target);
      }
    });
  }

  // Init
  document.addEventListener('DOMContentLoaded', () => {
    syncTemplateViews();
    setupRowDragging();
  });
</script>


@endpush
