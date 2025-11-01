@extends('admin.layouts.app')

@section('title', 'Create Customer')

@push('css')
@endpush

@section('content')

<!-- <div class="mb-6 flex items-center justify-between">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">Add New Customer</h3>
        <a href="{{ route('admin.crm.customers.index') }}" class="inline-flex items-center px-4 py-2 rounded-md bg-red-600 text-white text-sm font-medium hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 transition">
            <svg class="w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"></path>
            </svg> Back
        </a>
    </div> -->

@include('flash::message')
@include('admin.partials.formErrors')

<!-- Customer Form -->
{{ html()->form()->attributes([
                            'autocomplete' => 'off',
                            'data-parsley-validate' => true,
                                'id' => 'customerForm',

                            'class' => 'space-y-8',
                        ])->acceptsFiles()->open() }}


<div class="bg-gray-50 px-4 py-4 border-b border-gray-200">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <!-- Left Section -->
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
            <!-- Back to Customers -->
            <a href="{{ route('admin.crm.customers.index') }}" class="flex items-center text-gray-600 hover:text-gray-800 transition">
                <x-heroicon-o-arrow-left class="w-5 h-5 mr-1" />
                <span class="text-sm font-medium">Back to Customers</span>
            </a>

            <!-- Divider -->
            <div class="hidden sm:block h-6 border-l border-gray-300"></div>

            <!-- Customer Info -->
            <div>
                <h3 class="text-xl font-semibold text-gray-800">Add New Customer</h3>
            </div>
        </div>

        <!-- Right Section: Buttons -->
        <div class="flex flex-wrap gap-2">
            <div class="flex-1">
                <label for="category" class="block text-sm font-medium text-gray-700 mt-2">Status</label>
            </div>
            <!-- Field 2: Status -->
            <div class="w-40 min-w-[150px]">
                <select id="status" name="status" required
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                    <option value="Archived">Archived</option>
                </select>
            </div>
            <button type="submit" name="action" value="save" class="inline-flex items-center px-6 py-2 rounded-md text-white bg-teal-600 hover:bg-teal-700 text-sm font-semibold shadow transition"> Save
                <x-heroicon-o-check class="w-4 h-4 ml-2" />
            </button>

            <!-- Save & New Button -->
            <button type="submit" name="action" value="save_new" class="inline-flex items-center px-6 py-2 rounded-md text-white bg-green-600 hover:bg-green-700 text-sm font-semibold shadow transition"> Save & New
                <x-heroicon-o-plus class="w-4 h-4 ml-2" />
            </button>

            <!-- Save & Exit Button -->
            <button type="submit" name="action" value="save_exit" class="inline-flex items-center px-6 py-2 rounded-md text-white bg-blue-600 hover:bg-blue-700 text-sm font-semibold shadow transition"> Save & Exit
                <x-heroicon-o-arrow-right-on-rectangle class="w-4 h-4 ml-2" />
            </button>
        </div>
    </div>
</div>



@include('admin.crm.customers.partials._form')


{{ html()->form()->close() }}


<!-- tag module   -->
<div id="TagModal" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10 hidden">
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-sm flex flex-col max-h-full overflow-hidden border border-gray-200">

            <div class="flex items-center justify-between p-4 border-b border-gray-200">
                <div class="flex items-center space-x-3">
                    <h2 class="text-lg font-semibold text-gray-900">Add Tag</h2>
                </div>
                <button type="button" onclick="closeTagModal()" class="text-gray-400 hover:text-gray-700 text-xl">&times;</button>
            </div>
            <!-- Scrollable Content -->
            <div class="px-6 overflow-y-auto max-h-[70vh] mt-5 mb-5">
                <div class="mb-4">
                    <label for="newTagInput" class="block text-sm font-medium text-gray-700 mb-1 required"> New Tag</label>

                    <input class="w-full rounded-md border focus:outline-none px-3 py-2 text-sm shadow-sm border-gray-300 " type="text" name="newTagInput" id="newTagInput">
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-4 pb-4 px-4 border-t border-gray-200">
                <button type="button" onclick="closeTagModal()" class="px-4 py-2 text-sm rounded border border-gray-300 bg-white">Close</button>
                <button type="button" id="addTagBtn" class="flex items-center justify-center gap-2 px-4 py-2 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700 transition-all"> <svg id="addTagSpinner" xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4 hidden animate-spin"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <circle class="opacity-25" cx="12" cy="12" r="10"
                            stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span id="addTagText">Add</span> </button>
            </div>
        </div>
    </div>
