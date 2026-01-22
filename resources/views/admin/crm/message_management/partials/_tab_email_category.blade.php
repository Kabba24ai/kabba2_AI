<div id="btn-emailcat-modal"
     class="fixed inset-0 z-[99999] hidden items-center justify-center bg-black/50 px-4 py-10">
    
    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 
                    border border-gray-200 overflow-hidden flex flex-col max-h-full">

            <!-- HEADER -->
            <div class="flex justify-between items-center px-6 pt-4">
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-medium text-gray-900">Create New Category</h2>
                </div>

                <button type="button" class="text-gray-400 hover:text-gray-700 text-xl"
                        onclick="closeModal('btn-emailcat-modal')">×</button>
            </div>

             {{ html()->form()->id('email_cat')->attributes([
                            'autocomplete' => 'off',
                            'data-parsley-validate' => true,
                            'class' => 'space-y-8',
                        ])->open() }}

                        <input type="hidden" id="email_cat_edit_mode" value="0">
<input type="hidden" id="email_cat_unique_id" value="">


            <!-- BODY -->
            <div class="px-6 overflow-y-auto">
                
                  

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                        <!-- <input type="text" placeholder="Enter category name..." class="w-full px-3 py-3 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"> -->

                         {!! html()->text('email_cat_name', old('email_cat_name', ''))->class([
                    'w-full px-3 py-3 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500',
                    'border-red-500' => $errors->has('email_cat_name'),
                    'border-gray-300' => !$errors->has('email_cat_name'),
                    ])->attributes([
                    'placeholder' => 'Enter category name...',
                    'id' => 'email_cat_name',
                    'autocomplete' => 'off',
                    ])->required() !!}

                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Description 
                        </label>

                        <!-- <textarea rows="4" placeholder="Enter description..." class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm"></textarea> -->

                         {!! html()->textarea('email_cat_description', old('email_cat_description'))
                       ->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm')
                       ->rows(4)
                       ->placeholder('Enter description...') !!}

                    </div>

                


            </div>

            <!-- FOOTER -->
            <div class="px-6 py-4 flex items-center justify-between gap-3 border-t border-gray-200">

                <!-- SEND BUTTON -->
                <button type="submit" id="createEmailCatBtn" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium 
                            px-6 py-3 rounded-lg text-md transition">
                    Create
                </button>

                <!-- CANCEL -->
                <button type="button" onclick="closeModal('btn-emailcat-modal')"
                    class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium
                        px-6 py-3 rounded-lg text-md transition">
                    Cancel
                </button>

            </div>

            {{ html()->form()->close() }}
        </div>
    </div>
</div>


            <!-- Wrapper around all cards -->
            <div id="email-cat-cardsWrapper">


            </div>



