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

             fetchCreatedBroadcasts();
             
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