</div>




<!-- Notes Modal -->
<div id="notesModal" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10 hidden">
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-sm flex flex-col max-h-full overflow-hidden border border-gray-200">

            <div class="flex items-center justify-between p-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900" id="noteModalTitle">Add Note</h2>
                <button type="button" onclick="closeNotesModal()" class="text-gray-400 hover:text-gray-700 text-xl">&times;</button>
            </div>

            <div class="px-6 overflow-y-auto max-h-[70vh] mt-5 mb-5">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1 required">User</label>

                    {!! html()->select(
                    'user_id',
                    $employees->pluck('full_name', 'id')->toArray()
                    )->id('user_id')->class([
                    'w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-500 bg-white text-gray-700',
                    ]) !!}

                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1 required">Note</label>
                    <textarea id="note_text" rows="5"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-500"
                        placeholder="Enter note..."></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-4 pb-4 px-4 border-t border-gray-200">
                <button type="button" onclick="closeNotesModal()" class="px-4 py-2 text-sm rounded border border-gray-300 bg-white">Close</button>
                <button type="button" id="saveNoteBtn" class="px-4 py-2 text-sm rounded border border-grey-300 bg-blue-600 text-white">Save</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('js')

<script>
    function openNotesModal() {
        document.getElementById('notesModal').classList.remove('hidden');

    }

    function closeNotesModal() {
        document.getElementById('notesModal').classList.add('hidden');
    }
</script>

<script>
    function openTagModal() {
        document.getElementById('TagModal').classList.remove('hidden');

    }

    function closeTagModal() {
        document.getElementById('TagModal').classList.add('hidden');
    }
</script>

<!-- this is for appear tags her form choice js  -->

