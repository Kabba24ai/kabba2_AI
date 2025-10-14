<!-- Tag Modal -->
<div id="TagModalWrapper" class="hidden fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10">
    <div class="modal-scrollable w-full mx-auto">
        <div
            class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-4xl space-y-5 border border-gray-200 overflow-hidden flex flex-col max-h-full"
            onclick="event.stopPropagation()">
            <!--  Proper Header Section -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gray-50">
                <!-- Left side (icon + text) -->
                <div class="flex items-start gap-3">
                    <!-- Icon -->
                    <div class="p-2 border rounded-md bg-purple-600" style=" color: wheat;">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M3 3h6l11 11a2 2 0 01-2.83 2.83L6 5V3z"></path>
                        </svg>
                    </div>

                    <!-- Title + Subtitle stacked -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Tags Management</h3>
                        <p class="text-sm text-gray-600">Manage your supplier Tags and categories </p>
                    </div>
                </div>

                <!-- Close button -->
                <button onclick="closeModal('TagModalWrapper')"
                    class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
            </div>

            <!--  End Header -->

            <!-- Body -->
            <div class="px-6 py-5 space-y-6 overflow-y-auto">
                <!-- Add New Category -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Add New Tag</label>
                    <div class="flex gap-3">
                        <input type="text" id="newTagInput"
                            class="w-full px-4 py-2 text-sm border rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                            placeholder="Enter Tag name">
                        <button id="addTagBtn"
                            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Add
                        </button>
                    </div>
                </div>

                <!-- Search Tag -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search Tags</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <input type="text" id="searchTagInput"
                            class="w-full pl-9 pr-4 py-2 border rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 text-sm"
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
        //  Global tags
        window.tags = [{
                name: "24-7 Service"
            },
            {
                name: "APi-integration"
            },
            {
                name: "automotive"
            },
            {
                name: "Bulk-orders"
            },
            {
                name: "Carban-neutral"
            },
            {
                name: "Cloude-service"
            },
        ];

        const tagSelect = document.getElementById('supplierTags');
        let tagChoices = null; // hold Choices.js instance

        //  Reusable function to rebuild dropdown
        function updateTagSelect() {
            if (!tagSelect) return;

            // Destroy old Choices instance (if any)
            if (tagChoices && tagChoices.destroy) tagChoices.destroy();

            // Clear existing options
            tagSelect.innerHTML = '';

            // Add new ones
            window.tags.forEach(tag => {
                const opt = document.createElement('option');
                opt.value = tag.name;
                opt.textContent = '#' + tag.name;
                tagSelect.appendChild(opt);
            });

            // Reinitialize Choices.js
            tagChoices = new Choices(tagSelect, {
                removeItemButton: true,
                duplicateItemsAllowed: false,
                shouldSort: false,
                placeholderValue: 'Select or type tags',
                addItems: true,
                addItemText: (value) => `Press Enter to add #${value}`,
                itemSelectText: '',
            });
        }


        function renderTags(filter = '') {
            const tagList = document.getElementById('tagList');
            tagList.innerHTML = '';

            //  Build a filtered list
            const filteredTags = tags.filter(tag => tag.name.toLowerCase().includes(filter.toLowerCase()));

            filteredTags.forEach((tag) => {
                const li = document.createElement('li');
                li.className = 'flex justify-between items-center px-4 py-2 border rounded bg-purple-100 text-purple-700';

                const nameSpan = document.createElement('span');
                nameSpan.className = 'font-medium';
                nameSpan.innerHTML = `#${tag.name}`;
                li.appendChild(nameSpan);

                const btnDiv = document.createElement('div');
                btnDiv.className = 'flex gap-2';

                //  Edit button
                const editBtn = document.createElement('button');
                editBtn.className = 'hover:text-purple-800';
                editBtn.title = 'Edit';
                editBtn.innerHTML = `
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 
                        2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 
                        1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 
                        1.13-1.897L16.863 4.487Zm0 0L19.5 7.125"/>
                </svg>
            `;

                //  Delete button
                const deleteBtn = document.createElement('button');
                deleteBtn.className = 'hover:text-red-800';
                deleteBtn.title = 'Delete';
                deleteBtn.innerHTML = `
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="m14.74 9-.346 9m-4.788 0L9.26 
                        9m9.968-3.21c.342.052.682.107 
                        1.022.166m-1.022-.165L18.16 
                        19.673a2.25 2.25 0 0 1-2.244 
                        2.077H8.084a2.25 2.25 0 0 
                        1-2.244-2.077L4.772 
                        5.79m14.456 0a48.108 48.108 
                        0 0 0-3.478-.397m-12 
                        .562c.34-.059.68-.114 
                        1.022-.165m0 0a48.11 48.11 
                        0 0 1 3.478-.397m7.5 
                        0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 
                        51.964 0 0 0-3.32 0c-1.18.037-2.09 
                        1.022-2.09 2.201v.916m7.5 
                        0a48.667 48.667 0 0 0-7.5 0" />
                </svg>
            `;

                btnDiv.appendChild(editBtn);
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
                    li.replaceChild(inputName, nameSpan);

                    // Change icon to Save (✔)
                    editBtn.innerHTML = `
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                `;
                    editBtn.title = 'Save';

                    // Save handler
                    editBtn.addEventListener('click', () => {
                        const newName = inputName.value.trim();
                        if (!newName) {
                            notyf.error("Tag cannot be empty!");
                            return;
                        }

                        //  Find the actual index in the original array
                        const realIndex = tags.findIndex(t => t.name === tag.name);
                        if (realIndex !== -1) {
                            tags[realIndex].name = newName;
                        }

                        renderTags(document.getElementById('searchTagInput').value);
                        notyf.success("Tag updated successfully!");
                    }, {
                        once: true
                    });
                });

                // --- Delete functionality ---
                deleteBtn.addEventListener('click', () => {
                    if (confirm(`Delete "${tag.name}"?`)) {
                        const realIndex = tags.findIndex(t => t.name === tag.name);
                        if (realIndex !== -1) {
                            tags.splice(realIndex, 1);
                        }
                        renderTags(document.getElementById('searchTagInput').value);
                        notyf.success("Tag deleted successfully!");
                    }
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
            tags.push({
                name
            });
            document.getElementById('newTagInput').value = '';
            renderTags(document.getElementById('searchTagInput').value);
            notyf.success("Tag added successfully!");
        });

        // Search tags
        document.getElementById('searchTagInput').addEventListener('input', (e) => {
            renderTags(e.target.value);
        });

        // Initial render
        renderTags();
    });
</script>





@endpush