<!-- Category Modal -->
<div id="CategoryModalWrapper" class="hidden fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
    <div class="modal-scrollable w-full mx-auto">
        <div
            class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-4xl space-y-5 border border-gray-200 overflow-hidden flex flex-col max-h-full"
            onclick="event.stopPropagation()">
            <!--  
            Proper Header Section -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gray-50 mb-0">
                <!-- Left side (icon + text) -->
                <div class="flex items-start gap-3">
                    <!-- Icon -->
                    <div class="bg-gradient-to-r from-green-600 to-teal-600 p-2 rounded-lg mr-3 mt-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-white"><path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"></path></svg>
                    </div>

                    <!-- Title + Subtitle stacked -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Categories Management</h3>
                        <p class="text-sm text-gray-600">Manage your supplier categories and classifications</p>
                    </div>
                </div>

                <!-- Close button -->
                <button onclick="closeModal('CategoryModalWrapper')" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
            </div>

            <!--  End Header -->

            <!-- Body -->
            <div class="px-6 py-5 space-y-6 overflow-y-auto">
                <!-- Add New Category -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Add New Category</label>
                    <div class="flex gap-3">
                        <input type="text" id="newCategoryInput"
                            class="flex-1 px-3 py-2 text-sm border rounded-md focus:ring-green-500 focus:border-green-500"
                            placeholder="Enter category name">
                        <button id="addCategoryBtn"
                            class="px-3 py-2 bg-green-600 text-white rounded-lg disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors flex items-center text-sm">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Add Category
                        </button>
                    </div>
                </div>

                <!-- Search Category -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search Category</label>
                    <div class="relative">
                        <input type="text" id="searchCategoryInput"
                            class="w-full px-3 py-2 border rounded-md focus:ring-green-500 focus:border-green-500 text-sm"
                            placeholder="Search existing categories...">
                    </div>
                </div>

                <!-- Category List -->
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">All Category (<span id="totalcatagarys">0</span>)</h4>

                    <ul id="categoryList" class="space-y-2">

                    </ul>

                </div>
            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-2 px-6 py-3 border-t bg-gray-50">
                <button type="button" onclick="closeModal('CategoryModalWrapper')"
                    class="px-4 py-2 text-sm rounded border border-gray-300 bg-white hover:bg-gray-100">
                    Close
                </button>
            </div>


        </div>
    </div>
</div>


@push('js')

