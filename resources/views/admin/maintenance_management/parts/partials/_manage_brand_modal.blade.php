<!-- Brand Modal -->
<div id="BrandModalWrapper" class="hidden fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
    <div class="modal-scrollable w-full mx-auto">
        <div
            class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-4xl space-y-5 border border-gray-200 overflow-hidden flex flex-col max-h-full"
            onclick="event.stopPropagation()">

            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gray-50">
                <div class="flex items-start gap-3">
                    <div class="bg-gradient-to-r from-purple-600 to-indigo-600 p-2 rounded-lg mr-3 mt-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round"
                             stroke-linejoin="round" class="w-6 h-6 text-white">
                            <path d="M6 3v12l6 6 6-6V3H6z"/>
                        </svg>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Brand Management</h3>
                        <p class="text-sm text-gray-600">Manage your parts brands</p>
                    </div>
                </div>

                <button onclick="closeModal('BrandModalWrapper')"
                        class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
            </div>

            <!-- Body -->
            <div class="px-6 py-5 space-y-6 overflow-y-auto">

                <!-- Add New Brand -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Add New Brand</label>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <input type="text" id="newBrandInput"
                               class="flex-1 px-3 py-3 text-sm border rounded-md focus:ring-purple-500 focus:border-purple-500"
                               placeholder="Enter brand name">

                        <button id="addBrandBtn"
                                class="px-3 py-3 bg-purple-600 text-md text-white rounded-lg disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors flex items-center text-sm">
                            <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Add Brand
                        </button>
                    </div>
                </div>

                <!-- Search Brand -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search Brand</label>
                    <input type="text" id="searchBrandInput"
                           class="w-full px-3 py-3 border rounded-md focus:ring-purple-500 focus:border-purple-500 text-sm"
                           placeholder="Search existing brands...">
                </div>

                <!-- Brand List -->
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">All Brands (<span id="totalBrands">0</span>)</h4>
                    <ul id="brandList" class="space-y-2 w-full overflow-y-auto"></ul>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-2 px-6 py-3 border-t bg-gray-50">
                <button onclick="closeModal('BrandModalWrapper')"
                        class="px-4 py-2 text-sm rounded border border-gray-300 bg-white hover:bg-gray-100">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>


<!-- Brand Add Modal -->
<div id="BrandAddModal" class="fixed inset-0 z-[99999] hidden flex items-center justify-center bg-black/50 px-4 py-10">
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-sm flex flex-col max-h-full overflow-hidden border border-gray-200">

            <!-- Header -->
            <div class="flex items-center justify-between p-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Add Brand</h2>

                <button type="button" onclick="closeBrandAddModal()" 
                        class="text-gray-400 hover:text-gray-700 text-xl">&times;</button>
            </div>

            <!-- Body -->
            <div class="px-6 overflow-y-auto max-h-[70vh] mt-5 mb-5">
                <div class="mb-4">
                    <label for="newBrandInputModal" class="block text-sm font-medium text-gray-700 mb-1 required">
                        New Brand
                    </label>

                    <input class="w-full rounded-md border px-3 py-3 text-sm shadow-sm border-gray-300  
                           focus:outline-none" 
                           type="text" id="newBrandInputModal">

                           
                    <p id="brandDuplicateWarning" class="text-red-600 text-sm mt-1 hidden">
                        This brand already exists!
                    </p>
                </div>

                <!-- Suggestions Dropdown -->
<div id="brandDropdown"
     class="mt-2 border border-gray-300 rounded-md bg-white shadow max-h-40 overflow-y-auto hidden">
</div>


            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-2 pt-4 pb-4 px-4 border-t border-gray-200">
                <button type="button" onclick="closeBrandAddModal()" 
                        class="px-6 py-3 text-md rounded border border-gray-300 bg-white">Close</button>

                <button type="button" id="addBrandBtnModal"
                        class="flex items-center justify-center gap-2 px-6 py-3 text-md rounded-md bg-purple-600 text-white hover:bg-purple-700 transition-all">

                    <svg id="addBrandSpinner" 
                         class="h-4 w-4 hidden animate-spin" 
                         xmlns="http://www.w3.org/2000/svg" fill="none" 
                         viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>

                    <span id="addBrandText">Add</span>
                </button>
            </div>

        </div>
    </div>
