<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Service Tasks Repository</h2>
            <p class="text-gray-600 mt-1">Manage service tasks that can be added to templates</p>
        </div>
        <button
            @click="showNewTaskForm = true; editingTask = null; resetTaskForm()"
            class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
        >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add Task
        </button>
    </div>

    {{-- New/Edit Task Form --}}
    <div x-show="showNewTaskForm" x-cloak class="bg-white rounded-lg border-2 border-blue-500 p-6 mb-6">
        <h3 class="text-lg font-semibold mb-4" x-text="editingTask ? 'Edit Service Task' : 'New Service Task'"></h3>
        <form @submit.prevent="saveTask">
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Task Name <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        x-model="taskForm.name"
                        placeholder="e.g., Oil Change"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        required
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Estimated Duration (minutes)
                    </label>
                    <input
                        type="number"
                        x-model="taskForm.estimated_duration"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea
                    x-model="taskForm.description"
                    placeholder="Task description..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    rows="3"
                ></textarea>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <select
                    x-model="taskForm.category_id"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                    <option value="">Uncategorized</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label class="flex items-center gap-2">
                    <input
                        type="checkbox"
                        x-model="taskForm.auto_apply"
                        class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
                    />
                    <span class="text-sm font-medium text-gray-700">Apply to all intervals by default</span>
                </label>
                <p class="text-sm text-gray-500 ml-6 mt-1">
                    When checked, this task will be automatically selected for all time intervals when added to a template
                </p>
            </div>

            <div class="flex gap-2">
                <button
                    type="submit"
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors"
                >
                    Save
                </button>
                <button
                    type="button"
                    @click="showNewTaskForm = false; editingTask = null"
                    class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
                >
                    Cancel
                </button>
            </div>
        </form>
    </div>

    {{-- Task Categories Section --}}
    <div class="bg-white rounded-lg p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900">Task Categories</h3>
            <button
                @click="showNewCategoryForm = !showNewCategoryForm"
                class="flex items-center gap-2 px-3 py-1.5 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors"
            >
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                New Task Category
            </button>
        </div>

        {{-- New Category Form --}}
        <form x-show="showNewCategoryForm" x-cloak @submit.prevent="saveCategory" class="mb-4 p-4 bg-gray-50 rounded-lg">
            <div class="grid grid-cols-3 gap-4">
                <input
                    type="text"
                    x-model="categoryForm.name"
                    placeholder="Category name"
                    class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    required
                />
                <input
                    type="text"
                    x-model="categoryForm.description"
                    placeholder="Description"
                    class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <div class="flex gap-2">
                    <input
                        type="color"
                        x-model="categoryForm.color"
                        class="w-16 h-10 border border-gray-300 rounded-lg"
                    />
                    <button
                        type="submit"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors"
                    >
                        Save
                    </button>
                    <button
                        type="button"
                        @click="showNewCategoryForm = false"
                        class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </form>

        {{-- Category Filter Buttons --}}
        <div class="flex flex-wrap gap-3">
            <button
                @click="selectedCategory = 'all'; loadTasks()"
                :class="selectedCategory === 'all' ? 'bg-gray-700 text-white border-gray-700' : 'bg-white text-gray-700 hover:bg-gray-50 border-gray-300'"
                class="px-4 py-3 rounded-lg border-2 transition-colors"
            >
                <div class="flex gap-2" style="align-items: center;">
                    <span class="w-3 h-3 rounded-full bg-gray-700"></span>
                    <div>
                        <div class="font-medium">All Tasks</div>
                    </div>
                </div>
            </button>
            <template x-for="category in categories" :key="category.id">
                <button
                    @click="selectedCategory = category.id; loadTasks()"
                    :class="selectedCategory === category.id ? 'border-2' : 'border-2 border-transparent'"
                    :style="{
                        backgroundColor: selectedCategory === category.id ? category.color + '20' : 'white',
                        borderColor: selectedCategory === category.id ? category.color : '#e5e7eb',
                        color: category.color
                    }"
                    class="px-4 py-3 rounded-lg transition-colors hover:shadow-sm"
                >
                    <div class="flex gap-2" style="align-items: center;">
                        <span class="w-3 h-3 rounded-full" :style="{ backgroundColor: category.color }"></span>
                        <div class="text-left">
                            <div class="font-medium" x-text="category.name"></div>
                            <div x-show="category.description" class="text-xs opacity-75" x-text="category.description"></div>
                        </div>
                    </div>
                </button>
            </template>
            <button
                @click="selectedCategory = 'uncategorized'; loadTasks()"
                :class="selectedCategory === 'uncategorized' ? 'bg-gray-100 text-gray-700 border-gray-400' : 'bg-white text-gray-700 hover:bg-gray-50 border-gray-300'"
                class="px-4 py-3 rounded-lg border-2 transition-colors"
            >
                <div class="flex gap-2" style="align-items: center;">
                    <span class="w-3 h-3 rounded-full bg-gray-400"></span>
                    <div>
                        <div class="font-medium">Uncategorized</div>
                    </div>
                </div>
            </button>
        </div>

        <p class="text-sm text-gray-500 mt-2">
            <span x-text="filteredTasks.length"></span> tasks
        </p>
    </div>

    {{-- Tasks Table --}}
    <div class="bg-white rounded-lg overflow-hidden border border-gray-200">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Task Name
                    </th>
                    <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Task Category
                    </th>
                    <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Description
                    </th>
                    <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Duration (Min)
                    </th>
                    <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Auto-Apply
                    </th>
                    <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <template x-for="task in filteredTasks" :key="task.id">
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="task.name"></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <template x-if="task.category">
                                <span
                                    class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm"
                                    :style="{
                                        backgroundColor: task.category.color + '20',
                                        color: task.category.color
                                    }"
                                >
                                    <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: task.category.color }"></span>
                                    <span x-text="task.category.name"></span>
                                </span>
                            </template>
                            <template x-if="!task.category">
                                <span class="text-gray-400">-</span>
                            </template>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate" x-text="task.description || '-'"></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="task.estimated_duration"></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="task.auto_apply ? 'Yes' : 'No'"></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div class="flex items-center gap-2">
                                <button
                                    @click="editTask(task)"
                                    class="p-1 text-blue-600 hover:bg-blue-50 rounded"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                </button>
                                <button
                                    @click="deleteTask(task.id)"
                                    class="p-1 text-red-600 hover:bg-red-50 rounded"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>
