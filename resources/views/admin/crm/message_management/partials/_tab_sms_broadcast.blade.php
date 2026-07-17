{{-- SMS Message Library tab — reusable content. Sending happens in the
     Broadcast Wizard (separate page); this tab never shows send state. --}}

<div class="bg-white border border-gray-200 rounded-xl p-4 space-y-4 shadow-sm mb-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Filter -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Category</label>
            <select name="sms_bro_filter_category" id="sms_bro_filter_category" class="w-full text-sm px-3 py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option>All Categories</option>
                @foreach ($SmsCategory as $SmsCat)
                <option value="{{ $SmsCat->id }}">{{ $SmsCat->name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Search -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Search by Message Name</label>
            <input type="text" id="sms_bro_Search_name" name="sms_bro_Search_name" placeholder="Search by message name..." class="w-full px-3 py-3 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <!-- Active / Archived -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Show</label>
            <select id="sms_bro_archived" class="w-full text-sm px-3 py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Active Messages</option>
                <option value="1">Archived Messages</option>
            </select>
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
        closeModal('new-smsbroadcast');
        const tabBtn = document.getElementById('tab-categories');
        if (tabBtn) showTab('categories', tabBtn);
        openModal('btn-smscat-modal');
    });

    // ---------------------------------------
    // 1) FILTER & FETCH LIBRARY MESSAGES
    // ---------------------------------------
    const categorySelect = document.querySelector('select[name="sms_bro_filter_category"]');
    const nameInput = document.querySelector('input[name="sms_bro_Search_name"]');
    const archivedSelect = document.getElementById('sms_bro_archived');
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
        if (archivedSelect.value === '1') {
            params.append('archived', '1');
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
    archivedSelect.addEventListener('change', () => fetchBroadcasts(1));
    fetchBroadcasts(1); // initial load

    // ---------------------------------------
    // 2) STORE LIBRARY MESSAGE
    // ---------------------------------------
    const broadcastForm = document.getElementById("sms_broadcast_form");
    const broadcastSubmitBtn = document.getElementById("createSmsBroadcastBtn");

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
                notyf.success("SMS message saved to the library!");
                closeModal('new-smsbroadcast');
                broadcastForm.reset();
                $(broadcastForm).parsley().reset();
                fetchBroadcasts(1);
            }
        })
        .catch(err => { console.error(err); notyf.error("Server error. Try again."); })
        .finally(() => { broadcastSubmitBtn.disabled = false; broadcastSubmitBtn.textContent = "Create Message"; });
    });

    // ---------------------------------------
    // 3) ARCHIVE / RESTORE (delegated — table is AJAX-replaced)
    // ---------------------------------------
    document.addEventListener('click', e => {
        const btn = e.target.closest('.archive-smsbroadcast-btn');
        if (!btn) return;

        fetch("{{ route('admin.crm.message-management.sms-broadcast.archive', ':id') }}".replace(':id', btn.dataset.id), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                notyf.success(data.message);
                fetchBroadcasts(1);
            } else {
                notyf.error(data.message || 'Action failed.');
            }
        })
        .catch(() => notyf.error('Server error. Try again.'));
    });

    // Character count update for the edit modal
    document.getElementById("edit_sms_broadcast_description").addEventListener("input", function() {
        document.getElementById("editCharCount").textContent = this.value.length;
    });

});
</script>

@if(session('open_edit_template'))
<script>
document.addEventListener("DOMContentLoaded", function () {
    const newId = "{{ session('open_edit_template') }}";
    fetchBroadcasts(1, () => {
        const btn = document.querySelector(`.edit-smsbrod-btn[data-id="${newId}"]`);
        if (btn) btn.click();
    });
});
</script>
@endif

@endpush
