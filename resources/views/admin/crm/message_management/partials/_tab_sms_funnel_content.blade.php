<div id="send-new-sms-funnel"
     class="fixed inset-0 z-[99999] hidden items-center justify-center bg-black/50 px-4 py-10">

    <div class="modal-scrollable w-full mx-auto">

     <form id="sendFunnelForm" method="POST">
            @csrf
            <input type="hidden" name="funnel_id" id="funnel_id">
        <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5
                    border border-gray-200 overflow-hidden flex flex-col max-h-full">

            <!-- HEADER -->
            <div class="flex justify-between items-center px-6 pt-4">
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-medium text-gray-900">Send New SMS Broadcast</h2>
                </div>

                <button type="button" class="text-gray-400 hover:text-gray-700 text-xl"
                        onclick="closeModal('send-new-sms-funnel')">×</button>
            </div>

            <!-- BODY -->
            <div class="px-6 overflow-y-auto">

                    <!-- CATEGORY -->
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Select SMS Broadcast Message
                        </label>
                        <select
                            class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm
                                focus:ring-2 focus:ring-blue-500 focus:outline-none" id="funnel_select">
                                @foreach($createdFunnels as $funnel)
                                    <option
                                        value="{{ $funnel->id }}"
                                        data-name="{{ $funnel->name }}"
                                        data-category="{{ $funnel->category->name ?? 'No Category' }}"
                                        data-description="{{ $funnel->description }}"
                                    >
                                        {{ $funnel->name }} ({{ $funnel->category->name ?? 'No Category' }})
                                    </option>
                                @endforeach

                               @if($createdFunnels->isEmpty())
                                    <option disabled>No funnels created yet</option>
                                @endif
                        </select>
                    </div>

                     <div class="border border-gray-300 rounded-lg p-4 bg-gray-50">
                        <div class="mb-2">
                            <label>Message Preview</label>
                        </div>

                        <div class="flex flex-wrap items-center justify-between text-sm mb-3 gap-2">
                            <div>
                                <span class="font-medium text-gray-700">Category:</span>
                                    <span class="text-gray-900" id="funnel_preview_category">-</span>
                            </div>

                            <div>
                               <span class="font-medium text-gray-700">Name:</span>
                                    <span class="text-gray-900" id="funnel_preview_name">-</span>
                            </div>
                        </div>

                         <div id="funnel_preview_description"
                                 class="text-gray-800 text-sm leading-relaxed border border-gray-300 rounded-md p-3">
                            </div>

                        <div class="text-left text-xs text-gray-500 mt-3">
                                <span id="funnel_preview_chars">0</span> characters
                            </div>

                    </div>

                

            </div>

            <!-- FOOTER -->
            <div class="px-6 py-4 flex items-center justify-between gap-3 border-t border-gray-200">

                <!-- SEND BUTTON -->
                <button type="submit" id="sendFunnelBtn" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium
                            px-6 py-3 rounded-lg text-md transition">
                    Send SMS Broadcast
                </button>

                <!-- CANCEL -->
                <button  type="button"  onclick="closeModal('send-new-sms-funnel')"
                    class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium
                        px-6 py-3 rounded-lg text-md transition">
                    Cancel
                </button>

            </div>
        </div>

         </form>
    </div>
</div>

<div id="copy-msg-funnel"
     class="fixed inset-0 z-[99999] hidden items-center justify-center bg-black/50 px-4 py-10">

    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5 border border-gray-200 overflow-hidden flex flex-col max-h-full">

            <!-- HEADER -->
            <div class="flex justify-between items-center px-6 pt-4">
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-medium text-gray-900">Copy Message</h2>
                </div>

                <button type="button" class="text-gray-400 hover:text-gray-700 text-xl" onclick="closeModal('copy-msg-funnel')">×</button>
            </div>

           <div class="px-6 overflow-y-auto">

                <form class="space-y-6">

                    <!-- CATEGORY -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                          Content Category <span class="text-red-500">*</span>
                        </label>
                        <select class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm text-gray-700">
                            <option value="">Select Category</option>
                            <option>SMS Broadcast</option>
                            <option>SMS Funnel</option>
                            <option>Email Broadcast</option>
                            <option>Email Funnel</option>
                        </select>


                    </div>

                    <!-- CONTENT NAME -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Content Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               placeholder="BOGO Weekend Special (Copy)"
                               class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm"/>
                    </div>

                    <!-- CONTENT TEXT -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Content <span class="text-red-500">*</span>
                        </label>

                        <textarea id="contentText"
                                  rows="4"
                                  placeholder="Write your message here..."
                                  class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm"></textarea>

                        <!-- CHAR COUNTER -->
                         <div class="text-left text-xs text-gray-500">
                            166 characters
                        </div>

                    </div>

                </form>

            </div>

            <div class="px-6 py-4 flex items-center justify-between gap-3 border-t border-gray-200">

                <!-- SEND BUTTON -->
                <button type="button" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium
                            px-6 py-3 rounded-lg text-md transition">
                    Create Message
                </button>

                <!-- CANCEL -->
                <button type="button" onclick="closeModal('copy-msg-funnel')"
                    class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium
                        px-6 py-3 rounded-lg text-md transition">
                    Cancel
                </button>

            </div>

        </div>
    </div>