<script>
    document.addEventListener('DOMContentLoaded', () => {

        const categoryList = document.getElementById('categoryList');
        const newCategoryInput = document.getElementById('newCategoryInput');
        const addCategoryBtn = document.getElementById('addCategoryBtn');
        const searchCategoryInput = document.getElementById('searchCategoryInput');

        window.categories = [{
                name: "Equipment Dealer",
                usedBy: 0
            },
            {
                name: "Equipment Mfg.",
                usedBy: 3
            },
            {
                name: "Supplies - General",
                usedBy: 9
            },
            {
                name: "Software / IT",
                usedBy: 7
            },
            {
                name: "Parts",
                usedBy: 5
            },
            {
                name: "Financing",
                usedBy: 2
            },
        ];


        function renderCategories(filter = '') {
            categoryList.innerHTML = '';

            //  Filter list separately
            const filteredCategories = categories.filter(cat =>
                cat.name.toLowerCase().includes(filter.toLowerCase())
            );

            filteredCategories.forEach(cat => {
                const li = document.createElement('li');
                li.className = 'flex items-center justify-between px-4 py-2 rounded-md border border-gray-200 bg-white text-blue-700';

                const leftDiv = document.createElement('div');
                leftDiv.className = 'flex gap-2 items-center w-full';

                const nameSpan = document.createElement('span');
                nameSpan.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium mr-3 bg-blue-100 text-blue-800 border border-blue-200';
                nameSpan.textContent = `${cat.name} (Default)` ;

                const infoSpan = document.createElement('span');
                infoSpan.className = 'text-xs text-blue-600';
                infoSpan.innerHTML = `<span class="text-sm text-gray-500">Used by ${cat.usedBy} Suppliers</span>`;

                leftDiv.appendChild(nameSpan);
                leftDiv.appendChild(infoSpan);
                li.appendChild(leftDiv);

                const btnDiv = document.createElement('div');
                btnDiv.className = 'flex items-center gap-3';

                //  Edit button
                const editBtn = document.createElement('button');
                editBtn.className = 'hover:text-blue-800';
                editBtn.title = 'Edit';
                editBtn.innerHTML = `
                    <x-heroicon-o-pencil class="w-5 h-5" />`;
                btnDiv.appendChild(editBtn);

                //  Delete button
                const deleteBtn = document.createElement('button');
                deleteBtn.className = 'text-red-600';
                deleteBtn.title = 'Delete';
                deleteBtn.innerHTML = `
                    <x-heroicon-o-trash class="w-5 h-5" />`;
                btnDiv.appendChild(deleteBtn);

                li.appendChild(btnDiv);
                categoryList.appendChild(li);

                // --- Edit functionality ---
                editBtn.addEventListener('click', () => {
                    li.classList.remove('bg-blue-100', 'text-blue-700', 'border-blue-200');
                    li.classList.add('bg-white', 'text-gray-900', 'border-gray-300');

                    const inputName = document.createElement('input');
                    inputName.type = 'text';
                    inputName.value = cat.name;
                    inputName.className = 'border px-2 py-1 rounded w-full text-sm';
                    leftDiv.replaceChild(inputName, nameSpan);
                    leftDiv.removeChild(infoSpan);

                    // Change icon to Save
                    editBtn.innerHTML = `
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M4.5 12.75l6 6 9-13.5"/>
                    </svg>`;
                    editBtn.title = 'Save';

                    editBtn.addEventListener('click', () => {
                        const newName = inputName.value.trim();
                        if (!newName) {
                            notyf.error("Category name cannot be empty!");
                            return;
                        }

                        //  Find the actual index in the original array
                        const realIndex = categories.findIndex(c => c.name === cat.name);
                        if (realIndex !== -1) {
                            categories[realIndex].name = newName;
                        }

                        renderCategories(searchCategoryInput.value);
                        notyf.success("Category updated successfully!");
                    }, {
                        once: true
                    });
                });

                // --- Delete functionality ---
                deleteBtn.addEventListener('click', () => {
                    window.showConfirm(
                        `Are you sure you want to delete "${cat.name}"?`,
                        'Delete Category'
                    ).then((result) => {
                        if (result.isConfirmed) {
                            //  Find the actual index in the original array
                            const realIndex = categories.findIndex(c => c.name === cat.name);
                            if (realIndex !== -1) {
                                categories.splice(realIndex, 1);
                            }

                            renderCategories(searchCategoryInput.value);
                            notyf.success("Category deleted successfully!");
                        }
                    });
                });

                // this is rerender the suppier form 
                const select = document.getElementById('supplierCategory');
                if (!select) return;

                select.innerHTML = '<option value="">Select Category</option>';
                window.categories.forEach(cat => {
                    const opt = document.createElement('option');
                    opt.value = cat.name;
                    opt.textContent = cat.name;
                    select.appendChild(opt);
                });
                // this is rerender the suppier form 


            });

            // Update total count
            document.getElementById('totalcatagarys').textContent = categories.length;
        }

        // --- Add new category ---
        addCategoryBtn.addEventListener('click', () => {
            const name = newCategoryInput.value.trim();
            if (!name) {
                notyf.error("Category name cannot be empty!");
                return;
            }
            categories.push({
                name,
                usedBy: 0
            });
            newCategoryInput.value = '';
            renderCategories(searchCategoryInput.value);
            notyf.success("Category added successfully!");
        });

        // --- Search functionality ---
        searchCategoryInput.addEventListener('input', (e) => {
            renderCategories(e.target.value);
        });

        // Initial render
        renderCategories();
    });
</script>

@endpush