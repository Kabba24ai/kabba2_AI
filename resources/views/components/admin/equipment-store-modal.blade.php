@props(['stores' => []])

{{-- ===== Equipment Location / Store Assign Modal =====
     Shared across: Equipment Mgt, Schedule Assignment, Equipment Inventory, Dispatch.
     Trigger: any button with class "store-assign-btn" carrying:
       data-equipment-unique-id   - equipment.unique_id
       data-equipment-name        - display name for modal title
     After save: fires CustomEvent "equipmentStoreUpdated" on document so each
     host page can refresh its own table without knowing about this modal.
     NOTE: JS is inline (not @push) so it works inside Blade components.
--}}

<div id="storeAssignModal"
    class="fixed inset-0 z-[99999] hidden overflow-y-auto bg-gray-500/75 transition-opacity flex justify-center items-center">
    <div class="bg-white rounded-lg w-full max-w-lg shadow-lg flex flex-col">
        <div class="flex justify-between items-center p-4 border-b">
            <h2 class="text-lg font-semibold">Location: <span id="storeAssignModalTitle" class="capitalize"></span></h2>
            <button type="button"
                class="close-store-assign-modal text-2xl text-gray-400 hover:text-gray-700 leading-none focus:outline-none">&times;</button>
        </div>
        <form id="storeAssignForm" class="flex-1">
            <div class="overflow-y-auto flex flex-col gap-y-4 px-4 py-4">
                <div>
                    <label class="text-sm font-medium text-gray-700 required" for="store_unique_id_modal">Store / Location</label>
                    <select name="store_unique_id" id="store_unique_id_modal"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-500 bg-white text-gray-700"
                        required>
                        <option value="">Select Store</option>
                        @foreach ($stores as $uniqueId => $storeName)
                            <option value="{{ $uniqueId }}">{{ $storeName }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="flex justify-end gap-3 items-center px-6 py-4 border-t bg-gray-50 rounded-b-lg">
                <button type="button"
                    class="close-store-assign-modal px-5 py-2 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
                    Cancel
                </button>
                <button type="submit" id="store-assign-submit"
                    class="px-6 py-2 rounded-md bg-blue-600 text-white font-medium hover:bg-blue-700 shadow-sm transition">
                    Save Location
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    // Wait for DOM ready in case this script runs before the modal element is parsed
    function init() {
        const modal       = document.getElementById('storeAssignModal');
        const form        = document.getElementById('storeAssignForm');
        const titleEl     = document.getElementById('storeAssignModalTitle');
        const submitBtn   = document.getElementById('store-assign-submit');
        const storeSelect = document.getElementById('store_unique_id_modal');
        let activeEquipmentUniqueId = '';

        if (!modal || !form) return; // guard: already initialised by another instance

        function openModal(equipmentUniqueId, equipmentName) {
            activeEquipmentUniqueId = equipmentUniqueId;
            titleEl.textContent = equipmentName || '';
            storeSelect.selectedIndex = 0;
            modal.classList.remove('hidden');
        }

        function closeModal() {
            modal.classList.add('hidden');
            activeEquipmentUniqueId = '';
            titleEl.textContent = '';
            storeSelect.selectedIndex = 0;
            submitBtn.disabled = false;
            submitBtn.textContent = 'Save Location';
        }

        // Open — event delegation works even for AJAX-rendered table rows
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.store-assign-btn');
            if (!btn) return;
            openModal(
                btn.getAttribute('data-equipment-unique-id') || '',
                btn.getAttribute('data-equipment-name') || ''
            );
        });

        // Close via × button or Cancel
        document.addEventListener('click', function (e) {
            if (e.target.closest('.close-store-assign-modal')) closeModal();
        });
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeModal();
        });

        // Submit
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!activeEquipmentUniqueId) return;

            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving…';

            const formData = new FormData(form);
            formData.append('equipment_unique_id', activeEquipmentUniqueId);

            window.apiFetch('{{ route('admin.maintenance-management.equipment.store-assign') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'Accept': 'application/json',
                },
                body: formData,
            }).then(function (data) {
                closeModal();
                if (window.notyf) notyf.success(data.message);
                document.dispatchEvent(new CustomEvent('equipmentStoreUpdated', {
                    detail: { equipmentUniqueId: activeEquipmentUniqueId }
                }));
            }).catch(function () {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Save Location';
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
