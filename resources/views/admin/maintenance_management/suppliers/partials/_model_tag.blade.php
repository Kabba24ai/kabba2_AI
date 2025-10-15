<!-- Tag Modal -->
<div id="TagModalWrapper" class="hidden fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
    <div class="modal-scrollable w-full mx-auto">
        <div
            class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-4xl space-y-5 border border-gray-200 overflow-hidden flex flex-col max-h-full"
            onclick="event.stopPropagation()">
            <!--  Proper Header Section -->

            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gray-50 mb-0">
                <!-- Left side (icon + text) -->
                <div class="flex items-start gap-3">
                    <!-- Icon -->
                    <div class="bg-gradient-to-r from-purple-600 to-blue-600 text-white p-2 rounded-lg mr-3 mt-1">


                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2Z"></path>
                            <path d="M7 7h.01"></path>
                        </svg>
                    </div>

                    <!-- Title + Subtitle stacked -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Tags Management</h3>
                        <p class="text-sm text-gray-600">Manage your supplier Tags and categories</p>
                    </div>
                </div>

                <!-- Close button -->
                <button onclick="closeModal('TagModalWrapper')" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
            </div>

            <!--  End Header -->

            <!-- Body -->
            <div class="px-6 py-5 space-y-6 overflow-y-auto">
                <!-- Add New Category -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Add New Tag</label>
                    <div class="flex gap-3">
                        <input type="text" id="newTagInput"
                            class="flex-1 px-3 py-2 text-sm border rounded-md focus:ring-green-500 focus:border-green-500"
                            placeholder="Enter Tag name">
                        <button id="addTagBtn"
                            class="px-3 py-2 bg-green-600 text-white rounded-lg disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors flex items-center text-sm">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Add Tag
                        </button>
                    </div>
                </div>


                <!-- Search Tag -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search Tags</label>
                    <div class="relative">

                        <input type="text" id="searchTagInput"
                            class="w-full px-3 py-2 border rounded-md focus:ring-green-500 focus:border-green-500 text-sm"
                            placeholder="Search existing tags...">

                    </div>
                </div>

                <!-- Tag List -->
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">All Tags (<span id="totalTags">0</span>)</h4>

                    <ul id="tagList" class="space-y-2">

                    </ul>

                </div>
            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-2 px-6 py-3 border-t bg-gray-50">
                <button type="button" onclick="closeModal('TagModalWrapper')"
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
        window.tags = [];
        const tagSelect = document.getElementById('supplierTags');
        let tagChoices = null;

        if (!tagSelect) return;

        // Initialize Choices once
        tagChoices = new Choices(tagSelect, {
            removeItemButton: true,
            duplicateItemsAllowed: false,
            shouldSort: false,
            placeholderValue: 'Select or type tags',
            addItems: true,
            addItemText: value => `Press Enter to add #${value}`,
            itemSelectText: '',
        });

        function updateTagSelect() {
            if (!tagChoices) return;

            // Clear existing choices
            tagChoices.clearStore();

            // Add updated tags dynamically
            const choicesArray = window.tags.map(tag => ({
                value: tag.id, // always use unique ID
                label: `#${tag.name}`,
                selected: false
            }));

            tagChoices.setChoices(choicesArray, 'value', 'label', false);
        }


        function fetchTags() {
            fetch(`{{ route('admin.maintenance-management.suppliers.tag.fetch') }}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.tags = data.tags.map(tag => ({
                            name: tag.name,
                            usedBy: 0,
                            id: tag.id
                        }));
                        renderTags();
                        updateTagSelect();
                    }
                })
                .catch((e) => {
                    console.error("❌ Error in fetchTags:", e);
                    notyf.error("Error in fetchTags!");
                });
        }


        function renderTags(filter = '') {
            const tagList = document.getElementById('tagList');
            tagList.innerHTML = '';

            //  Build a filtered list
            const filteredTags = tags.filter(tag => tag.name.toLowerCase().includes(filter.toLowerCase()));

            filteredTags.forEach((tag) => {
                const li = document.createElement('li');
                li.className = 'flex items-center justify-between px-4 py-2 rounded-md border border-gray-200 bg-white text-blue-700';

                const leftDiv = document.createElement('div');
                leftDiv.className = 'flex gap-2 items-center w-full';

                const nameSpan = document.createElement('span');
                nameSpan.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium mr-3 bg-blue-100 text-blue-800 border border-blue-200';
                nameSpan.innerHTML = `#${tag.name}`;


                const infoSpan = document.createElement('span');
                infoSpan.className = 'text-xs text-blue-600';
                infoSpan.innerHTML = `<span class="text-sm text-gray-500">Used by ${tag.usedBy} Suppliers</span>`;


                leftDiv.appendChild(nameSpan);
                leftDiv.appendChild(infoSpan);
                li.appendChild(leftDiv);
                // li.appendChild(nameSpan);

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
                deleteBtn.className = 'text-red-800';
                deleteBtn.title = 'Delete';
                deleteBtn.innerHTML = `
                    <x-heroicon-o-trash class="w-5 h-5" />`;


                btnDiv.appendChild(deleteBtn);

                li.appendChild(btnDiv);
                tagList.appendChild(li);

                // --- Edit functionality ---
                editBtn.addEventListener('click', () => {
                    li.classList.remove('bg-purple-100', 'text-purple-700', 'border-purple-200');
                    li.classList.add('bg-white', 'text-gray-900', 'border-gray-300');

                    const inputName = document.createElement('input');
                    inputName.type = 'text';
                    inputName.value = tag.name;
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


                    // Save handler
                    editBtn.addEventListener('click', () => {
                        const newName = inputName.value.trim();
                        if (!newName) {
                            notyf.error("Tag cannot be empty!");
                            return;
                        }

                        let updateUrl = `{{ route('admin.maintenance-management.suppliers.tag.update', ['tag' => ':id']) }}`;
                        updateUrl = updateUrl.replace(':id', tag.id);

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
                                    fetchTags();
                                } else {
                                    notyf.error("Update failed!");
                                }
                            })
                            .catch(() => notyf.error("Error updating Tag!"));
                    }, {
                        once: true
                    });
                });

                // --- Delete functionality ---
                deleteBtn.addEventListener('click', () => {
                    window.showConfirm(
                        `Are you sure you want to delete "${tag.name}"?`,
                        'Delete tag'
                    ).then((result) => {
                        if (result.isConfirmed) {
                            //  Construct delete URL dynamically
                            let deleteUrl = `{{ route('admin.maintenance-management.suppliers.tag.delete', ['tag' => ':id']) }}`;
                            deleteUrl = deleteUrl.replace(':id', tag.id);

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
                                        fetchTags(); // Refresh list
                                    } else {
                                        notyf.error("Failed to delete Tags!");
                                    }
                                })
                                .catch(() => notyf.error("Error deleting Tags!"));
                        }
                    });
                });

            });

            // Update total tags count
            document.getElementById('totalTags').textContent = tags.length;

            updateTagSelect();
        }

        // Add new tag
        document.getElementById('addTagBtn').addEventListener('click', () => {
            const name = document.getElementById('newTagInput').value.trim();
            if (!name) {
                notyf.error("Tag cannot be empty!");
                return;
            }
            fetch(`{{ route('admin.maintenance-management.suppliers.tag.store') }}`, {
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
                    document.getElementById('newTagInput').value = '';
                    fetchTags();
                })
                .catch(() => notyf.error("Failed to add Tag"));


        });

        // Search tags
        document.getElementById('searchTagInput').addEventListener('input', (e) => {
            renderTags(e.target.value);
        });

        // Initial render
        fetchTags();
    });
</script>

@endpush