</div>


@push('js')
<script>
    window.selectedPrimaryBrand = "{{ old('brand', $part->primary_brand_id ?? '') }}";
    window.selectedAlt1Brand    = "{{ old('brand_alt_1', $part->alt_1_brand_id ?? '') }}";
    window.selectedAlt2Brand    = "{{ old('brand_alt_2', $part->alt_2_brand_id ?? '') }}";
</script>


<script>

document.addEventListener('DOMContentLoaded', () => {




    // Brand state
    window.brands = [];

    const brandList = document.getElementById('brandList');
    const newBrandInput = document.getElementById('newBrandInput');
    const addBrandBtn = document.getElementById('addBrandBtn');
    const searchBrandInput = document.getElementById('searchBrandInput');

    function fetchBrands() {
        fetch(`{{ route('admin.maintenance-management.parts.brand.fetch') }}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.brands = data.brands;
                    renderBrands();
                    renderBrandSelects();
                }
            })
             .catch(err => {
            console.error("Fetch Error:", err);   
            notyf.error("Failed to load brands");
        });
    }

  function renderBrandSelects() {
    const primarySelect = document.getElementById("brand");
    const alt1Select = document.getElementById("brand_alt_1");
    const alt2Select = document.getElementById("brand_alt_2");

    const selects = [primarySelect, alt1Select, alt2Select];

    selects.forEach(select => {
        if (!select) return;

        const selectedValue =
            select.id === "brand" ? window.selectedPrimaryBrand :
            select.id === "brand_alt_1" ? window.selectedAlt1Brand :
            window.selectedAlt2Brand;

        select.innerHTML = `<option value="">Select brand</option>`;

        window.brands.forEach(brand => {
            const opt = document.createElement("option");
            opt.value = brand.id;
            opt.textContent = brand.name;

            //  PRE-SELECT BRAND
            if (String(selectedValue) === String(brand.id)) {
                opt.selected = true;
            }

            select.appendChild(opt);
        });
    });
}



    function renderBrands(filter = '') {
        brandList.innerHTML = '';

        const filtered = window.brands.filter(brand =>
            brand.name.toLowerCase().includes(filter.toLowerCase())
        );

        filtered.forEach(brand => {
            const li = document.createElement('li');
            li.className =
                'w-full px-4 py-2 rounded-md border border-gray-200 bg-white text-purple-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3';

            // Left side
            const left = document.createElement('div');
            left.className = 'flex gap-2 items-center w-full';

            const label = document.createElement('span');
            label.className =
                'inline-flex whitespace-nowrap items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-purple-100 text-purple-800 border border-purple-200';
            label.textContent = brand.name;

            left.appendChild(label);
            li.appendChild(left);

            // Right side buttons
            const btns = document.createElement('div');
            btns.className = 'flex items-center gap-3';

            // Edit
            const editBtn = document.createElement('button');
            editBtn.className = 'hover:text-purple-800';
            editBtn.innerHTML = `<x-heroicon-o-pencil-square class="w-5 h-5" />`;
            btns.appendChild(editBtn);

            // Delete
            const deleteBtn = document.createElement('button');
            deleteBtn.className = 'text-red-600';
            deleteBtn.innerHTML = `<x-heroicon-o-trash class="w-5 h-5" />`;
            btns.appendChild(deleteBtn);

            li.appendChild(btns);
            brandList.appendChild(li);

editBtn.onclick = () => {

    const freshBrand = window.brands.find(b => b.id === brand.id);

    const input = document.createElement('input');
    input.type = "text";
    input.value = freshBrand.name;
    input.className = "border px-2 py-1 rounded w-full text-sm";

    left.innerHTML = "";
    left.appendChild(input);

    editBtn.innerHTML = `
        <svg class="w-5 h-5" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M5 13l4 4L19 7"/>
        </svg>
    `;

    // NEXT CLICK becomes SAVE
    editBtn.onclick = () => {
        const newName = input.value.trim();
        // console.log("Saving name:", newName);

        let updateUrl = `{{ route('admin.maintenance-management.parts.brand.update', ':id') }}`
            .replace(':id', freshBrand.id);

        fetch(updateUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ name: newName })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                 notyf.success(data.message);
                fetchBrands(); // will rebuild UI correctly
            }
        });
    };
};



            // Delete
            deleteBtn.addEventListener('click', () => {
                window.showConfirm(`Delete brand "${brand.name}"?`, "Delete Brand")
                .then(res => {
                    if (res.isConfirmed) {
                        let deleteUrl = `{{ route('admin.maintenance-management.parts.brand.delete', ':id') }}`.replace(':id', brand.id);

                        fetch(deleteUrl, {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        }).then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                notyf.success(data.message);
                                fetchBrands();
                            }
                        });
                    }
                });
            });

        });

document.getElementById('totalBrands').textContent = window.brands.length;
    }

    // ADD NEW BRAND
    addBrandBtn.addEventListener('click', () => {
        const name = newBrandInput.value.trim();
        if (!name) return notyf.error("Brand name cannot be empty");
 //  FRONT-END DUPLICATE CHECK
    if (window.brands.some(b => b.name.toLowerCase() === name.toLowerCase())) {
        return notyf.error("This brand already exists!");
    }
        fetch(`{{ route('admin.maintenance-management.parts.brand.store') }}`, {
            method: "POST",
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ name })
        }).then(res => res.json())
        .then(data => {
            if (data.success) {
                notyf.success(data.message);
                newBrandInput.value = "";
                fetchBrands();
            }
        });
    });

    // SEARCH
    searchBrandInput.addEventListener('input', e => {
        renderBrands(e.target.value);
    });

    fetchBrands();



document.getElementById("addBrandBtnModal").addEventListener("click", () => {

    const name = document.getElementById("newBrandInputModal").value.trim();
    if (!name) return notyf.error("Brand name cannot be empty");

    // Duplicate check
    if (window.brands.some(b => b.name.toLowerCase() === name.toLowerCase())) {
        document.getElementById("brandDuplicateWarning").classList.remove("hidden");
        return;
    }

    // Show spinner
    document.getElementById("addBrandSpinner").classList.remove("hidden");
    document.getElementById("addBrandText").textContent = "Adding...";

    fetch(`{{ route('admin.maintenance-management.parts.brand.store') }}`, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ name })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            notyf.success("Brand added successfully!");

            closeBrandAddModal();

            fetchBrands(); // refresh dropdowns
        }
    })
    .finally(() => {
        document.getElementById("addBrandSpinner").classList.add("hidden");
        document.getElementById("addBrandText").textContent = "Add";
    });
});




const brandInputModal = document.getElementById('newBrandInputModal');
const brandDropdown = document.getElementById('brandDropdown');
const duplicateWarning = document.getElementById('brandDuplicateWarning');

brandInputModal.addEventListener('input', () => {
    const search = brandInputModal.value.trim().toLowerCase();

    brandDropdown.innerHTML = "";
    duplicateWarning.classList.add("hidden");

    if (!search) {
        brandDropdown.classList.add("hidden");
        return;
    }

    // Filter brands
    const matches = window.brands.filter(b => 
        b.name.toLowerCase().includes(search)
    );

    // If exact match → duplicate warning
    const isDuplicate = window.brands.some(b => 
        b.name.toLowerCase() === search
    );

    if (isDuplicate) {
        duplicateWarning.classList.remove("hidden");
    }

    // If no matches, hide dropdown
    if (matches.length === 0) {
        brandDropdown.classList.add("hidden");
        return;
    }

    // Show dropdown
    brandDropdown.classList.remove("hidden");

    matches.forEach(b => {
        const item = document.createElement("div");
        item.className = "px-3 py-2 text-sm cursor-pointer hover:bg-gray-100";
        item.textContent = b.name;

        item.onclick = () => {
            brandInputModal.value = b.name;
            brandDropdown.classList.add("hidden");
        };

        brandDropdown.appendChild(item);
    });
});

// Close dropdown when clicking outside
document.addEventListener("click", (e) => {
    if (!brandInputModal.contains(e.target) && !brandDropdown.contains(e.target)) {
        brandDropdown.classList.add("hidden");
    }
});


});


</script>


@endpush