</div>

<!-- CREATE NEW MESSENGER MODAL -->
<div id="new-sms-funnel"
     class="fixed inset-0 z-[99999] hidden items-center justify-center bg-black/50 px-4 py-10">

    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5
                    border border-gray-200 overflow-hidden flex flex-col max-h-full">

            <!-- HEADER -->
            <div class="flex justify-between items-center px-6 pt-4">
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <h2 class="text-lg font-medium text-gray-900">Create New Message</h2>
                </div>

                <button type="button" class="text-gray-400 hover:text-gray-700 text-xl"
                        onclick="closeModal('new-sms-funnel')">×</button>
            </div>

            {{ html()->form()->id('sms_funnel_form')->attributes([
                'autocomplete' => 'off',
                'data-parsley-validate' => true,
                'class' => 'space-y-8'
            ])->open() }}
            @csrf

            <!-- BODY -->
            <div class="px-6 overflow-y-auto">

                <!-- <form class="space-y-6"> -->

                    <!-- CATEGORY -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Category <span class="text-red-500">*</span>
                        </label>

                           {!! html()->select('sms_funnel_cat_id')
                        ->class([
                            'w-full border rounded-md px-3 py-3 text-sm',
                            'border-gray-300'
                        ])
                        ->placeholder('Select Category')
                        ->id('sms_funnel_cat_id')
                        ->required()
                    !!}



                    </div>

                    <!-- CONTENT NAME -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Content Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name"
                               placeholder="e.g. Welcome Message"
                               class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm"/>

                    </div>



                    <!-- CONTENT TEXT -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Content <span class="text-red-500">*</span>
                        </label>

                        {!! html()->textarea('description')
                        ->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm char-count-input')
                        ->attributes(['data-counter' => 'charCountFunnel'])
                        ->rows(4)
                        ->placeholder('Write your message...')
                        ->id('sms_funnel_description')
                        ->required()
                    !!}
                    <p class="text-xs text-gray-500 mt-1">
                        Characters: <span id="charCountFunnel">0</span>
                    </p>
                    </div>

                <!-- </form> -->

            </div>

            <!-- FOOTER -->
            <div class="flex justify-end gap-2 px-6 pb-4">
                <button  type="button"  onclick="closeModal('new-sms-funnel')"
                        class="px-6 py-3 text-md rounded-lg font-medium border border-gray-300 bg-white text-gray-700">
                    Cancel
                </button>

                <button type="submit" id="createSmsFunnelBtn" class="px-6 py-3 text-md rounded-lg font-medium bg-blue-600 text-white hover:bg-blue-700">
                    Create Message
                </button>
            </div>

                {{ html()->form()->close() }}

        </div>
    </div>
</div>