@push('js')
<script>
document.addEventListener("DOMContentLoaded", () => {

    function refreshEmailCategoryCards() {
    fetch("{{ route('admin.crm.message-management.email-category.index') }}")
        .then(response => response.json())
        .then(result => {

            if (result.status !== "success") {
                console.error("Could not load category list.");
                return;
            }

            const categories = result.data;
            const wrapper = document.getElementById("email-cat-cardsWrapper");

            wrapper.innerHTML = ""; // clear old cards

             //  Show message when no categories exist
            if (categories.length === 0) {
                wrapper.innerHTML = `
                    <div class="text-center py-10 text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-lg font-medium">No Email categories found</p>
                        <p class="text-sm text-gray-400">Click "New Email Category" to create one.</p>
                    </div>
                `;
                return;
            }

            categories.forEach(cat => {

                wrapper.innerHTML += `
                    <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm question-card mb-4">
                        <div class="flex items-center justify-between gap-3 flex-nowrap">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-base font-semibold text-gray-900">${cat.name}</h3>
                                </div>
                                <p class="text-sm text-gray-600 mt-1">${cat.description ?? ''}</p>
                            </div>

                            <div class="flex items-center gap-4 mt-3 md:mt-0 text-gray-600 text-sm" data-count="2">

                                <button type="button" class="edit-question-btn text-green-600 hover:text-green-800" 
                                    data-id="${cat.unique_id}">
                                     <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125"></path>
                                </svg>
                                </button>

                                <button type="button" class="text-red-600 hover:text-red-800 delete-category-btn" 
                                    data-id="${cat.unique_id}">
                                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"></path>
                                    </svg>
                                </button>

                            </div>
                        </div>
                    </div>
                `;
            });
        })
        .catch(err => console.error("Category refresh failed:", err));
}

refreshEmailCategoryCards();

    // store 
    const form = document.getElementById("email_cat");
    const submitBtn = document.getElementById("createEmailCatBtn");

    submitBtn.addEventListener("click", function (e) {

        if (document.getElementById("email_cat_edit_mode").value === "1") {
            return; // NOT store mode → skip
        }

    

        if (!$(form).parsley().isValid()) return;
    e.preventDefault();
        submitBtn.disabled = true;
        submitBtn.textContent = "Processing...";

        let formData = new FormData(form);

        fetch("{{ route('admin.crm.message-management.email-category.store') }}", {
            method: "POST",
            headers: { "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content },
            body: formData
        })
        .then(r => r.json())
        .then(data => {

            if (data.status === "success") {
                notyf.success("Category created successfully!");
                closeModal('btn-emailcat-modal');
                form.reset();
                $(form).parsley().reset();

                refreshEmailCategoryCards();
            } 
        })
        .catch(() => notyf.error("Server error. Try again."))
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = "Create";
        });
    });

    // store 


    // When clicking edit button → load data + open modal
    document.addEventListener("click", function(e) {

        const btn = e.target.closest(".edit-question-btn");
        if (!btn) return;

        let id = btn.dataset.id;
let showUrl = "{{ route('admin.crm.message-management.email-category.show', ':id') }}"
                .replace(':id', id);

            fetch(showUrl)
            .then(r => r.json())
            .then(data => {

                if (data.status !== "success") return;

                // enable edit mode
                document.getElementById("email_cat_edit_mode").value = "1";
                document.getElementById("email_cat_unique_id").value = id;

                // fill form
                document.getElementById('email_cat_name').value = data.data.name;
                document.getElementById('email_cat_description').value = data.data.description ?? "";

                // update modal text
                document.querySelector("#btn-emailcat-modal h2").textContent = "Edit Category";
                document.getElementById("createEmailCatBtn").textContent = "Update";

                openModal("btn-emailcat-modal");
            });
    });


    submitBtn.addEventListener("click", function (e) {

        if (document.getElementById("email_cat_edit_mode").value === "0") {
            return; // NOT edit mode → skip
        }

        e.preventDefault();

        if (!$(form).parsley().isValid()) return;

        submitBtn.disabled = true;
        submitBtn.textContent = "Updating...";

        let id = document.getElementById("email_cat_unique_id").value;

        let formData = new FormData(form);
        formData.append("_method", "PUT");

        let updateUrl = "{{ route('admin.crm.message-management.email-category.update', ':id') }}"
                    .replace(':id', id);

        formData.append("_method", "PUT");

        fetch(updateUrl, {
            method: "POST",
            headers: { "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content },
            body: formData
        })
        .then(r => r.json())
        .then(data => {

            if (data.status === "success") {

                notyf.success("Category updated!");

                closeModal('btn-emailcat-modal');

                form.reset();
                $(form).parsley().reset();

                // reset modal back to create mode
                document.getElementById("email_cat_edit_mode").value = "0";
                document.getElementById("email_cat_unique_id").value = "";
                document.querySelector("#btn-emailcat-modal h2").textContent = "Create New Category";
                document.getElementById("createEmailCatBtn").textContent = "Create";

                refreshEmailCategoryCards();
            }
        })
        .catch(() => notyf.error("Update error. Try again."))
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = "Create";
        });

    });


    document.getElementById("btn-emailcat").addEventListener("click", function () {

    // Reset form
    const form = document.getElementById("email_cat");
    form.reset();
    $(form).parsley().reset();

    // Reset edit mode
    document.getElementById("email_cat_edit_mode").value = "0";
    document.getElementById("email_cat_unique_id").value = "";

    // Reset modal UI
    document.querySelector("#btn-emailcat-modal h2").textContent = "Create New Category";
    document.getElementById("createEmailCatBtn").textContent = "Create";

    // Open modal
    openModal("btn-emailcat-modal");
});





document.addEventListener("click", function(e) {

    const btn = e.target.closest(".delete-category-btn");
    if (!btn) return;

    let id = btn.dataset.id;

    window.showConfirm(
        "Delete this email category? This action cannot be undone!",
        "Delete Email Category"
    ).then((result) => {

        if (!result.isConfirmed) return;

        // Build delete URL using route
        let deleteUrl = "{{ route('admin.crm.message-management.email-category.delete', ':id') }}"
            .replace(':id', id);

        fetch(deleteUrl, {
            method: "DELETE",
            headers: {
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(r => r.json())
        .then(data => {
            if (data.status === "success") {
                notyf.success("Category deleted!");
                refreshEmailCategoryCards();
            } else {
                notyf.error("Failed to delete category.");
            }
        })
        .catch(err => {
            console.error(err);
            notyf.error("Server error. Try again.");
        });

    });
});



});


</script>



@endpush
