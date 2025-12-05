<div id="btn-smscat-modal"
     class="fixed inset-0 z-[99999] hidden items-center justify-center bg-black/50 px-4 py-10">

    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 
                    border border-gray-200 overflow-hidden flex flex-col max-h-full">

            <!-- HEADER -->
            <div class="flex justify-between items-center px-6 pt-4">
                <h2 class="text-lg font-medium text-gray-900">Create New SMS Category</h2>
                <button type="button" class="text-gray-400 hover:text-gray-700 text-xl"
                        onclick="closeModal('btn-smscat-modal')">×</button>
            </div>

            {{ html()->form()->id('sms_cat')->attributes([
                'autocomplete' => 'off',
                'data-parsley-validate' => true,
                'class' => 'space-y-8',
            ])->open() }}

                <input type="hidden" id="sms_cat_edit_mode" value="0">
                <input type="hidden" id="sms_cat_unique_id" value="">

                <!-- BODY -->
                <div class="px-6 overflow-y-auto">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>

                        {!! html()->text('sms_cat_name', old('sms_cat_name', ''))
                            ->class('w-full px-3 py-3 text-sm border border-gray-300 rounded-md focus:ring-blue-500')
                            ->attributes(['placeholder' => 'Enter SMS category name...', 'id' => 'sms_cat_name'])
                            ->required() !!}
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>

                        {!! html()->textarea('sms_cat_description', old('sms_cat_description'))
                            ->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm')
                            ->rows(4)
                            ->placeholder('Enter description...') !!}
                    </div>

                </div>

                <!-- FOOTER -->
                <div class="px-6 py-4 flex items-center justify-between gap-3 border-t border-gray-200">
                    <button type="submit" id="createSmsCatBtn"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium 
                        px-6 py-3 rounded-lg text-md transition">
                        Create
                    </button>

                    <button type="button" onclick="closeModal('btn-smscat-modal')"
                        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium
                        px-6 py-3 rounded-lg text-md transition">
                        Cancel
                    </button>
                </div>

            {{ html()->form()->close() }}

        </div>
    </div>
</div>

            <div id="sms-cat-cardsWrapper"></div>




            
