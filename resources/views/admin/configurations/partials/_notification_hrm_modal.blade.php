<div
    id="hrm-modal"
    class="fixed inset-0 z-[99999] hidden items-center justify-center bg-black/50 px-4 py-10"
>
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6 space-y-4">

        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold">
                Select HRM Users
            </h3>
            <button type="button" id="close-hrm-modal">
                <x-heroicon-o-x-mark class="w-5 h-5 text-gray-500" />
            </button>
        </div>

        <div id="hrm-user-list" class="max-h-80 overflow-y-auto space-y-2">
            <!-- injected by JS -->
        </div>

        <div class="flex justify-end gap-2 pt-4 border-t">
            <button
                type="button"
                id="cancel-hrm-modal"
                class="px-6 py-3 border text-md font-medium rounded-lg" 
            >
                Cancel
            </button>

            <button
                type="button"
                id="save-hrm-modal"
                class="px-6 py-3 bg-blue-600 text-white text-md font-medium rounded-lg"
            >
                Add Selected
            </button>
        </div>
    </div>
</div>
