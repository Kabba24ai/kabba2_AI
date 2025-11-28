<div>
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Settings</h2>
        <p class="text-gray-600 mt-1">Configure interval presets and notification thresholds</p>
    </div>

    {{-- Notification Settings --}}
    <div class="bg-white rounded-lg p-6 mb-6">
        <h3 class="text-xl font-semibold text-gray-900 mb-2">Notification Settings</h3>
        <p class="text-gray-600 mb-6">Configure when service status changes between conditions</p>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <h4 class="font-semibold text-blue-900 mb-2">How Service Status Works</h4>
                    <ul class="space-y-2 text-sm text-blue-800">
                        <li>
                            <span class="font-semibold">Not Due (Grey):</span> Service is not yet due (equipment hours are more than "Before" threshold away from service interval)
                        </li>
                        <li>
                            <span class="font-semibold">Pending (Yellow):</span> Service is due soon or slightly overdue (equipment hours are within the "Before" threshold or up to the "After" threshold past the service interval)
                        </li>
                        <li>
                            <span class="font-semibold">Overdue (Red):</span> Service is significantly overdue (equipment hours exceed service interval by more than the "After" threshold)
                        </li>
                        <li>
                            <span class="font-semibold">Completed (Green):</span> Service has been completed and recorded
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <form @submit.prevent="saveSettings">
            <div class="grid grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Pending Before (Yellow) - Hours
                    </label>
                    <input
                        type="number"
                        x-model="settingsForm.pending_before_hours"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                    <p class="text-sm text-gray-500 mt-2">
                        Hours before service is due to show yellow status
                    </p>
                    <div class="mt-3 p-3 bg-gray-50 rounded-lg">
                        <p class="text-sm text-gray-700">
                            <span class="font-semibold">Example:</span> If set to <span x-text="settingsForm.pending_before_hours"></span> hours, equipment at <span x-text="250 - parseInt(settingsForm.pending_before_hours)"></span> hours will show yellow for a 250-hour service (<span x-text="settingsForm.pending_before_hours"></span> hours before due).
                        </p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Pending After (Yellow) - Hours
                    </label>
                    <input
                        type="number"
                        x-model="settingsForm.pending_after_hours"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                    <p class="text-sm text-gray-500 mt-2">
                        Hours after service is due to continue showing yellow before turning red
                    </p>
                    <div class="mt-3 p-3 bg-gray-50 rounded-lg">
                        <p class="text-sm text-gray-700">
                            <span class="font-semibold">Example:</span> If set to <span x-text="settingsForm.pending_after_hours"></span> hours, equipment at <span x-text="250 + parseInt(settingsForm.pending_after_hours)"></span> hours will show yellow for a 250-hour service (<span x-text="settingsForm.pending_after_hours"></span> hours overdue), but <span x-text="250 + parseInt(settingsForm.pending_after_hours) + 1"></span> hours will show red.
                        </p>
                    </div>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Master Admin Code
                </label>
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <input
                        type="text"
                        x-model="settingsForm.master_admin_code"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Enter admin code"
                    />
                </div>
                <p class="text-sm text-gray-500 mt-2">
                    This code is required to edit completed service records
                </p>
            </div>

            <button
                type="submit"
                :disabled="isSaving"
                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:bg-gray-400"
                x-text="isSaving ? 'Saving...' : 'Save Settings'"
            >
            </button>
        </form>
    </div>

    {{-- Task Categories Management --}}
    <div class="bg-white rounded-lg p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-semibold text-gray-900">Task Categories</h3>
            <button
                @click="isAddingCategory = true"
                class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Category
            </button>
        </div>

        {{-- Add Category Form --}}
        <div x-show="isAddingCategory" x-cloak class="mb-4 p-4 bg-gray-50 rounded-lg">
            <form @submit.prevent="addCategory">
                <div class="grid grid-cols-3 gap-4 mb-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category Name</label>
                        <input
                            type="text"
                            x-model="newCategoryName"
                            placeholder="Enter category name"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            required
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <input
                            type="text"
                            x-model="newCategoryDescription"
                            placeholder="Enter description"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Color</label>
                        <input
                            type="color"
                            x-model="newCategoryColor"
                            class="w-full h-10 px-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                    </div>
                </div>
                <div class="flex gap-2">
                    <button
                        type="submit"
                        :disabled="!newCategoryName.trim()"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:bg-gray-400"
                    >
                        Save
                    </button>
                    <button
                        type="button"
                        @click="isAddingCategory = false; newCategoryName = ''; newCategoryDescription = ''; newCategoryColor = '#3B82F6'"
                        class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700"
                    >
                        Cancel
                    </button>
                </div>
            </form>
        </div>

        {{-- Categories List --}}
        <div class="space-y-2">
            <template x-for="category in categories" :key="category.id">
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <template x-if="editingCategoryId === category.id">
                        <div class="flex-1 grid grid-cols-3 gap-4">
                            <input
                                type="text"
                                x-model="editCategoryName"
                                placeholder="Category name"
                                class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                            <input
                                type="text"
                                x-model="editCategoryDescription"
                                placeholder="Description"
                                class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                            <input
                                type="color"
                                x-model="editCategoryColor"
                                class="h-10 px-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                        </div>
                    </template>
                    <template x-if="editingCategoryId !== category.id">
                        <div class="flex items-center gap-3">
                            <div class="w-6 h-6 rounded" :style="{ backgroundColor: category.color }"></div>
                            <div>
                                <div class="font-medium text-gray-900" x-text="category.name"></div>
                                <div x-show="category.description" class="text-xs text-gray-600" x-text="category.description"></div>
                            </div>
                        </div>
                    </template>
                    <div class="flex items-center gap-2">
                        <template x-if="editingCategoryId === category.id">
                            <div class="flex gap-2">
                                <button
                                    @click="saveEditCategory(category.id)"
                                    class="p-2 text-green-600 hover:bg-green-50 rounded-lg"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                                <button
                                    @click="cancelEditCategory"
                                    class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </template>
                        <template x-if="editingCategoryId !== category.id">
                            <div class="flex gap-2">
                                <button
                                    @click="startEditCategory(category)"
                                    class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                </button>
                                <button
                                    @click="deleteCategory(category.id)"
                                    class="p-2 text-red-600 hover:bg-red-50 rounded-lg"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Status Example --}}
    <div class="bg-white rounded-lg p-6">
        <h3 class="text-xl font-semibold text-gray-900 mb-4">Status Example Based on Current Settings</h3>

        <div class="space-y-4">
            <div class="flex items-center gap-4">
                <div class="w-24 h-12 bg-gray-500 rounded-lg flex items-center justify-center text-white font-semibold">
                    Grey
                </div>
                <div>
                    <div class="font-semibold text-gray-900">Service Not Due</div>
                    <div class="text-sm text-gray-600">
                        For a 250h service: Equipment has &lt; <span x-text="250 - parseInt(settingsForm.pending_before_hours)"></span> hours (more than <span x-text="settingsForm.pending_before_hours"></span> hours away)
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <div class="w-24 h-12 bg-yellow-500 rounded-lg flex items-center justify-center text-white font-semibold">
                    Yellow
                </div>
                <div>
                    <div class="font-semibold text-gray-900">Service Pending</div>
                    <div class="text-sm text-gray-600">
                        For a 250h service: Equipment has <span x-text="250 - parseInt(settingsForm.pending_before_hours)"></span> - <span x-text="250 + parseInt(settingsForm.pending_after_hours)"></span> hours (within <span x-text="settingsForm.pending_before_hours"></span>h before to <span x-text="settingsForm.pending_after_hours"></span>h after)
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <div class="w-24 h-12 bg-red-500 rounded-lg flex items-center justify-center text-white font-semibold">
                    Red
                </div>
                <div>
                    <div class="font-semibold text-gray-900">Service Overdue</div>
                    <div class="text-sm text-gray-600">
                        For a 250h service: Equipment has &gt; <span x-text="250 + parseInt(settingsForm.pending_after_hours)"></span> hours (more than <span x-text="settingsForm.pending_after_hours"></span> hours overdue)
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <div class="w-24 h-12 bg-green-500 rounded-lg flex items-center justify-center text-white font-semibold">
                    Green
                </div>
                <div>
                    <div class="font-semibold text-gray-900">Service Completed</div>
                    <div class="text-sm text-gray-600">
                        Service has been completed and recorded in the system
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

