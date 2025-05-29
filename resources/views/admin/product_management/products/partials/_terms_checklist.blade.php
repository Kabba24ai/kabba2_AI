<div x-show="type === 'rental'" x-transition class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
    <!-- Terms -->
    <div class="border border-gray-200 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 p-4 space-y-3">
        <h4 class="text-sm font-semibold text-blue-700 dark:text-blue-400 mb-2">Terms</h4>

        <div class="flex items-center gap-4">
            <label class="inline-flex items-center space-x-2 text-sm text-gray-700 dark:text-gray-200">
                <input type="checkbox" name="general_terms" value="1" checked
                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-700" />
                <span>General Terms</span>
            </label>

            <label class="inline-flex items-center space-x-2 text-sm text-gray-700 dark:text-gray-200">
                <input type="checkbox" name="product_specific_terms" x-model="showTermSelect"
                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:bg-gray-900 dark:border-gray-700" />
                <span>Add Product Specific Terms</span>
            </label>
        </div>

        <!-- Terms Dropdown -->
        <div x-show="showTermSelect" x-transition>
            <select name="terms[]" class="mt-2 w-full rounded border-gray-300 text-sm dark:bg-gray-900 dark:text-white dark:border-gray-700">
                <option value="">Select Terms</option>
                <option value="term-1">Term 1</option>
                <option value="term-2">Term 2</option>
                <option value="term-3">Term 3</option>
            </select>
        </div>
    </div>

    <!-- Checklist -->
    <div class="border border-gray-200 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 p-4">
        <h4 class="text-sm font-semibold text-blue-700 dark:text-blue-400 mb-2">Checklist</h4>

        <select name="checklist_id" class="w-full rounded border-gray-300 text-sm dark:bg-gray-900 dark:text-white dark:border-gray-700">
            <option value="">Select Checklist</option>
            <option value="1">Checklist A</option>
            <option value="2">Checklist B</option>
        </select>
    </div>
</div>
