<div
    id="notification-modal"
    class="fixed inset-0 z-[99999] hidden items-center justify-center bg-black/50 px-4 py-10"
>
    <div
        class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-2xl
               space-y-4 border border-gray-200 overflow-hidden
               flex flex-col max-h-full"
    >

        <!-- Header -->
        <div class="flex items-center justify-between px-6 pt-4">
            <h3 class="text-lg font-semibold">
                Edit <span id="modal-title"></span> Notification
            </h3>

            <button type="button" id="close-modal" class="text-gray-500 hover:text-gray-700">
                <x-heroicon-o-x-mark class="w-5 h-5" />
            </button>
        </div>

        <!-- Actions -->
        <div class="flex gap-2 px-6">
            <button
                type="button"
                id="add-hrm"
                class="px-4 py-2 text-sm rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50"
            >
                ➕ Add from HRM
            </button>

            <button
                type="button"
                id="add-manual"
                class="px-4 py-2 text-sm rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50"
            >
                ➕ Add Manual
            </button>
        </div>

        <!-- Table (Scrollable Content Area) -->
        <div class="px-6 overflow-auto">
            <table class="w-full text-sm border border-gray-200 rounded-md overflow-hidden">
                <thead class="bg-gray-50 text-gray-700">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium">Name</th>
                        <th class="px-3 py-2 text-left font-medium">Phone</th>
                        <th class="px-3 py-2 w-10"></th>
                    </tr>
                </thead>
                <tbody id="edit-table-body" class="divide-y"></tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="flex justify-end gap-2 px-6 pb-4 pt-3 border-t bg-gray-50">
            <button
                type="button"
                id="cancel-modal"
                class="px-6 py-3 text-md rounded-lg font-medium border border-gray-300 bg-white text-gray-700"
            >
                Cancel
            </button>

            <button
                type="button"
                id="save-modal"
                class="px-6 py-3 text-md rounded-lg font-medium bg-blue-600 text-white hover:bg-blue-700"
            >
                Save
            </button>
        </div>

    </div>
</div>