@push('js')
<script>
document.addEventListener("DOMContentLoaded", () => {

    /*
    |--------------------------------------------------------------------------
    | Load SMS Categories
    |--------------------------------------------------------------------------
    */
    function refreshSmsCategoryCards() {
        fetch("{{ route('admin.crm.message-management.sms-category.index') }}")
            .then(response => response.json())
            .then(result => {

                if (result.status !== "success") {
                    console.error("Could not load SMS categories.");
                    return;
                }

                  const categories = result.data;



                const wrapper = document.getElementById("sms-cat-cardsWrapper");
                wrapper.innerHTML = "";

            //  Show message when no categories exist
            if (categories.length === 0) {
                wrapper.innerHTML = `
                    <div class="text-center py-10 text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-lg font-medium">No SMS categories found</p>
                        <p class="text-sm text-gray-400">Click "New SMS Category" to create one.</p>
                    </div>
                `;
                return;
            }


                result.data.forEach(cat => {
                    wrapper.innerHTML += `
                        <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm mb-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900">${cat.name}</h3>
                                    <p class="text-sm text-gray-600 mt-1">${cat.description ?? ''}</p>
                                </div>

                                <div class="flex items-center gap-4 text-gray-600 text-sm">

                                    <button type="button" 
                                            class="edit-smscat-btn text-green-600 hover:text-green-800"
                                            data-id="${cat.unique_id}">
                                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125"></path>
                                </svg>
                                    </button>

                                    <button type="button"
                                            class="delete-smscat-btn text-red-600 hover:text-red-800"
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

    refreshSmsCategoryCards();


    /*
    |--------------------------------------------------------------------------
    | Store SMS Category
    |--------------------------------------------------------------------------
    */
    const form = document.getElementById("sms_cat");
    const submitBtn = document.getElementById("createSmsCatBtn");

    submitBtn.addEventListener("click", function (e) {

        if (document.getElementById("sms_cat_edit_mode").value === "1") {
            return; // skip store — edit mode active
        }

        if (!$(form).parsley().isValid()) return;
        e.preventDefault();

        submitBtn.disabled = true;
        submitBtn.textContent = "Processing...";

        let formData = new FormData(form);

        fetch("{{ route('admin.crm.message-management.sms-category.store') }}", {
            method: "POST",
            headers: { "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content },
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.status === "success") {
                notyf.success("SMS Category created!");
                closeModal('btn-smscat-modal');

                form.reset();
                $(form).parsley().reset();

                refreshSmsCategoryCards();
            }
        })
        .catch(() => notyf.error("Server error. Try again."))
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = "Create";
        });
    });


    /*
    |--------------------------------------------------------------------------
    | Open Edit Modal
    |--------------------------------------------------------------------------
    */
    document.addEventListener("click", function (e) {

        const btn = e.target.closest(".edit-smscat-btn");
        if (!btn) return;

        let id = btn.dataset.id;

        let showUrl = "{{ route('admin.crm.message-management.sms-category.show', ':id') }}"
                        .replace(':id', id);

        fetch(showUrl)
            .then(r => r.json())
            .then(data => {

                if (data.status !== "success") return;

                document.getElementById("sms_cat_edit_mode").value = "1";
                document.getElementById("sms_cat_unique_id").value = id;

                document.getElementById("sms_cat_name").value = data.data.name;
                document.getElementById("sms_cat_description").value = data.data.description ?? "";

                document.querySelector("#btn-smscat-modal h2").textContent = "Edit SMS Category";
                submitBtn.textContent = "Update";

                openModal("btn-smscat-modal");
            });
    });


    /*
    |--------------------------------------------------------------------------
    | Update SMS Category
    |--------------------------------------------------------------------------
    */
    submitBtn.addEventListener("click", function (e) {

        if (document.getElementById("sms_cat_edit_mode").value === "0") {
            return; // skip update — not in edit mode
        }

        if (!$(form).parsley().isValid()) return;
        e.preventDefault();

        submitBtn.disabled = true;
        submitBtn.textContent = "Updating...";

        let id = document.getElementById("sms_cat_unique_id").value;

        let formData = new FormData(form);
        formData.append("_method", "PUT");

        let updateUrl = "{{ route('admin.crm.message-management.sms-category.update', ':id') }}"
                            .replace(':id', id);

        fetch(updateUrl, {
            method: "POST",
            headers: { "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content },
            body: formData
        })
        .then(r => r.json())
        .then(data => {

            if (data.status === "success") {
                notyf.success("SMS Category updated!");

                closeModal('btn-smscat-modal');

                form.reset();
                $(form).parsley().reset();

                document.getElementById("sms_cat_edit_mode").value = "0";
                document.getElementById("sms_cat_unique_id").value = "";

                document.querySelector("#btn-smscat-modal h2").textContent = "Create New SMS Category";
                submitBtn.textContent = "Create";

                refreshSmsCategoryCards();
            }
        })
        .catch(() => notyf.error("Update error. Try again."))
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = "Create";
        });
    });


    /*
    |--------------------------------------------------------------------------
    | Open Create Modal (RESET modal every time)
    |--------------------------------------------------------------------------
    */
    document.getElementById("btn-smscat").addEventListener("click", function () {

        form.reset();
        $(form).parsley().reset();

        document.getElementById("sms_cat_edit_mode").value = "0";
        document.getElementById("sms_cat_unique_id").value = "";

        document.querySelector("#btn-smscat-modal h2").textContent = "Create New SMS Category";
        submitBtn.textContent = "Create";

        openModal("btn-smscat-modal");
    });


    /*
    |--------------------------------------------------------------------------
    | Delete SMS Category
    |--------------------------------------------------------------------------
    */
    document.addEventListener("click", function (e) {

        const btn = e.target.closest(".delete-smscat-btn");
        if (!btn) return;

        let id = btn.dataset.id;

        window.showConfirm(
            "Delete this SMS category? This action cannot be undone!",
            "Delete SMS Category"
        ).then((result) => {

            if (!result.isConfirmed) return;

            let deleteUrl = "{{ route('admin.crm.message-management.sms-category.delete', ':id') }}"
                                .replace(':id', id);

            fetch(deleteUrl, {
                method: "DELETE",
                headers: { "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content }
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === "success") {
                    notyf.success("SMS Category deleted!");
                    refreshSmsCategoryCards();
                }
            })
            .catch(() => notyf.error("Delete error. Try again."));
        });
    });

});
</script>
@endpush
