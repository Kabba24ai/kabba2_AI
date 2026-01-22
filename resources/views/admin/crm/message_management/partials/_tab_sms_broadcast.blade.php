<div id="send-new-sms-broadcast"
     class="fixed inset-0 z-[99999] hidden items-center justify-center bg-black/50 px-4 py-10">

    <div class="modal-scrollable w-full mx-auto">
        <form id="sendBroadcastForm" method="POST">
            @csrf
               <input type="hidden" name="broadcast_id" id="broadcast_id">
        <div class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-lg space-y-5
                    border border-gray-200 overflow-hidden flex flex-col max-h-full">

            <!-- HEADER -->
            <div class="flex justify-between items-center px-6 pt-4">
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-medium text-gray-900">Send New SMS Broadcast</h2>
                </div>

                <button type="button" class="text-gray-400 hover:text-gray-700 text-xl"
                        onclick="closeModal('send-new-sms-broadcast')">×</button>
            </div>

            <!-- BODY -->
            <div class="px-6 overflow-y-auto">

                <div class="space-y-6">

                    <!-- CATEGORY -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Select SMS Broadcast Message
                        </label>
                       <select class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm
                        focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        id="broadcast_select">

                        @foreach($createdBroadcasts as $broadcast)
                            <option
                                value="{{ $broadcast->id }}"
                                data-name="{{ $broadcast->name }}"
                                data-category="{{ $broadcast->category->name ?? 'No Category' }}"
                                data-description="{{ $broadcast->description }}"
                            >
                                {{ $broadcast->name }} ({{ $broadcast->category->name ?? 'No Category' }}) - Not Sent
                            </option>
                        @endforeach

                        @if($createdBroadcasts->isEmpty())
                            <option disabled>No unsent broadcasts</option>
                        @endif
                    </select>

                    </div>

                     <div class="border border-gray-300 rounded-lg p-4 bg-gray-50">
                        <div class="mb-2">
                            <label>Message Preview</label>
                        </div>

                        <div class="flex flex-wrap justify-between text-sm mb-3 gap-2">
                            <div>
                                <span class="font-medium text-gray-700">Category:</span>
                                <span class="text-gray-900" id="preview_category">-</span>
                            </div>

                            <div>
                                <span class="font-medium text-gray-700">Name:</span>
                                <span class="text-gray-900" id="preview_name">-</span>
                            </div>
                        </div>

                        <div id="preview_description"
                            class="text-gray-800 text-sm leading-relaxed border border-gray-300 rounded-md p-3">
                        </div>

                        <div class="text-left text-xs text-gray-500 mt-3">
                            <span id="preview_chars">0</span> characters
                        </div>


                    </div>

                </div>

            </div>

            <!-- FOOTER -->
            <div class="px-6 py-4 flex items-center justify-between gap-3 border-t border-gray-200">

                <!-- SEND BUTTON -->
                <button type="submit" id="sendBroadcastBtn" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium
                            px-6 py-3 text-md rounded-lg transition">
                    Send SMS Broadcast
                </button>

                <!-- CANCEL -->
                <button type="button" onclick="closeModal('send-new-sms-broadcast')"
                    class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium
                        px-6 py-3 text-md rounded-lg transition">
                    Cancel
                </button>

            </div>
        </div>
        </form>
    </div>

</div>



<div class="bg-white border border-gray-200 rounded-xl p-4 space-y-4 shadow-sm mb-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Filter -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Category</label>
            <select  name="sms_bro_filter_category" id="sms_bro_filter_category" class="w-full text-sm px-3 py-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option>All Categories</option>
                @foreach ($SmsCategory as $SmsCat)
                <option value="{{ $SmsCat->id }}">{{ $SmsCat->name }}</option>

                @endforeach
            </select>
        </div>

        <!-- Search -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Search by Content Name</label>
            <input type="text" id="sms_bro_Search_name" name="sms_bro_Search_name" placeholder="Search by content name..." class="w-full px-3 py-3 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>


    </div>
</div>

<div id="broadcast-table-wrapper">
    @include('admin.crm.message_management.partials._sms_broadcast_table', [
        'broadcasts' => []
    ])
</div>






