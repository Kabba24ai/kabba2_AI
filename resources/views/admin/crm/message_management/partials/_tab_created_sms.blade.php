
<div class="bg-white border border-gray-200 rounded-xl p-4 space-y-4 shadow-sm mb-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Filter -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Category</label>
            <select  name="sms_created_bro_filter_category" id="sms_created_bro_filter_category" class="w-full text-sm px-3 py-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option>All Categories</option>
                @foreach ($SmsCategory as $SmsCat)
                <option value="{{ $SmsCat->id }}">{{ $SmsCat->name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Search -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Search by Content Name</label>
            <input type="text" id="sms_created_bro_Search_name" name="sms_created_bro_Search_name" placeholder="Search by content name..." class="w-full px-3 py-3 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>


    </div>
</div>

<div id="created-broadcast-table-wrapper">
    @include('admin.crm.message_management.partials._sms_created_table', [
        'broadcasts' => []
    ])
</div>

@push('js')

<script>
document.addEventListener("DOMContentLoaded", () => {

    // ---------------------------------------
    // 1) FILTER & FETCH BROADCASTS
    // ---------------------------------------
    const categorySelect = document.querySelector('select[name="sms_created_bro_filter_category"]');
    const nameInput = document.querySelector('input[name="sms_created_bro_Search_name"]');
    const wrapper = document.getElementById('created-broadcast-table-wrapper');
    const freezeClass = ['opacity-50', 'pointer-events-none'];
    let timeout = null;
    const screenKey = "created_broadcast_filters";
    const fieldMap = {
        'sms_created_bro_filter_category': categorySelect,
        'sms_created_bro_Search_name': nameInput,
    };

    FilterFreezer.loadFilters(screenKey, fieldMap);

    function freezeUI() { wrapper.classList.add(...freezeClass); }
    function unfreezeUI() { wrapper.classList.remove(...freezeClass); }

    window.fetchCreatedBroadcasts = (page = 1, callback = null) => {
        const params = new URLSearchParams();
        if (nameInput.value.length >= 2 || nameInput.value.length === 0) {
            params.append('sms_created_bro_Search_name', nameInput.value);
        }
        if (categorySelect.value !== '' && categorySelect.value !== 'All Categories') {
            params.append('sms_created_bro_filter_category', categorySelect.value);
        }
        params.append('page', page);

        freezeUI();
        FilterFreezer.saveFilters(screenKey, fieldMap);

        fetch("{{ route('admin.crm.message-management.sms-created-broadcast.index') }}?" + params.toString(), {
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

    Paginator.init({ wrapper, fetchCallback: fetchCreatedBroadcasts });

        nameInput.addEventListener('input', () => {
            clearTimeout(timeout);
            timeout = setTimeout(() => fetchCreatedBroadcasts(1), 300);
        });

        categorySelect.addEventListener('change', () => fetchCreatedBroadcasts(1));
        fetchCreatedBroadcasts(1); // initial load




});
</script>
@endpush