<!-- EDIT SMS FUNNEL MODAL -->
<div id="edit-sms-funnel"
     class="fixed inset-0 z-[99999] hidden items-center justify-center bg-black/50 px-4 py-10">

    <div class="modal-scrollable w-full mx-auto">
        <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5
                    border border-gray-200 overflow-hidden flex flex-col max-h-full">

            <!-- HEADER -->
            <div class="flex justify-between items-center px-6 pt-4">
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="w-5 h-5 text-yellow-500" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M3 3h18v18H3z" />
                    </svg>
                    <h2 class="text-lg font-medium text-gray-900">Edit SMS Funnel</h2>
                </div>

                <button type="button" class="text-gray-400 hover:text-gray-700 text-xl"
                        onclick="closeModal('edit-sms-funnel')">×</button>
            </div>

            <!-- FORM -->
            <form id="edit_sms_funnel_form" class="space-y-8 px-6 overflow-y-auto"
                  autocomplete="off" data-parsley-validate>

                <input type="hidden" id="edit_sms_funnel_id" name="id">

                <!-- CATEGORY -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Category <span class="text-red-500">*</span>
                    </label>
                    <select id="edit_sms_funnel_cat_id" name="sms_funnel_cat_id"
                            class="w-full border rounded-md px-3 py-3 text-sm"
                            required>
                        <option value="">Select Category</option>
                    </select>
                </div>

                <!-- FUNNEL NAME -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Funnel Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="edit_sms_funnel_name" name="name"
                           class="w-full border rounded-md px-3 py-3 text-sm"
                           placeholder="Enter funnel title..."
                           required>
                </div>

                <!-- FUNNEL DESCRIPTION -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Funnel Content <span class="text-red-500">*</span>
                    </label>
                    <!-- <textarea id="edit_sms_funnel_description" name="description"
                              rows="4"
                              class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm"
                              placeholder="Write SMS funnel message..."
                              required></textarea> -->

                               {!! html()->textarea('description')
                        ->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm char-count-input')
                        ->attributes(['data-counter' => 'editFunnelCharCount'])
                        ->rows(4)
                        ->placeholder('Write SMS funnel message...')
                        ->id('edit_sms_funnel_description')
                        ->required()
                    !!}

                    <!-- CHAR COUNTER -->
                    <p class="text-xs text-gray-500 mt-1">
                        Characters: <span id="editFunnelCharCount">0</span>
                    </p>
                </div>
                <!-- CONTENT TEXT -->
                    <!-- <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Content <span class="text-red-500">*</span>
                        </label>

                        {!! html()->textarea('description')
                        ->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm char-count-input')
                        ->attributes(['data-counter' => 'charCountFunnel'])
                        ->rows(4)
                        ->placeholder('Write your message...')
                        ->id('sms_funnel_description')
                        ->required()
                    !!}
                    <p class="text-xs text-gray-500 mt-1">
                        Characters: <span id="charCountFunnel">0</span>
                    </p>
                    </div> -->

                <!-- FOOTER -->
                <div class="flex justify-end gap-2 pb-4">
                    <button type="button" onclick="closeModal('edit-sms-funnel')"
                            class="px-6 py-3 text-md rounded-lg font-medium border border-gray-300 bg-white text-gray-700">
                        Cancel
                    </button>
                    <button type="submit" id="updateSmsFunnelBtn"
                            class="px-6 py-3 text-md rounded-lg font-medium bg-yellow-500 text-white hover:bg-yellow-600">
                        Update Funnel
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>



<div class="bg-white border border-gray-200 rounded-xl p-4 space-y-4 shadow-sm mb-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Category</label>
            <select name="sms_funnel_filter_category" id="sms_funnel_filter_category" class="w-full text-sm px-3 py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option>All Categories</option>
                 @foreach ($SmsCategory as $SmsCat)
                <option value="{{ $SmsCat->id }}">{{ $SmsCat->name }}</option>

                @endforeach
            </select>
        </div>

        <!-- Search -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Search by Content Name</label>
            <input type="text" id="sms_funnel_Search_name" name="sms_funnel_Search_name" placeholder="Search by content name..." class="w-full px-3 py-3 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>


    </div>
</div>


<div id="smsfunnel-table-wrapper">
    @include('admin.crm.message_management.partials._sms_funnel_table', [
        'smsfunnels' => []
    ])
</div>


