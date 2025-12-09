{{-- Interval Templates Tab --}}
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-6 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">Interval Templates</h2>
                <p class="text-sm text-gray-600 mt-1">Define hour intervals for service schedules</p>
            </div>
            <button
                @click="isCreatingIntervalPreset = true; resetPresetForm()"
                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                New Interval Template
            </button>
        </div>
    </div>

    {{-- Hour-Based Templates --}}
    <div class="p-6 border-b border-gray-200">
        <div class="flex items-center gap-2 mb-4">
            <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="text-lg font-semibold text-gray-900">Hourly Service Intervals</h3>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <template x-for="preset in presets.filter(p => p.interval_type === 'hour')" :key="preset.id">
                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-900" x-text="preset.name"></h3>
                            <p class="text-sm text-gray-600 mt-1" x-text="preset.description || 'No description'"></p>
                        </div>
                        <div class="flex items-center gap-1 ml-2">
                            <button
                                @click="editPreset(preset)"
                                class="p-1 text-blue-600 hover:bg-blue-50 rounded transition-colors"
                                title="Edit"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                            <button
                                @click="clonePreset(preset)"
                                class="p-1 text-green-600 hover:bg-green-50 rounded transition-colors"
                                title="Clone"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                            </button>
                            <button
                                @click="deletePreset(preset.id)"
                                class="p-1 text-red-600 hover:bg-red-50 rounded transition-colors"
                                title="Delete"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="flex flex-wrap gap-2">
                        <template x-for="interval in preset.intervals" :key="interval">
                            <span class="inline-flex items-center px-3 py-1 bg-blue-50 text-blue-700 text-sm font-medium rounded-full">
                                <span x-text="interval"></span>h
                            </span>
                        </template>
                    </div>
                    
                    <div class="mt-3 pt-3 border-t border-gray-100">
                        <p class="text-xs text-gray-500">
                            <span x-text="preset.intervals.length"></span> interval<span x-show="preset.intervals.length !== 1">s</span>
                        </p>
                    </div>
                </div>
            </template>

            <div x-show="presets.filter(p => p.interval_type === 'hour').length === 0" class="col-span-full text-center py-8 text-gray-500">
                <p class="text-sm">No hour-based templates created yet</p>
            </div>
        </div>
    </div>

    {{-- Date-Based Templates --}}
    <div class="p-6">
        <div class="flex items-center gap-2 mb-4">
            <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <h3 class="text-lg font-semibold text-gray-900">Daily Service Intervals</h3>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <template x-for="preset in presets.filter(p => p.interval_type === 'date')" :key="preset.id">
                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-900" x-text="preset.name"></h3>
                            <p class="text-sm text-gray-600 mt-1" x-text="preset.description || 'No description'"></p>
                        </div>
                        <div class="flex items-center gap-1 ml-2">
                            <button
                                @click="editPreset(preset)"
                                class="p-1 text-blue-600 hover:bg-blue-50 rounded transition-colors"
                                title="Edit"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                            <button
                                @click="clonePreset(preset)"
                                class="p-1 text-green-600 hover:bg-green-50 rounded transition-colors"
                                title="Clone"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                            </button>
                            <button
                                @click="deletePreset(preset.id)"
                                class="p-1 text-red-600 hover:bg-red-50 rounded transition-colors"
                                title="Delete"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="flex flex-wrap gap-2">
                        <template x-for="interval in preset.intervals" :key="interval">
                            <span class="inline-flex items-center px-3 py-1 bg-green-50 text-green-700 text-sm font-medium rounded-full">
                                <span x-text="interval"></span>d
                            </span>
                        </template>
                    </div>
                    
                    <div class="mt-3 pt-3 border-t border-gray-100">
                        <p class="text-xs text-gray-500">
                            <span x-text="preset.intervals.length"></span> interval<span x-show="preset.intervals.length !== 1">s</span>
                        </p>
                    </div>
                </div>
            </template>

            <div x-show="presets.filter(p => p.interval_type === 'date').length === 0" class="col-span-full text-center py-8 text-gray-500">
                <p class="text-sm">No date-based templates created yet</p>
            </div>
        </div>
    </div>
</div>

{{-- Create/Edit Interval Preset Modal --}}
<div
    x-show="isCreatingIntervalPreset"
    x-cloak
    class="fixed inset-0 z-[99999] overflow-y-auto bg-gray-500/75 transition-opacity flex justify-center items-center py-4"
    @click.self="isCreatingIntervalPreset = false; resetPresetForm()"
>
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-xl font-semibold text-gray-900">
                <span x-text="isEditingPreset ? 'Edit Interval Template' : 'New Interval Template'"></span>
            </h3>
        </div>

        <div class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Template Name *</label>
                <input
                    type="text"
                    x-model="presetForm.name"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="e.g., Standard Service Intervals"
                    required
                />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea
                    x-model="presetForm.description"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    rows="2"
                    placeholder="Optional description"
                ></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Interval Type *</label>
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            type="radio"
                            x-model="presetForm.interval_type"
                            value="hour"
                            class="w-4 h-4 text-blue-600 focus:ring-blue-500"
                        />
                        <span class="text-sm text-gray-700">Hour-Based</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            type="radio"
                            x-model="presetForm.interval_type"
                            value="date"
                            class="w-4 h-4 text-green-600 focus:ring-green-500"
                        />
                        <span class="text-sm text-gray-700">Date-Based</span>
                    </label>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <span x-show="presetForm.interval_type === 'hour'">Hour Intervals *</span>
                    <span x-show="presetForm.interval_type === 'date'">Date Intervals (Days) *</span>
                </label>
                <div class="flex gap-2 mb-3">
                    <input
                        type="text"
                        x-model="intervalInput"
                        @keydown.enter.prevent="addIntervals()"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        :placeholder="presetForm.interval_type === 'hour' ? 'Enter hours (e.g., 100, 200, 300 or comma separated)' : 'Enter days (e.g., 30, 60, 90 or comma separated)'"
                    />
                    <button
                        @click="addIntervals()"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                    >
                        Add
                    </button>
                </div>

                <div class="flex flex-wrap gap-2 min-h-[60px] p-3 border border-gray-200 rounded-lg bg-gray-50">
                    <template x-for="(interval, index) in presetForm.intervals" :key="index">
                        <span 
                            class="inline-flex items-center gap-1 px-3 py-1 text-white text-sm font-medium rounded-full"
                            :class="presetForm.interval_type === 'hour' ? 'bg-blue-600' : 'bg-green-600'"
                        >
                            <span x-text="interval"></span><span x-text="presetForm.interval_type === 'hour' ? 'h' : 'd'"></span>
                            <button
                                @click="removeInterval(index)"
                                class="ml-1 rounded-full p-0.5"
                                :class="presetForm.interval_type === 'hour' ? 'hover:bg-blue-700' : 'hover:bg-green-700'"
                            >
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </span>
                    </template>
                    <div x-show="presetForm.intervals.length === 0" class="text-gray-400 text-sm py-2">
                        No intervals added yet
                    </div>
                </div>
            </div>
        </div>

        <div class="p-6 border-t border-gray-200 flex justify-end gap-3">
            <button
                @click="isCreatingIntervalPreset = false; resetPresetForm()"
                class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
            >
                Cancel
            </button>
            <button
                @click="savePreset()"
                :disabled="!presetForm.name || presetForm.intervals.length === 0"
                :class="!presetForm.name || presetForm.intervals.length === 0 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-blue-700'"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg transition-colors"
            >
                <span x-text="isEditingPreset ? 'Update Template' : 'Create Template'"></span>
            </button>
        </div>
    </div>
</div>