@push('js')
<script>
document.addEventListener("DOMContentLoaded", () => {

    document.getElementById('sms-cat-open').addEventListener('click', function () {
        closeModal('new-smsbroadcast'); // Close current modal


        // Activate "Categories" tab from inside the modal
        const tabBtn = document.getElementById('tab-categories');
        if (tabBtn) {
            showTab('categories', tabBtn);
        }

        openModal('btn-smscat-modal'); // Open category modal
    });


    // ---------------------------------------
    // 1) FILTER & FETCH BROADCASTS
    // ---------------------------------------
    const categorySelect = document.querySelector('select[name="sms_bro_filter_category"]');
    const nameInput = document.querySelector('input[name="sms_bro_Search_name"]');
    const wrapper = document.getElementById('broadcast-table-wrapper');
    const freezeClass = ['opacity-50', 'pointer-events-none'];
    let timeout = null;
    const screenKey = "broadcast_filters";
    const fieldMap = {
        'sms_bro_filter_category': categorySelect,
        'sms_bro_Search_name': nameInput,
    };

    FilterFreezer.loadFilters(screenKey, fieldMap);

    function freezeUI() { wrapper.classList.add(...freezeClass); }
    function unfreezeUI() { wrapper.classList.remove(...freezeClass); }

    window.fetchBroadcasts = (page = 1, callback = null) => {
        const params = new URLSearchParams();
        if (nameInput.value.length >= 2 || nameInput.value.length === 0) {
            params.append('sms_bro_Search_name', nameInput.value);
        }
        if (categorySelect.value !== '' && categorySelect.value !== 'All Categories') {
            params.append('sms_bro_filter_category', categorySelect.value);
        }
        params.append('page', page);

        freezeUI();
        FilterFreezer.saveFilters(screenKey, fieldMap);

        fetch("{{ route('admin.crm.message-management.sms-broadcast.index') }}?" + params.toString(), {
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

    Paginator.init({ wrapper, fetchCallback: fetchBroadcasts });

    nameInput.addEventListener('input', () => {
        clearTimeout(timeout);
        timeout = setTimeout(() => fetchBroadcasts(1), 300);
    });

    categorySelect.addEventListener('change', () => fetchBroadcasts(1));
    fetchBroadcasts(1); // initial load

    // ---------------------------------------
    // 2) STORE SMS BROADCAST
    // ---------------------------------------
    const broadcastForm = document.getElementById("sms_broadcast_form");
    const broadcastSubmitBtn = document.getElementById("createSmsBroadcastBtn");
    const broadcastSelect = document.getElementById("broadcast_select");

    broadcastSubmitBtn.addEventListener("click", () => {
        if (!$(broadcastForm).parsley().isValid()) return;

        broadcastSubmitBtn.disabled = true;
        broadcastSubmitBtn.textContent = "Processing...";

        const formData = new FormData(broadcastForm);

        fetch("{{ route('admin.crm.message-management.sms-broadcast.store') }}", {
            method: "POST",
            headers: { "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === "success") {
                notyf.success("SMS Broadcast created!");
                closeModal('new-smsbroadcast');
                broadcastForm.reset();
                $(broadcastForm).parsley().reset();

                // Add new option to select
                const newOption = document.createElement("option");
                newOption.value = data.broadcast.id;
                newOption.dataset.name = data.broadcast.name;
                newOption.dataset.category = data.broadcast.category;
                newOption.dataset.description = data.broadcast.description;
                newOption.textContent = `${data.broadcast.name} (${data.broadcast.category}) - Not Sent`;
                broadcastSelect.appendChild(newOption);
                broadcastSelect.value = data.broadcast.id;
                updatePreview();

                 fetchCreatedBroadcasts();
            }
        })
        .catch(err => { console.error(err); notyf.error("Server error. Try again."); })
        .finally(() => { broadcastSubmitBtn.disabled = false; broadcastSubmitBtn.textContent = "Create Message"; });
    });

    // ---------------------------------------
    // 3) PREVIEW SELECT CHANGE
    // ---------------------------------------
    function updatePreview() {
        const opt = broadcastSelect.options[broadcastSelect.selectedIndex];
        if (!opt) return;
        document.getElementById("preview_category").textContent = opt.dataset.category;
        document.getElementById("preview_name").textContent = opt.dataset.name;
        document.getElementById("preview_description").textContent = opt.dataset.description;
        document.getElementById("preview_chars").textContent = opt.dataset.description.length;
    }
    broadcastSelect.addEventListener("change", updatePreview);
    updatePreview();

    // ---------------------------------------
    // 4) SEND BROADCAST
    // ---------------------------------------
    const sendBroadcastForm = document.getElementById("sendBroadcastForm");
    const sendBtn = document.getElementById("sendBroadcastBtn");

    sendBtn.addEventListener("click", e => {
        const broadcastId = broadcastSelect.value;
        if (!broadcastId) { e.preventDefault(); notyf.error("Please select a broadcast message."); return; }

        document.getElementById("broadcast_id").value = broadcastId;
        let sendUrl = "{{ route('admin.crm.message-management.sms-broadcast.send', ':id') }}";
        sendUrl = sendUrl.replace(":id", broadcastId);

        sendBtn.disabled = true;
        sendBtn.innerHTML = `<span class="animate-spin border-2 border-white border-t-transparent rounded-full w-4 h-4 inline-block mr-2"></span> Sending...`;

        sendBroadcastForm.action = sendUrl;
        sendBroadcastForm.submit();
    });

   


    // Character count update
    document.getElementById("edit_sms_broadcast_description").addEventListener("input", function() {
        document.getElementById("editCharCount").textContent = this.value.length;
    });

});
</script>



@if(session('open_edit_template'))
<script>
document.addEventListener("DOMContentLoaded", function () {
    const newId = "{{ session('open_edit_template') }}";

    // Wait until table is fetched and rendered
    fetchBroadcasts(1, () => {
        const btn = document.querySelector(`.edit-smsbrod-btn[data-id="${newId}"]`);
        if (btn) btn.click(); // open modal
    });
});
</script>
@endif




@endpush
