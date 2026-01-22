<div>
    {{-- Template Creation Wizard --}}
    <div x-show="isCreatingTemplate" x-cloak>
        <div class="mb-6">
            <div class="flex items-center gap-3">
                <button
                    @click="cancelTemplateCreation"
                    class="p-2 hover:bg-gray-100 rounded-lg"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </button>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Create New Template</h2>
                    <p class="text-gray-600 mt-1">
                        Step <span x-text="creationStepNumber"></span> of 4
                    </p>
                </div>
            </div>
        </div>

        {{-- Step 1: Template Info --}}
        <div x-show="creationStep === 'name'" class="bg-white rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Template Information</h3>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Template Name <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    x-model="templateForm.name"
                    placeholder="e.g., Boom Lifts, Skid Steer"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea
                    x-model="templateForm.description"
                    placeholder="Template description..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    rows="3"
                ></textarea>
            </div>
            <div class="flex gap-3">
                <button
                    @click="cancelTemplateCreation"
                    class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg"
                >
                    Cancel
                </button>
                <button
                    @click="nextCreationStep"
                    :disabled="!templateForm.name"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400"
                >
                    Next: Select Service Intervals
                </button>
            </div>
        </div>

        {{-- Step 2: Select Interval Preset --}}
        <div x-show="creationStep === 'interval' && !isCreatingIntervalPreset" class="bg-white rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Select Service Intervals</h3>
                    <p class="text-gray-600 mt-1">Choose an interval preset for this template</p>
                </div>
                <button
                    @click="isCreatingIntervalPreset = true"
                    class="flex items-center gap-2 px-4 py-2 text-blue-600 border border-blue-600 rounded-lg hover:bg-blue-50"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Interval Template
                </button>
            </div>

            {{-- Hour-Based Presets --}}
            <div class="mb-6">
                <div class="flex items-center gap-2 mb-3">
                    <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h4 class="font-semibold text-gray-900">Horly Service Intervals</h4>
                </div>
                <div class="grid grid-cols-2 gap-4" x-show="presets.filter(p => p.interval_type === 'hour').length > 0">
                    <template x-for="preset in presets.filter(p => p.interval_type === 'hour')" :key="preset.id">
                        <div class="relative">
                            <div class="absolute -top-2 -right-2 z-10 flex gap-1">
                                <button
                                    type="button"
                                    @click.stop="editPreset(preset)"
                                    class="p-2 bg-blue-600 text-white rounded-full hover:bg-blue-700 shadow-lg"
                                    title="Edit"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                </button>
                                <button
                                    type="button"
                                    @click.stop="clonePreset(preset)"
                                    class="p-2 bg-green-600 text-white rounded-full hover:bg-green-700 shadow-lg"
                                    title="Clone"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </button>
                                <button
                                    type="button"
                                    @click.stop="deletePreset(preset.id)"
                                    class="p-2 bg-red-600 text-white rounded-full hover:bg-red-700 shadow-lg"
                                    title="Delete"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                            <button
                                type="button"
                                @click="templateForm.preset_id = preset.id"
                                :class="templateForm.preset_id === preset.id ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300'"
                                class="w-full p-4 border-2 rounded-lg text-left transition-colors"
                            >
                                <div class="font-semibold text-gray-900 mb-1" x-text="preset.name"></div>
                                <div x-show="preset.description" class="text-sm text-gray-500 mb-3" x-text="preset.description"></div>
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="interval in preset.intervals" :key="interval">
                                        <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs">
                                            <span x-text="interval"></span>h
                                        </span>
                                    </template>
                                </div>
                            </button>
                        </div>
                    </template>
                </div>
                <div x-show="presets.filter(p => p.interval_type === 'hour').length === 0" class="text-center py-6 text-gray-500 text-sm">
                    No hour-based templates available
                </div>
            </div>

            {{-- Date-Based Presets --}}
            <div class="mb-6">
                <div class="flex items-center gap-2 mb-3">
                    <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <h4 class="font-semibold text-gray-900">Daily Service Intervals</h4>
                </div>
                <div class="grid grid-cols-2 gap-4" x-show="presets.filter(p => p.interval_type === 'date').length > 0">
                    <template x-for="preset in presets.filter(p => p.interval_type === 'date')" :key="preset.id">
                        <div class="relative">
                            <div class="absolute -top-2 -right-2 z-10 flex gap-1">
                                <button
                                    type="button"
                                    @click.stop="editPreset(preset)"
                                    class="p-2 bg-blue-600 text-white rounded-full hover:bg-blue-700 shadow-lg"
                                    title="Edit"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                </button>
                                <button
                                    type="button"
                                    @click.stop="clonePreset(preset)"
                                    class="p-2 bg-green-600 text-white rounded-full hover:bg-green-700 shadow-lg"
                                    title="Clone"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </button>
                                <button
                                    type="button"
                                    @click.stop="deletePreset(preset.id)"
                                    class="p-2 bg-red-600 text-white rounded-full hover:bg-red-700 shadow-lg"
                                    title="Delete"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                            <button
                                type="button"
                                @click="templateForm.preset_id = preset.id"
                                :class="templateForm.preset_id === preset.id ? 'border-green-500 bg-green-50' : 'border-gray-200 hover:border-gray-300'"
                                class="w-full p-4 border-2 rounded-lg text-left transition-colors"
                            >
                                <div class="font-semibold text-gray-900 mb-1" x-text="preset.name"></div>
                                <div x-show="preset.description" class="text-sm text-gray-500 mb-3" x-text="preset.description"></div>
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="interval in preset.intervals" :key="interval">
                                        <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs">
                                            <span x-text="interval"></span>d
                                        </span>
                                    </template>
                                </div>
                            </button>
                        </div>
                    </template>
                </div>
                <div x-show="presets.filter(p => p.interval_type === 'date').length === 0" class="text-center py-6 text-gray-500 text-sm">
                    No date-based templates available
                </div>
            </div>

            <div class="flex gap-3">
                <button
                    @click="previousCreationStep"
                    class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg"
                >
                    Previous
                </button>
                <button
                    @click="nextCreationStep"
                    :disabled="!templateForm.preset_id"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400"
                >
                    Next: Assign Tasks
                </button>
            </div>
        </div>

        {{-- Interval Preset Creation Sub-form --}}
        <div x-show="creationStep === 'interval' && isCreatingIntervalPreset" class="bg-white rounded-lg p-6">
            <div class="mb-6">
                <button
                    @click="isCreatingIntervalPreset = false; resetPresetForm()"
                    class="flex items-center gap-2 text-gray-600 hover:text-gray-900"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Select Intervals
                </button>
            </div>

            <h3 class="text-lg font-semibold text-gray-900 mb-4" x-text="isEditingPreset ? 'Edit Interval Template' : 'Create New Interval Template'"></h3>

            <form @submit.prevent="savePreset">
                <div class="space-y-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Template Name</label>
                        <input
                            type="text"
                            x-model="presetForm.name"
                            placeholder="e.g., Custom Equipment"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            required
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description (Optional)</label>
                        <input
                            type="text"
                            x-model="presetForm.description"
                            placeholder="e.g., Custom maintenance schedule"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Interval Type</label>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="radio"
                                    x-model="presetForm.interval_type"
                                    value="hour"
                                    class="w-4 h-4 text-blue-600 focus:ring-2 focus:ring-blue-500"
                                />
                                <span class="text-sm text-gray-700">Hour-Based</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="radio"
                                    x-model="presetForm.interval_type"
                                    value="date"
                                    class="w-4 h-4 text-green-600 focus:ring-2 focus:ring-green-500"
                                />
                                <span class="text-sm text-gray-700">Date-Based</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Service Intervals (<span x-text="presetForm.interval_type === 'date' ? 'days' : 'hours'"></span>)
                        </label>
                        <div class="flex gap-2">
                            <input
                                type="text"
                                x-model="intervalInput"
                                :placeholder="presetForm.interval_type === 'date' ? 'Enter days (comma separated, e.g., 30, 60, 90)' : 'Enter hours (comma separated, e.g., 50, 100, 250)'"
                                class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                            <button
                                type="button"
                                @click="addIntervals"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                            >
                                Add
                            </button>
                        </div>
                        <div x-show="presetForm.intervals.length > 0" class="flex flex-wrap gap-2 mt-3">
                            <template x-for="(interval, index) in presetForm.intervals" :key="index">
                                <span 
                                    class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm"
                                    :class="presetForm.interval_type === 'date' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'"
                                >
                                    <span x-text="interval"></span><span x-text="presetForm.interval_type === 'date' ? 'd' : 'h'"></span>
                                    <button
                                        type="button"
                                        @click="removeInterval(index)"
                                        :class="presetForm.interval_type === 'date' ? 'hover:text-green-900' : 'hover:text-blue-900'"
                                    >
                                        ×
                                    </button>
                                </span>
                            </template>
                        </div>
                    </div>
                </div>

                <button
                    type="submit"
                    :disabled="!presetForm.name || presetForm.intervals.length === 0"
                    class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400"
                    x-text="isEditingPreset ? 'Update Interval Template' : 'Create Interval Template'"
                >
                </button>
            </form>
        </div>

        {{-- Step 3: Select Tasks --}}
        <div x-show="creationStep === 'tasks'" class="bg-white rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Select Tasks</h3>
            
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Category</label>
                <p class="text-sm text-gray-500 mb-3">Choose categories to filter tasks. Click to toggle selection.</p>
                <div class="flex flex-wrap gap-2">
                    <template x-for="category in categories" :key="category.id">
                        <button
                            type="button"
                            @click="toggleCategoryFilter(category.id)"
                            :class="selectedCategoryIds.includes(category.id) ? 'text-white ring-2 ring-offset-2' : ''"
                            :style="{
                                backgroundColor: selectedCategoryIds.includes(category.id) ? category.color : category.color + '33',
                                color: selectedCategoryIds.includes(category.id) ? 'white' : category.color,
                                ringColor: selectedCategoryIds.includes(category.id) ? category.color : 'transparent'
                            }"
                            class="px-4 py-2 rounded-lg transition-colors"
                            x-text="category.name"
                        ></button>
                    </template>
                    <button
                        type="button"
                        @click="toggleCategoryFilter('uncategorized')"
                        :class="selectedCategoryIds.includes('uncategorized') ? 'bg-gray-400 text-white ring-2 ring-gray-400 ring-offset-2' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        class="px-4 py-2 rounded-lg transition-colors"
                    >
                        Uncategorized
                    </button>
                    <button
                        x-show="selectedCategoryIds.length > 0"
                        type="button"
                        @click="selectedCategoryIds = []"
                        class="px-4 py-2 rounded-lg bg-gray-700 text-white hover:bg-gray-800"
                    >
                        Clear Selection (Show All)
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto mb-6">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-center px-4 py-3 text-sm font-medium text-gray-700 w-12">
                                <input
                                    type="checkbox"
                                    @change="toggleAllFilteredTasks($event.target.checked)"
                                    :checked="allFilteredTasksSelected"
                                    class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
                                />
                            </th>
                            <th class="text-left px-4 py-3 text-sm font-medium text-gray-700">Task</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="task in getFilteredTasksForCreation()" :key="task.id">
                            <tr :class="!selectedTaskIds.has(task.id) ? 'opacity-40' : ''" class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="text-center px-4 py-3">
                                    <input
                                        type="checkbox"
                                        :checked="selectedTaskIds.has(task.id)"
                                        @change="toggleTaskSelection(task.id)"
                                        class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
                                    />
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="font-medium text-gray-900" x-text="task.name"></div>
                                    <template x-if="task.category">
                                        <div
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs mt-1"
                                            :style="{
                                                backgroundColor: task.category.color + '20',
                                                color: task.category.color
                                            }"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full" :style="{ backgroundColor: task.category.color }"></span>
                                            <span x-text="task.category.name"></span>
                                        </div>
                                    </template>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="flex gap-3">
                <button
                    @click="previousCreationStep"
                    class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg"
                >
                    Previous
                </button>
                <button
                    @click="nextCreationStep"
                    :disabled="selectedTaskIds.size === 0"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400"
                >
                    Next: Assign Intervals
                </button>
            </div>
        </div>

        {{-- Step 4: Assign Intervals --}}
        <div x-show="creationStep === 'assign'" class="bg-white rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Assign Tasks to Intervals</h3>
            <p class="text-sm text-gray-500 mb-6">Select which intervals each task should be performed at. Tasks marked as "Auto Apply" will have all intervals pre-selected.</p>

            <div class="overflow-x-auto mb-6">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left px-4 py-3 text-sm font-medium text-gray-700">Task</th>
                            <template x-for="interval in getSelectedPresetIntervals()" :key="interval">
                                <th class="text-center px-4 py-3 text-sm font-medium text-gray-700">
                                    <span x-text="interval"></span><span x-text="presets.find(p => p.id == templateForm.preset_id)?.interval_type === 'date' ? 'd' : 'h'"></span>
                                </th>
                            </template>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="task in getSelectedTasks()" :key="task.id">
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm">
                                    <div class="flex items-center gap-2">
                                        <div class="font-medium text-gray-900" x-text="task.name"></div>
                                        <span x-show="task.auto_apply" class="px-2 py-0.5 bg-blue-100 text-blue-700 text-xs rounded">Auto Apply</span>
                                    </div>
                                    <template x-if="task.category">
                                        <div
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs mt-1"
                                            :style="{
                                                backgroundColor: task.category.color + '20',
                                                color: task.category.color
                                            }"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full" :style="{ backgroundColor: task.category.color }"></span>
                                            <span x-text="task.category.name"></span>
                                        </div>
                                    </template>
                                </td>
                                <template x-for="interval in getSelectedPresetIntervals()" :key="interval">
                                    <td class="text-center px-4 py-3">
                                        <input
                                            type="checkbox"
                                            :checked="taskSelections[task.id]?.includes(interval)"
                                            @change="toggleIntervalInCreation(task.id, interval)"
                                            class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
                                        />
                                    </td>
                                </template>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="flex gap-3">
                <button
                    @click="previousCreationStep"
                    class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg"
                >
                    Previous
                </button>
                <button
                    @click="saveTemplate"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
                >
                    Create Template
                </button>
            </div>
        </div>
    </div>

    {{-- Template List and Editor --}}
    <div x-show="!isCreatingTemplate" x-cloak class="grid grid-cols-4 gap-6">
        {{-- Templates Sidebar --}}
        <div class="col-span-1">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900">Templates</h3>
                <button
                    @click="startTemplateCreation"
                    class="flex items-center gap-1 px-3 py-1.5 text-sm text-blue-600 hover:bg-blue-50 rounded-lg font-medium"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    New
                </button>
            </div>

            <div class="space-y-2 pr-5">
                <template x-for="template in templates" :key="template.id">
                    <button
                        @click="selectTemplate(template)"
                        :class="selectedTemplate?.id === template.id ? 'bg-blue-50 border-blue-200' : 'bg-white border-gray-200 hover:bg-gray-50'"
                        class="w-full text-left p-3 rounded-lg border transition-colors"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-medium text-gray-900" x-text="template.name"></div>
                                <div x-show="template.description" class="text-xs text-gray-500 mt-1" x-text="template.description"></div>
                            </div>
                            <svg :class="selectedTemplate?.id === template.id ? 'text-blue-600' : 'text-gray-400'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                        <div class="flex items-center gap-2 mt-2">
                            <button
                                @click.stop="deleteTemplate(template.id)"
                                class="p-1 text-red-600 hover:bg-red-50 rounded"
                            >
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </button>
                </template>
            </div>
        </div>

        {{-- Template Editor --}}
        <div class="col-span-3">
            <div x-show="selectedTemplate">
                <div class="mb-6 flex items-center justify-between">
                    <div class="flex-1">
                        <template x-if="!isEditingTemplate">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900" x-text="selectedTemplate?.name"></h2>
                                <p class="text-gray-600 mt-1" x-text="selectedTemplate?.description || 'Service template'"></p>
                            </div>
                        </template>
                        <template x-if="isEditingTemplate">
                            <div class="space-y-2 pr-3">
                                <input
                                    type="text"
                                    x-model="selectedTemplate.name"
                                    @change="updateTemplateInfo()"
                                    placeholder="Template name"
                                    class="w-full text-2xl font-bold px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                                <input
                                    type="text"
                                    x-model="selectedTemplate.description"
                                    @change="updateTemplateInfo()"
                                    placeholder="Template description"
                                    class="w-full text-gray-600 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                            </div>
                        </template>
                    </div>
                    <div class="flex items-center gap-3 ml-4">
                        
                        <button
                            @click="isEditingTemplate = !isEditingTemplate; if (!isEditingTemplate) selectedCategoryIds = []"
                            :class="isEditingTemplate ? 'bg-gray-600 hover:bg-gray-700' : 'bg-blue-600 hover:bg-blue-700'"
                            class="flex items-center gap-2 px-4 py-2 text-white rounded-lg"
                        >
                            <template x-if="!isEditingTemplate">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                            </template>
                            <span x-text="isEditingTemplate ? 'Done Editing' : 'Edit Template'"></span>
                        </button>
                        <button
                            @click="cloneTemplate(selectedTemplate.id)"
                            class="flex items-center gap-2 px-4 py-2 text-green-600 border border-green-600 rounded-lg hover:bg-green-50"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <span>Clone</span>
                        </button>
                        <button
                            @click="deleteTemplate(selectedTemplate.id)"
                            class="flex items-center gap-2 px-4 py-2 text-red-600 border border-red-600 rounded-lg hover:bg-red-50"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            <span>Delete Template</span>
                        </button>
                        
                    </div>
                </div>

                <div x-show="selectedTemplate?.preset" class="bg-white rounded-lg p-6">
                    {{-- Category filter when editing --}}
                    <div x-show="isEditingTemplate" class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Category</label>
                        <p class="text-sm text-gray-500 mb-3">Choose categories to show tasks from. Click to toggle selection.</p>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="category in categories" :key="category.id">
                                <button
                                    @click="toggleCategoryFilter(category.id)"
                                    :class="selectedCategoryIds.includes(category.id) ? 'text-white ring-2 ring-offset-2' : ''"
                                    :style="{
                                        backgroundColor: selectedCategoryIds.includes(category.id) ? category.color : category.color + '33',
                                        color: selectedCategoryIds.includes(category.id) ? 'white' : category.color
                                    }"
                                    class="px-4 py-2 rounded-lg transition-colors"
                                    x-text="category.name"
                                ></button>
                            </template>
                            <button
                                @click="toggleCategoryFilter('uncategorized')"
                                :class="selectedCategoryIds.includes('uncategorized') ? 'bg-gray-400 text-white ring-2 ring-gray-400 ring-offset-2' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                class="px-4 py-2 rounded-lg transition-colors"
                            >
                                Uncategorized
                            </button>
                            <button
                                x-show="selectedCategoryIds.length > 0"
                                @click="selectedCategoryIds = []"
                                class="px-4 py-2 rounded-lg bg-gray-700 text-white hover:bg-gray-800"
                            >
                                Clear Selection (Show All)
                            </button>
                        </div>
                    </div>

                    {{-- Template Tasks Table --}}
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th x-show="isEditingTemplate" class="text-center px-4 py-3 text-sm font-medium text-gray-700 w-12"></th>
                                    <th class="text-left px-4 py-3 text-sm font-medium text-gray-700">Task</th>
                                    <template x-if="selectedTemplate?.preset">
                                        <template x-for="interval in selectedTemplate.preset.intervals" :key="interval">
                                            <th class="text-center px-4 py-3 text-sm font-medium text-gray-700">
                                                <span x-text="interval"></span><span x-text="selectedTemplate.preset.interval_type === 'date' ? 'd' : 'h'"></span>
                                            </th>
                                        </template>
                                    </template>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="templateTask in getFilteredTemplateTasks()" :key="templateTask.id">
                                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                                        <td x-show="isEditingTemplate" class="text-center px-4 py-3">
                                            <input
                                                type="checkbox"
                                                checked
                                                @change="removeTemplateTask(templateTask.id)"
                                                class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500 cursor-pointer"
                                            />
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            <div class="font-medium text-gray-900" x-text="templateTask.task?.name"></div>
                                        </td>
                                        <template x-if="selectedTemplate?.preset">
                                            <template x-for="interval in selectedTemplate.preset.intervals" :key="interval">
                                                <td class="text-center px-4 py-3">
                                                    <input
                                                        type="checkbox"
                                                        :checked="templateTask.intervals && templateTask.intervals.includes(interval)"
                                                        @change="isEditingTemplate && toggleTemplateTaskInterval(templateTask, interval)"
                                                        :disabled="!isEditingTemplate"
                                                        :class="isEditingTemplate ? 'cursor-pointer' : 'cursor-default'"
                                                        class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
                                                    />
                                                </td>
                                            </template>
                                        </template>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    {{-- Edit Interval Template Section --}}
                    <div x-show="isEditingTemplate && selectedTemplate?.preset" class="mt-6 border-t pt-6" x-data="{ isEditingIntervals: false, tempIntervalInput: '', tempIntervals: [] }">
                        <h4 class="font-medium text-gray-900 mb-3">Edit Service Intervals</h4>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="mb-3">
                                <p class="text-sm text-gray-600 mb-2">
                                    Interval Template: <span class="font-semibold" x-text="selectedTemplate.preset.name"></span>
                                </p>
                                <div x-show="!isEditingIntervals" class="flex flex-wrap gap-2 mb-3">
                                    <template x-for="interval in selectedTemplate.preset.intervals" :key="interval">
                                        <span 
                                            class="px-2 py-1 rounded text-xs"
                                            :class="selectedTemplate.preset.interval_type === 'date' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'"
                                        >
                                            <span x-text="interval"></span><span x-text="selectedTemplate.preset.interval_type === 'date' ? 'd' : 'h'"></span>
                                        </span>
                                    </template>
                                </div>
                                
                                <div x-show="isEditingIntervals" class="space-y-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Service Intervals (<span x-text="selectedTemplate.preset.interval_type === 'date' ? 'days' : 'hours'"></span>)
                                        </label>
                                        <div class="flex gap-2">
                                            <input
                                                type="text"
                                                x-model="tempIntervalInput"
                                                @keyup.enter="
                                                    if (tempIntervalInput.trim()) {
                                                        const intervals = tempIntervalInput.split(',').map(i => parseInt(i.trim())).filter(i => !isNaN(i) && i > 0);
                                                        tempIntervals.push(...intervals);
                                                        tempIntervals = [...new Set(tempIntervals)].sort((a, b) => a - b);
                                                        tempIntervalInput = '';
                                                    }
                                                "
                                                :placeholder="selectedTemplate.preset.interval_type === 'date' ? 'Enter days (comma separated, e.g., 30, 60, 90)' : 'Enter hours (comma separated, e.g., 50, 100, 250)'"
                                                class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                            />
                                            <button
                                                type="button"
                                                @click="
                                                    if (tempIntervalInput.trim()) {
                                                        const intervals = tempIntervalInput.split(',').map(i => parseInt(i.trim())).filter(i => !isNaN(i) && i > 0);
                                                        tempIntervals.push(...intervals);
                                                        tempIntervals = [...new Set(tempIntervals)].sort((a, b) => a - b);
                                                        tempIntervalInput = '';
                                                    }
                                                "
                                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm"
                                            >
                                                Add
                                            </button>
                                        </div>
                                        <div x-show="tempIntervals.length > 0" class="flex flex-wrap gap-2 mt-3">
                                            <template x-for="(interval, index) in tempIntervals" :key="index">
                                                <span 
                                                    class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm"
                                                    :class="selectedTemplate.preset.interval_type === 'date' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'"
                                                >
                                                    <span x-text="interval"></span><span x-text="selectedTemplate.preset.interval_type === 'date' ? 'd' : 'h'"></span>
                                                    <button
                                                        type="button"
                                                        @click="tempIntervals.splice(index, 1)"
                                                        :class="selectedTemplate.preset.interval_type === 'date' ? 'hover:text-green-900' : 'hover:text-blue-900'"
                                                    >
                                                        ×
                                                    </button>
                                                </span>
                                            </template>
                                        </div>
                                    </div>
                                    <div class="flex gap-2">
                                        <button
                                            type="button"
                                            @click="isEditingIntervals = false; tempIntervals = []; tempIntervalInput = ''"
                                            class="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm"
                                        >
                                            Cancel
                                        </button>
                                        <button
                                            type="button"
                                            @click="async () => {
                                                if (tempIntervals.length === 0) {
                                                    showToast('Please add at least one interval', 'error');
                                                    return;
                                                }
                                                try {
                                                    const response = await fetch(`/maintenance-management/service-master/presets/${selectedTemplate.preset.id}`, {
                                                        method: 'PUT',
                                                        headers: {
                                                            'Content-Type': 'application/json',
                                                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                                            'Accept': 'application/json',
                                                        },
                                                        body: JSON.stringify({
                                                            name: selectedTemplate.preset.name,
                                                            description: selectedTemplate.preset.description,
                                                            intervals: tempIntervals,
                                                            interval_type: selectedTemplate.preset.interval_type
                                                        })
                                                    });
                                                    const data = await response.json();
                                                    if (data.success) {
                                                        selectedTemplate.preset.intervals = tempIntervals;
                                                        await loadPresets();
                                                        await loadTemplates();
                                                        isEditingIntervals = false;
                                                        tempIntervals = [];
                                                        tempIntervalInput = '';
                                                        showToast('Intervals updated successfully', 'success');
                                                    }
                                                } catch (error) {
                                                    console.error('Error:', error);
                                                    showToast('Failed to update intervals', 'error');
                                                }
                                            }"
                                            :disabled="tempIntervals.length === 0"
                                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:bg-gray-400 text-sm"
                                        >
                                            Save Changes
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <button
                                x-show="!isEditingIntervals"
                                @click="isEditingIntervals = true; tempIntervals = [...selectedTemplate.preset.intervals]"
                                class="flex items-center gap-2 px-4 py-2 text-blue-600 border border-blue-600 rounded-lg hover:bg-blue-50 text-sm"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                                <span x-text="selectedTemplate.preset.interval_type === 'date' ? 'Edit Interval Days' : 'Edit Interval Hours'"></span>
                            </button>
                        </div>
                    </div>

                    {{-- Add Tasks Section --}}
                    <div x-show="isEditingTemplate && getTasksNotInTemplate().length > 0" class="mt-6">
                        <h4 class="font-medium text-gray-900 mb-3">Add Tasks</h4>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b border-gray-200">
                                        <th class="text-left px-4 py-3 text-sm font-medium text-gray-700">Task Name</th>
                                        <th class="text-left px-4 py-3 text-sm font-medium text-gray-700">Category</th>
                                        <th class="text-left px-4 py-3 text-sm font-medium text-gray-700">Description</th>
                                        <th class="text-center px-4 py-3 text-sm font-medium text-gray-700 w-24">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="task in getTasksNotInTemplate()" :key="task.id">
                                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm">
                                                <span class="font-medium text-gray-900" x-text="task.name"></span>
                                            </td>
                                            <td class="px-4 py-3 text-sm">
                                                <template x-if="task.category">
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs"
                                                        :style="{
                                                            backgroundColor: task.category.color + '20',
                                                            color: task.category.color
                                                        }">
                                                        <span class="w-1.5 h-1.5 rounded-full" :style="{ backgroundColor: task.category.color }"></span>
                                                        <span x-text="task.category.name"></span>
                                                    </span>
                                                </template>
                                                <template x-if="!task.category">
                                                    <span class="text-gray-400 text-xs">-</span>
                                                </template>
                                            </td>
                                            <td class="px-4 py-3 text-sm">
                                                <span x-show="task.description" class="text-gray-600" x-text="task.description"></span>
                                                <span x-show="!task.description" class="text-gray-400">-</span>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <button
                                                    @click="addTaskToTemplate(task.id)"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-600 text-white text-xs rounded-lg hover:bg-blue-700"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                    </svg>
                                                    Add
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>

            <div x-show="!selectedTemplate" class="flex items-center justify-center h-64 text-gray-500">
                Select a template to configure
            </div>
        </div>
    </div>
</div>