@push('js')
<script>
document.addEventListener("DOMContentLoaded", () => {
   

    

    // ---------------------------------------
    // 1) FILTER & FETCH funnel
    // ---------------------------------------
    const categorySelect = document.querySelector('select[name="sms_funnel_filter_category"]');
    const nameInput = document.querySelector('input[name="sms_funnel_Search_name"]');
    const wrapper = document.getElementById('smsfunnel-table-wrapper');
    const freezeClass = ['opacity-50', 'pointer-events-none'];
    let timeout = null;
    const screenKey = "smsfunnel_filters";
    const fieldMap = {
        'sms_funnel_filter_category': categorySelect,
        'sms_funnel_Search_name': nameInput,
    };

    FilterFreezer.loadFilters(screenKey, fieldMap);

    function freezeUI() { wrapper.classList.add(...freezeClass); }
    function unfreezeUI() { wrapper.classList.remove(...freezeClass); }

    window.fetchSmsfunnels = (page = 1, callback = null) => {
        const params = new URLSearchParams();
        if (nameInput.value.length >= 2 || nameInput.value.length === 0) {
            params.append('sms_funnel_Search_name', nameInput.value);
        }
        if (categorySelect.value !== '' && categorySelect.value !== 'All Categories') {
            params.append('sms_funnel_filter_category', categorySelect.value);
        }
        params.append('page', page);

        freezeUI();
        FilterFreezer.saveFilters(screenKey, fieldMap);

        fetch("{{ route('admin.crm.message-management.sms-funnel.index') }}?" + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
       .then(data => {
        wrapper.innerHTML = data.html;

        //  Trigger callback after table is rendered
        if (typeof callback === 'function') callback();
    })
        .catch(() => { wrapper.innerHTML = `<div class='p-4 text-red-500'>Failed to load data!</div>`; })
        .finally(() => unfreezeUI());
    }

    Paginator.init({ wrapper, fetchCallback: fetchSmsfunnels });

    nameInput.addEventListener('input', () => {
        clearTimeout(timeout);
        timeout = setTimeout(() => fetchSmsfunnels(1), 300);
    });

    categorySelect.addEventListener('change', () => fetchSmsfunnels(1));
    fetchSmsfunnels(1); // initial load


    // -----------------------
    // Store SMS Funnel
    // -----------------------
    const smsfunnelForm = document.getElementById("sms_funnel_form");
    const smsfunnelSubmitBtn = document.getElementById("createSmsFunnelBtn");
    const smsfunnelSelect = document.getElementById("funnel_select");

    smsfunnelSubmitBtn.addEventListener("click", (e) => {
        e.preventDefault();

        const isValid = $(smsfunnelForm).parsley().isValid();

        smsfunnelSubmitBtn.disabled = true;
        smsfunnelSubmitBtn.textContent = "Processing...";
     
        const formData = new FormData(smsfunnelForm);

        fetch("{{ route('admin.crm.message-management.sms-funnel.store') }}", {
            method: "POST",
            headers: { "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content },
            body: formData
        })
        .then(async res => {
           
            const data = await res.json().catch(e => {
                console.error(" JSON Parse Error", e);
                throw e;
            });
            console.log(" JSON Response:", data);
            return data;
        })
        .then(data => {
            if (data.status === "success") {
                console.log(" Success! SMS Funnel created:", data.funnel);
                notyf.success("SMS Funnel created!");
                closeModal('new-sms-funnel');
                smsfunnelForm.reset();
                $(smsfunnelForm).parsley().reset();




                
                // Add new created funnel to select dropdown
                const option = document.createElement("option");
                option.value = data.funnel.id;
                option.dataset.name = data.funnel.name;
                option.dataset.category = data.funnel.category;
                option.dataset.description = data.funnel.description;
                option.textContent = `${data.funnel.name} (${data.funnel.category}) - Not Sent`;

                smsfunnelSelect.appendChild(option);
                smsfunnelSelect.value = data.funnel.id;
                updateFunnelPreview();



            } else {
                console.warn("⚠ API returned non-success status:", data);
                notyf.error(data.message || "Unexpected Server Response");
            }
        })
        .catch(err => {
            console.error(" Fetch Error:", err);
            notyf.error("Server error. Try again.");
        })
        .finally(() => {
            console.log(" Submit finished.");
            smsfunnelSubmitBtn.disabled = false;
            smsfunnelSubmitBtn.textContent = "Create Message";
        });
    });



    // -----------------------
    // Character Counter
    // -----------------------
    document.querySelectorAll(".char-count-input").forEach(input => {

        input.addEventListener("input", function () {
            const counterId = this.dataset.counter;
            const countTarget = document.getElementById(counterId);
            const len = this.value.length;
            if (countTarget) {
                countTarget.textContent = len;

            }
        });
    });

  // ---------------------------------------
    // 4) SEND BROADCAST
    // ---------------------------------------
    const sendFunnelForm = document.getElementById("sendFunnelForm");
    const sendFunnelBtn = document.getElementById("sendFunnelBtn");

    sendFunnelBtn.addEventListener("click", e => {
        const funnelId = smsfunnelSelect.value;
        if (!funnelId) { e.preventDefault(); notyf.error("Please select a funnel message."); return; }

        document.getElementById("funnel_id").value = funnelId;
        let sendUrl = "{{ route('admin.crm.message-management.sms-funnel.send', ':id') }}";
        sendUrl = sendUrl.replace(":id", funnelId);

        sendFunnelBtn.disabled = true;
        sendFunnelBtn.innerHTML = `<span class="animate-spin border-2 border-white border-t-transparent rounded-full w-4 h-4 inline-block mr-2"></span> Sending...`;

        sendFunnelForm.action = sendUrl;
        sendFunnelForm.submit();
    });



    

    // ---------------------------------------
    //  PREVIEW FUNNEL CHANGE
    // ---------------------------------------
    function updateFunnelPreview() {
        const opt = smsfunnelSelect.options[smsfunnelSelect.selectedIndex];
        if (!opt) return;

        document.getElementById("funnel_preview_category").textContent = opt.dataset.category;
        document.getElementById("funnel_preview_name").textContent = opt.dataset.name;
        document.getElementById("funnel_preview_description").textContent = opt.dataset.description;
        document.getElementById("funnel_preview_chars").textContent = opt.dataset.description.length;
    }
    smsfunnelSelect.addEventListener("change", updateFunnelPreview);
    updateFunnelPreview();


    // ---------------------------------------
// 5) EDIT FUNNEL MODAL (DELEGATED)
// ---------------------------------------
const routes = {
    showFUNNEL: "{{ route('admin.crm.message-management.sms-funnel.show', ['id' => '__ID__']) }}",
    updateFUNNEL: "{{ route('admin.crm.message-management.sms-funnel.update', ['id' => '__ID__']) }}"
};

document.addEventListener("click", e => {
    const btn = e.target.closest(".edit-sms-funnel-btn");
    if (!btn) return;

    const id = btn.dataset.id;
    const showUrl = routes.showFUNNEL.replace('__ID__', id);

    fetch(showUrl)
        .then(res => res.json())
        .then(data => {
            const funnel = data.funnel;
            const categories = data.categories;

            document.getElementById("edit_sms_funnel_id").value = funnel.id;
            document.getElementById("edit_sms_funnel_name").value = funnel.name;
            document.getElementById("edit_sms_funnel_description").value = funnel.description;

            const catSelect = document.getElementById("edit_sms_funnel_cat_id");
            catSelect.innerHTML = `<option value="">Select Category</option>`;
            categories.forEach(c => {
                catSelect.innerHTML += `
                    <option value="${c.id}" ${c.id == funnel.sms_cat_id ? "selected" : ""}>
                        ${c.name}
                    </option>
                `;
            });


            openModal('edit-sms-funnel');
        })
        .catch(() => notyf.error("Error loading data"));
});

// ---------------------------------------
// 6) UPDATE FUNNEL
// ---------------------------------------
document.getElementById("edit_sms_funnel_form").addEventListener("submit", function(e) {
    e.preventDefault();

    const btn = document.getElementById("updateSmsFunnelBtn");
    btn.disabled = true;
    btn.textContent = "Updating...";

    const id = document.getElementById("edit_sms_funnel_id").value;
    const formData = new FormData(this);
    formData.append('_method', 'PUT');

    const updateUrl = routes.updateFUNNEL.replace('__ID__', id);

    fetch(updateUrl, {
        method: "POST",
        headers: { "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === "success") {
            notyf.success("SMS Funnel updated!");

            closeModal('edit-sms-funnel');
            fetchSmsfunnels(1); // Refresh table
        } else {
            notyf.error(data.message || "Update failed.");
        }
    })
    .catch(err => {
        console.error(err);
        notyf.error("Server error");
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = "Update Funnel";
    });
});


    

});

</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Delegate to handle dynamic buttons
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.delete-sms-funnel-btn');
        if (!btn) return;

        const funnelId = btn.dataset.id;
        const funnelName = btn.dataset.name || 'this message';

        window.showConfirm(
            `Delete "${funnelName}"? This action cannot be undone!`,
            'Delete SMS Broadcast'
        ).then((result) => {
            if (result.isConfirmed) {
                // Send AJAX delete request
                fetch(`{{ route('admin.crm.message-management.sms-funnel.delete', ':id') }}`.replace(':id', funnelId), {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        notyf.success(data.message);
                        // Optionally remove the row from the table
                        btn.closest('tr').remove();
                    } else {
                        notyf.error(data.message || 'Delete failed.');
                    }
                })
                .catch(err => {
                    console.error(err);
                    notyf.error('Server error. Try again.');
                });
            }
        });
    });
});
</script>

@if(session('open_edit_sms_funnel'))
<script>
document.addEventListener("DOMContentLoaded", function () {
    const newId = "{{ session('open_edit_sms_funnel') }}";

    // Load funnels and auto-click the correct one
    fetchSmsfunnels(1, () => {
        const btn = document.querySelector(`.edit-sms-funnel-btn[data-id="${newId}"]`);
        if (btn) btn.click();
    });
});
</script>
@endif

@endpush