<script>
    document.addEventListener('DOMContentLoaded', () => {

        const addTagBtn = document.getElementById('addTagBtn');
        const addTagText = document.getElementById('addTagText');
        const addTagSpinner = document.getElementById('addTagSpinner');
        const newTagInput = document.getElementById('newTagInput');

        window.tags = [];
        const tagSelect = document.getElementById('ContactTags');

        if (!tagSelect) return;

        // Initialize Choices once
        if (tagSelect && !window.tagChoices) {
            window.tagChoices = new Choices(tagSelect, {
                removeItemButton: true,
                duplicateItemsAllowed: false,
                shouldSort: false,
                placeholderValue: 'Select or type tags',
                addItems: true,
                addItemText: value => `Press Enter to add #${value}`,
                itemSelectText: '',
            });
        }

        // Update the full list of tags (Add form)
        function updateTagSelect() {
            if (!window.tagChoices) return;

            // Build choices array
            const choicesArray = window.tags.map(tag => ({
                value: String(tag.id),
                label: `#${tag.name}`,
                selected: false
            }));

            // Update choices without clearing the instance
            window.tagChoices.setChoices(choicesArray, 'value', 'label', true);
        }


        function fetchTags() {
            fetch(`{{ route('admin.crm.tags.fetch') }}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        tags = data.tags.map(tag => ({
                            name: tag.name,
                            id: tag.id
                        }));

                        updateTagSelect();
                    }
                })
                .catch((e) => {
                    console.error("❌ Error in fetchTags:", e);
                    notyf.error("Error in fetchTags!");
                });
        }


        // Add new tag
        addTagBtn.addEventListener('click', () => {
            const name = newTagInput.value.trim();
            if (!name) {
                notyf.error("Tag cannot be empty!");
                return;
            }

            //  Start loading animation
            addTagBtn.disabled = true;
            addTagSpinner.classList.remove('hidden');
            addTagText.textContent = 'Saving...';

            fetch(`{{ route('admin.crm.tags.store') }}`, {
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
                    if (data.success) {
                        notyf.success(data.message || 'Tag added!');
                        newTagInput.value = '';
                        fetchTags(); // refresh tag list
                    } else {
                        notyf.error(data.message || 'Failed to add tag');
                    }
                })
                .catch(() => notyf.error("Failed to add tag"))
                .finally(() => {
                    //  Stop loading animation
                    addTagBtn.disabled = false;
                    addTagSpinner.classList.add('hidden');
                    addTagText.textContent = 'Add';
                });
        });
        // Initial render
        fetchTags();
    });
</script>


<!-- notes  -->

<script>
    document.addEventListener("DOMContentLoaded", function() {

        const notesModal = document.getElementById('notesModal');
        const noteText = document.getElementById('note_text');
        const userSelect = document.getElementById('user_id');
        const noteList = document.getElementById('noteList');
        const addNoteBtn = document.getElementById('addNoteBtn');
        const saveNoteBtn = document.getElementById('saveNoteBtn');
        const modalTitle = document.getElementById('noteModalTitle');
        const hiddenInput = document.getElementById('notes_json'); //  hidden input

        let notes = [];
        let editNoteId = null;

        //  Open modal
        addNoteBtn.addEventListener('click', () => {
            modalTitle.textContent = "Add Note";
            noteText.value = "";
            // Open modal
            addNoteBtn.addEventListener('click', () => {
                modalTitle.textContent = "Add Note";
                noteText.value = "";
                userSelect.selectedIndex = 0;
                editNoteId = null;
                notesModal.classList.remove('hidden');
            });
            editNoteId = null;
            notesModal.classList.remove('hidden');
        });

        //  Close modal
        window.closeNotesModal = function() {
            notesModal.classList.add('hidden');
        };

        //  Save note (add or edit)
        saveNoteBtn.addEventListener('click', () => {
            const text = noteText.value.trim();
            const userId = userSelect.value;
            const userName = userSelect.options[userSelect.selectedIndex]?.text || "";
            const timestamp = new Date().toLocaleString();

            if (!text || !userId) {

                notyf.error('Please fill all fields.');

                return;
            }

            if (editNoteId) {
                // Update existing
                const note = notes.find(n => n.id === editNoteId);
                if (note) {
                    note.text = text;
                    note.userId = userId;
                    note.userName = userName;
                    note.updatedAt = timestamp;

                    notyf.success("Note Edited Successfully .");

                }
            } else {
                // Add new note
                notes.unshift({
                    id: Date.now(),
                    text,
                    userId,
                    userName,
                    createdAt: timestamp,
                    updatedAt: null
                });

                notyf.success("New Note Added Successfully ");
            }

            renderNotes();
            closeNotesModal();
        });

        //  Render all notes
        function renderNotes() {
            if (notes.length === 0) {
                noteList.innerHTML = `<li class="text-gray-400 text-sm">No notes available.</li>`;
                return;
            }

            noteList.innerHTML = notes.map(note => `
            <li class="list-disc border-b border-gray-200 pb-3" data-id="${note.id}">
                <div class="flex justify-between items-start">
                    <div class="flex-1 pr-3">
                        <p class="text-sm text-gray-800 leading-relaxed text-justify font-semibold">
                            ${note.text}
                        </p>
                        <div class="mt-1 space-y-1 text-xs text-gray-500">
                            <div>
                                Created ${note.createdAt}
                                by <span class="font-semibold">${note.userName}</span>
                            </div>
                            ${note.updatedAt ? `<div>Updated ${note.updatedAt}</div>` : ""}
                        </div>
                    </div>
                    <div class="flex gap-2 mt-1">
                        <button type="button" class="text-blue-500 hover:text-blue-700 editNoteBtn" title="Edit Note" data-id="${note.id}">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
  <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"></path>
</svg>
                        </button>
                        <button type="button" class="text-red-500 hover:text-red-700 deleteNoteBtn" title="Delete Note" data-id="${note.id}">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
  <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"></path>
</svg>
                        </button>
                    </div>
                </div>
            </li>
        `).join('');

            //  Update hidden input for form
            hiddenInput.value = JSON.stringify(notes);
        }

        //  Edit & Delete buttons
        noteList.addEventListener('click', (e) => {
            const editBtn = e.target.closest('.editNoteBtn');
            const delBtn = e.target.closest('.deleteNoteBtn');

            if (editBtn) {
                const id = parseInt(editBtn.dataset.id);
                const note = notes.find(n => n.id === id);
                if (!note) return;

                modalTitle.textContent = "Edit Note";
                noteText.value = note.text;
                userSelect.value = note.userId;
                editNoteId = id;
                notesModal.classList.remove('hidden');
            }

            if (delBtn) {
                const id = parseInt(delBtn.dataset.id);

                window.showConfirm(
                    `Delete this note? `,
                    'Delete note'
                ).then((result) => {
                    if (result.isConfirmed) {
                        notes = notes.filter(n => n.id !== id);

                        notyf.success("Deleted Successfully .");

                        renderNotes();
                    }
                });

            }
        });

        // Initial render
        renderNotes();
    });
</script>
@endpush