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
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-white">
                            <path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"></path>
                        </svg>
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
                    <div class="flex flex-col sm:flex-row gap-3">
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

                    <ul id="categoryList" class="space-y-2 w-full overflow-y-auto">

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
        //  Define empty array first so renderCategories() doesn’t fail
        window.categories = [];

        const categoryList = document.getElementById('categoryList');
        const newCategoryInput = document.getElementById('newCategoryInput');
        const addCategoryBtn = document.getElementById('addCategoryBtn');
        const searchCategoryInput = document.getElementById('searchCategoryInput');

        const supplierSelect = document.getElementById('supplierCategory');

        function fetchCategories() {
            fetch(`{{ route('admin.maintenance-management.suppliers.category.fetch') }}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.categories = data.categories.map(cat => ({
                            name: cat.name,
                            usedBy: cat.usedBy,
                            id: cat.id
                        }));
                        renderCategories();
                        renderSelect();

                    }
                })
                .catch(() => notyf.error("Failed to load categories"));
        }


        function renderSelect() {
            if (!supplierSelect) return;

            supplierSelect.innerHTML = '<option value="">All Categories</option>';
            window.categories.forEach(cat => {
                const opt = document.createElement('option');
                opt.value = cat.id;
                opt.textContent = cat.name;
                supplierSelect.appendChild(opt);
            });


            // this is rerender the suppier form
            const select = document.getElementById('supplierCategorysform');
            if (!select) return;

            select.innerHTML = '<option value="">Select Category</option>';
            window.categories.forEach(cat => {
                const opt = document.createElement('option');
                opt.value = cat.id;
                opt.textContent = cat.name;
                select.appendChild(opt);
            });
            // this is rerender the suppier form
        }


        function renderCategories(filter = '') {
            categoryList.innerHTML = '';

            //  Filter list separately
            const filteredCategories = categories.filter(cat =>
                cat.name.toLowerCase().includes(filter.toLowerCase())
            );

            filteredCategories.forEach(cat => {
                const li = document.createElement('li');
                li.className = 'w-full px-4 py-2 rounded-md border border-gray-200 bg-white text-blue-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3';

                const leftDiv = document.createElement('div');
                leftDiv.className = 'flex gap-2 items-center w-full';

                const nameSpan = document.createElement('span');
                nameSpan.className = 'inline-flex whitespace-nowrap items-center px-2.5 py-0.5 rounded-full text-sm font-medium mr-3 bg-blue-100 text-blue-800 border border-blue-200';
                nameSpan.textContent = `${cat.name} (Default)`;

                const infoSpan = document.createElement('span');
                infoSpan.className = 'text-xs text-blue-600';
                infoSpan.innerHTML = `<span class="text-sm  text-gray-500">Used by ${cat.usedBy} Suppliers</span>`;

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
                    leftDiv.innerHTML = '';
                    leftDiv.appendChild(inputName);


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

                        let updateUrl = `{{ route('admin.maintenance-management.suppliers.category.update', ['category' => ':id']) }}`;
                        updateUrl = updateUrl.replace(':id', cat.id);

                        fetch(updateUrl, {
                                method: 'PUT',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                },
                                body: JSON.stringify({
                                    name: newName
                                })
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    notyf.success(data.message);
                                    fetchCategories();
                                    fetchSuppliers();
                                } else {
                                    notyf.error("Update failed!");
                                }
                            })
                            .catch(() => notyf.error("Error updating category!"));

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
                            //  Construct delete URL dynamically
                            let deleteUrl = `{{ route('admin.maintenance-management.suppliers.category.delete', ['category' => ':id']) }}`;
                            deleteUrl = deleteUrl.replace(':id', cat.id);

                            fetch(deleteUrl, {
                                    method: 'DELETE',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                    }
                                })
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success) {
                                        notyf.success(data.message);
                                        fetchCategories(); // Refresh list
                                    } else {
                                        notyf.error("Failed to delete category!");
                                    }
                                })
                                .catch(() => notyf.error("Error deleting category!"));
                        }
                    });
                });



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

            fetch(`{{ route('admin.maintenance-management.suppliers.category.store') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        name
                    })
                })
                .then(res => res.json())
                .then(data => {
                    notyf.success(data.message);
                    newCategoryInput.value = '';
                    fetchCategories();
                })
                .catch(() => notyf.error("Failed to add category"));

        });

        // --- Search functionality ---
        searchCategoryInput.addEventListener('input', (e) => {
            renderCategories(e.target.value);
        });

        fetchCategories();

    });
</script>

@endpush