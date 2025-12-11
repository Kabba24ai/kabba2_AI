@extends('admin.layouts.app')

@section('title', 'Service Master')

@push('css')
<style>
    [x-cloak] {
        display: none !important;
    }
    
    /* Toast Notification Styles */
    .toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 10px;
        pointer-events: none;
    }
    
    .toast {
        min-width: 300px;
        max-width: 500px;
        padding: 16px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        display: flex;
        align-items: flex-start;
        gap: 12px;
        pointer-events: auto;
        animation: slideIn 0.3s ease-out;
        background: white;
    }
    
    .toast.success {
        border-left: 4px solid #10b981;
    }
    
    .toast.error {
        border-left: 4px solid #ef4444;
    }
    
    .toast.warning {
        border-left: 4px solid #f59e0b;
    }
    
    .toast.info {
        border-left: 4px solid #3b82f6;
    }
    
    .toast-icon {
        flex-shrink: 0;
        width: 20px;
        height: 20px;
    }
    
    .toast-icon.success {
        color: #10b981;
    }
    
    .toast-icon.error {
        color: #ef4444;
    }
    
    .toast-icon.warning {
        color: #f59e0b;
    }
    
    .toast-icon.info {
        color: #3b82f6;
    }
    
    .toast-content {
        flex: 1;
    }
    
    .toast-title {
        font-weight: 600;
        font-size: 14px;
        margin-bottom: 4px;
        color: #1f2937;
    }
    
    .toast-message {
        font-size: 13px;
        color: #6b7280;
        line-height: 1.5;
        white-space: pre-line;
    }
    
    .toast-close {
        flex-shrink: 0;
        width: 20px;
        height: 20px;
        cursor: pointer;
        color: #9ca3af;
        transition: color 0.2s;
    }
    
    .toast-close:hover {
        color: #4b5563;
    }
    
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    .toast.fade-out {
        animation: slideOut 0.3s ease-in forwards;
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
</style>
@endpush

@section('content')
<!-- Toast Container -->
<div id="toastContainer" class="toast-container"></div>

@push('js')
<script>
function serviceMaster() {
    return {
        // Tab Management
        activeTab: '{{ $activeTab }}',

        // Service Tasks Data
        tasks: @json($tasks),
        categories: @json($categories),
        selectedCategory: 'all',
        showNewTaskForm: false,
        editingTask: null,
        taskForm: {
            name: '',
            description: '',
            estimated_duration: 0,
            category_id: '',
            auto_apply: false,
            inspection_required: false,
        },

        // Task Categories
        showNewCategoryForm: false,
        categoryForm: {
            name: '',
            description: '',
            color: '#64748b',
        },

        // Templates Data
        templates: @json($templates),
        presets: @json($presets),
        selectedTemplate: null,
        templateTasks: [],
        isCreatingTemplate: false,
        isEditingTemplate: false,
        creationStep: 'name',
        templateForm: {
            name: '',
            description: '',
            preset_id: '',
        },

        // Template Creation
        selectedCategoryIds: [],
        selectedTaskIds: new Set(),
        taskSelections: {},

        // Interval Presets
        isCreatingIntervalPreset: false,
        isEditingPreset: false,
        editingPresetId: null,
        presetForm: {
            name: '',
            description: '',
            intervals: [],
            interval_type: 'hour',
        },
        intervalInput: '',

        // Settings
        settingsForm: {
            pending_before_hours: {{ $settings ? $settings->pending_before_hours : 20 }},
            pending_after_hours: {{ $settings ? $settings->pending_after_hours : 15 }},
            pending_before_dates: {{ $settings ? $settings->pending_before_dates : 20 }},
            pending_after_dates: {{ $settings ? $settings->pending_after_dates : 15 }},
            master_admin_code: '{{ $settings ? $settings->master_admin_code : "" }}',
        },
        isSaving: false,

        // Category Editing in Settings
        isAddingCategory: false,
        newCategoryName: '',
        newCategoryDescription: '',
        newCategoryColor: '#3B82F6',
        editingCategoryId: null,
        editCategoryName: '',
        editCategoryDescription: '',
        editCategoryColor: '',

        // Prevent rapid clicks
        pendingIntervalUpdates: new Map(),

        // Initialize
        init() {
            // sort all data alphabetically for UI
            this.sortAllData();

            if (this.templates.length > 0 && !this.selectedTemplate) {
                this.selectTemplate(this.templates[0]);
            }
        },

        sortAllData() {
            this.sortCategories();
            this.sortTasks();
            this.sortTemplates();
            this.sortPresets();
            this.sortTemplateTasks();
        },

        sortCategories() {
            if (!Array.isArray(this.categories)) return;
            this.categories.sort((a, b) => (a.name || '').toString().toLowerCase().localeCompare((b.name || '').toString().toLowerCase()));
        },

        sortTasks() {
            if (!Array.isArray(this.tasks)) return;
            this.tasks.sort((a, b) => (a.name || '').toString().toLowerCase().localeCompare((b.name || '').toString().toLowerCase()));
        },

        sortTemplates() {
            if (!Array.isArray(this.templates)) return;
            this.templates.sort((a, b) => (a.name || '').toString().toLowerCase().localeCompare((b.name || '').toString().toLowerCase()));
        },

        sortPresets() {
            if (!Array.isArray(this.presets)) return;
            this.presets.sort((a, b) => (a.name || '').toString().toLowerCase().localeCompare((b.name || '').toString().toLowerCase()));
        },

        sortTemplateTasks() {
            if (!Array.isArray(this.templateTasks)) return;
            this.templateTasks.sort((a, b) => {
                const na = (a.task?.name || '').toString().toLowerCase();
                const nb = (b.task?.name || '').toString().toLowerCase();
                return na.localeCompare(nb);
            });
        },

        // Toast Notification
        showToast(message, type = 'info', title = '') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            
            const icons = {
                success: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />',
                error: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />',
                warning: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />',
                info: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />'
            };
            
            const titles = {
                success: title || 'Success',
                error: title || 'Error',
                warning: title || 'Warning',
                info: title || 'Info'
            };
            
            toast.innerHTML = `
                <svg class="toast-icon ${type}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    ${icons[type]}
                </svg>
                <div class="toast-content">
                    <div class="toast-title">${titles[type]}</div>
                    <div class="toast-message">${message}</div>
                </div>
                <svg class="toast-close" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            `;
            
            container.appendChild(toast);
            
            // Close button
            toast.querySelector('.toast-close').addEventListener('click', () => {
                toast.classList.add('fade-out');
                setTimeout(() => toast.remove(), 300);
            });
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.classList.add('fade-out');
                    setTimeout(() => toast.remove(), 300);
                }
            }, 5000);
        },

        // Computed Properties
        get filteredTasks() {
            if (this.selectedCategory === 'all') {
                return this.tasks;
            }
            if (this.selectedCategory === 'uncategorized') {
                return this.tasks.filter(task => !task.category_id);
            }
            return this.tasks.filter(task => task.category_id == this.selectedCategory);
        },

        get creationStepNumber() {
            const steps = { name: 1, interval: 2, tasks: 3, assign: 4 };
            return steps[this.creationStep] || 1;
        },

        get allFilteredTasksSelected() {
            const filtered = this.getFilteredTasksForCreation();
            return filtered.length > 0 && filtered.every(t => this.selectedTaskIds.has(t.id));
        },

        // Load Methods
        async loadTasks() {
            try {
                const response = await fetch('{{ route('admin.maintenance-management.service-master.index') }}?tab=tasks&_ajax=1', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                this.tasks = data.tasks || [];
                this.sortTasks();
            } catch (error) {
                console.error('Error loading tasks:', error);
            }
        },

        async loadCategories() {
            try {
                const response = await fetch('{{ route('admin.maintenance-management.service-master.index') }}?_ajax=1', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                this.categories = data.categories || [];
                this.sortCategories();
            } catch (error) {
                console.error('Error loading categories:', error);
            }
        },

        async loadTemplates() {
            try {
                const response = await fetch('{{ route('admin.maintenance-management.service-master.index') }}?tab=templates&_ajax=1', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                this.templates = data.templates || [];
                this.sortTemplates();
            } catch (error) {
                console.error('Error loading templates:', error);
            }
        },

        async loadPresets() {
            try {
                const response = await fetch('{{ route('admin.maintenance-management.service-master.index') }}?tab=templates&_ajax=1', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                this.presets = data.presets || [];
                this.sortPresets();
            } catch (error) {
                console.error('Error loading presets:', error);
            }
        },

        async loadTemplateTasks(templateId) {
            try {
                const response = await fetch(`/maintenance-management/service-master/templates/${templateId}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    this.templateTasks = data.template.template_tasks || data.template.templateTasks || [];
                    this.sortTemplateTasks();
                }
            } catch (error) {
                console.error('Error loading template tasks:', error);
            }
        },

        // Task CRUD
        resetTaskForm() {
            this.taskForm = {
                name: '',
                description: '',
                estimated_duration: 0,
                category_id: '',
                auto_apply: false,
                inspection_required: false,
            };
        },

        editTask(task) {
            this.editingTask = task;
            this.taskForm = {
                name: task.name,
                description: task.description || '',
                estimated_duration: task.estimated_duration,
                category_id: task.category_id || '',
                auto_apply: task.auto_apply,
                inspection_required: task.inspection_required || false,
            };
            this.showNewTaskForm = true;
        },

        async saveTask() {
            const url = this.editingTask
                ? `/maintenance-management/service-master/tasks/${this.editingTask.id}`
                : '/maintenance-management/service-master/tasks';
            
            const method = this.editingTask ? 'PUT' : 'POST';

            // Prepare data - convert empty category_id to null
            const taskData = {
                ...this.taskForm,
                category_id: this.taskForm.category_id || null
            };

            try {
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(taskData),
                });

                const data = await response.json();
                if (data.success) {
                    this.showNewTaskForm = false;
                    this.editingTask = null;
                    this.resetTaskForm();
                    await this.loadTasks();
                    await this.loadCategories();
                }
            } catch (error) {
                console.error('Error saving task:', error);
                this.showToast('Error saving task', 'error');
            }
        },

        async deleteTask(taskId) {
            if (!confirm('Are you sure you want to delete this task?')) return;

            try {
                const response = await fetch(`/maintenance-management/service-master/tasks/${taskId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });

                const data = await response.json();
                if (data.success) {
                    await this.loadTasks();
                    this.showToast('Task deleted successfully', 'success');
                }
            } catch (error) {
                console.error('Error deleting task:', error);
                this.showToast('Error deleting task', 'error');
            }
        },

        // Category CRUD (in Tasks tab)
        async saveCategory() {
            try {
                // Validate before sending
                if (!this.categoryForm.name.trim()) {
                    this.showToast('Category name is required', 'error');
                    return;
                }
                if (!this.categoryForm.color) {
                    this.showToast('Category color is required', 'error');
                    return;
                }

                const response = await fetch('{{ route("admin.maintenance-management.service-master.categories.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.categoryForm),
                });

                const data = await response.json();
                console.log('Save category response:', data);
                
                if (!response.ok) {
                    console.error('HTTP Error:', response.status, data);
                    this.showToast(data.message || 'Failed to save category', 'error');
                    return;
                }

                if (data.success) {
                    this.showNewCategoryForm = false;
                    this.categoryForm = {
                        name: '',
                        description: '',
                        color: '#64748b',
                    };
                    await this.loadCategories();
                    this.showToast('Category saved successfully!', 'success');
                } else {
                    this.showToast(data.message || 'Failed to save category', 'error');
                }
            } catch (error) {
                console.error('Error saving category:', error);
                this.showToast('Error saving category: ' + error.message, 'error');
            }
        },

        // Settings Category Management
        startEditCategory(category) {
            this.editingCategoryId = category.id;
            this.editCategoryName = category.name;
            this.editCategoryDescription = category.description || '';
            this.editCategoryColor = category.color;
        },

        cancelEditCategory() {
            this.editingCategoryId = null;
            this.editCategoryName = '';
            this.editCategoryDescription = '';
            this.editCategoryColor = '';
        },

        async saveEditCategory(categoryId) {
            try {
                const baseUrl = '{{ route("admin.maintenance-management.service-master.categories.update", ["id" => "PLACEHOLDER"]) }}'.replace('PLACEHOLDER', categoryId);
                const response = await fetch(baseUrl, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        name: this.editCategoryName,
                        description: this.editCategoryDescription,
                        color: this.editCategoryColor,
                    }),
                });

                const data = await response.json();
                if (data.success) {
                    this.cancelEditCategory();
                    await this.loadCategories();
                    this.showToast('Category updated successfully', 'success');
                }
            } catch (error) {
                console.error('Error updating category:', error);
                this.showToast('Error updating category', 'error');
            }
        },

        async addCategory() {
            try {
                const response = await fetch('{{ route("admin.maintenance-management.service-master.categories.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        name: this.newCategoryName,
                        description: this.newCategoryDescription,
                        color: this.newCategoryColor,
                    }),
                });

                const data = await response.json();
                if (data.success) {
                    this.isAddingCategory = false;
                    this.newCategoryName = '';
                    this.newCategoryDescription = '';
                    this.newCategoryColor = '#3B82F6';
                    await this.loadCategories();
                    this.showToast('Category added successfully', 'success');
                }
            } catch (error) {
                console.error('Error adding category:', error);
                this.showToast('Error adding category', 'error');
            }
        },

        async deleteCategory(categoryId) {
            if (!confirm('Are you sure you want to delete this category? Tasks in this category will become uncategorized.')) {
                return;
            }

            try {
                const baseUrl = '{{ route("admin.maintenance-management.service-master.categories.destroy", ["id" => "PLACEHOLDER"]) }}'.replace('PLACEHOLDER', categoryId);
                const response = await fetch(baseUrl, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });

                const data = await response.json();
                if (data.success) {
                    await this.loadCategories();
                    this.showToast('Category deleted successfully!', 'success');
                } else {
                    this.showToast(data.message || 'Failed to delete category', 'error');
                }
            } catch (error) {
                console.error('Error deleting category:', error);
                this.showToast('Error deleting category: ' + error.message, 'error');
            }
        },

        // Template Creation Wizard
        startTemplateCreation() {
            this.isCreatingTemplate = true;
            this.creationStep = 'name';
            this.templateForm = {
                name: '',
                description: '',
                preset_id: '',
            };
            this.selectedCategoryIds = [];
            this.selectedTaskIds = new Set();
            this.taskSelections = {};
        },

        cancelTemplateCreation() {
            this.isCreatingTemplate = false;
            this.creationStep = 'name';
            this.selectedCategoryIds = [];
            this.selectedTaskIds = new Set();
            this.taskSelections = {};
        },

        nextCreationStep() {
            if (this.creationStep === 'name') {
                this.creationStep = 'interval';
            } else if (this.creationStep === 'interval') {
                this.creationStep = 'tasks';
            } else if (this.creationStep === 'tasks') {
                // Initialize task selections with auto-apply
                const intervals = this.getSelectedPresetIntervals();
                const newTaskSelections = {};

                Array.from(this.selectedTaskIds).forEach(taskId => {
                    const task = this.tasks.find(t => t.id == taskId);
                    if (task?.auto_apply) {
                        newTaskSelections[taskId] = [...intervals];
                    } else {
                        newTaskSelections[taskId] = [];
                    }
                });

                this.taskSelections = newTaskSelections;
                this.creationStep = 'assign';
            }
        },

        previousCreationStep() {
            if (this.creationStep === 'assign') {
                this.creationStep = 'tasks';
            } else if (this.creationStep === 'tasks') {
                this.creationStep = 'interval';
            } else if (this.creationStep === 'interval') {
                this.creationStep = 'name';
            }
        },

        getSelectedPresetIntervals() {
            const preset = this.presets.find(p => p.id == this.templateForm.preset_id);
            return preset?.intervals || [];
        },

        getFilteredTasksForCreation() {
            if (this.selectedCategoryIds.length === 0) return this.tasks;

            return this.tasks.filter(task => {
                if (this.selectedCategoryIds.includes('uncategorized') && !task.category_id) {
                    return true;
                }
                return task.category_id && this.selectedCategoryIds.includes(task.category_id.toString());
            });
        },

        getSelectedTasks() {
            return this.tasks.filter(task => this.selectedTaskIds.has(task.id));
        },

        toggleCategoryFilter(categoryId) {
            const index = this.selectedCategoryIds.indexOf(categoryId.toString());
            if (index > -1) {
                this.selectedCategoryIds.splice(index, 1);
                // Deselect tasks in this category
                const tasksInCategory = this.tasks.filter(t => 
                    categoryId === 'uncategorized' ? !t.category_id : t.category_id == categoryId
                );
                tasksInCategory.forEach(t => this.selectedTaskIds.delete(t.id));
            } else {
                this.selectedCategoryIds.push(categoryId.toString());
                // Auto-select tasks in this category
                const tasksInCategory = this.tasks.filter(t => 
                    categoryId === 'uncategorized' ? !t.category_id : t.category_id == categoryId
                );
                tasksInCategory.forEach(t => this.selectedTaskIds.add(t.id));
            }
        },

        toggleAllFilteredTasks(checked) {
            const filtered = this.getFilteredTasksForCreation();
            if (checked) {
                filtered.forEach(t => this.selectedTaskIds.add(t.id));
            } else {
                filtered.forEach(t => this.selectedTaskIds.delete(t.id));
            }
            this.selectedTaskIds = new Set(this.selectedTaskIds);
        },

        toggleTaskSelection(taskId) {
            if (this.selectedTaskIds.has(taskId)) {
                this.selectedTaskIds.delete(taskId);
            } else {
                this.selectedTaskIds.add(taskId);
            }
            this.selectedTaskIds = new Set(this.selectedTaskIds);
        },

        toggleIntervalInCreation(taskId, interval) {
            if (!this.taskSelections[taskId]) {
                this.taskSelections[taskId] = [];
            }

            const intervals = this.taskSelections[taskId];
            const index = intervals.indexOf(interval);

            if (index > -1) {
                intervals.splice(index, 1);
            } else {
                intervals.push(interval);
                intervals.sort((a, b) => a - b);
            }

            if (intervals.length === 0) {
                delete this.taskSelections[taskId];
            }
        },

        async saveTemplate() {
            const tasksData = Object.entries(this.taskSelections)
                .filter(([taskId, intervals]) => this.selectedTaskIds.has(parseInt(taskId)) && intervals.length > 0)
                .map(([taskId, intervals]) => ({
                    task_id: taskId,
                    intervals: intervals,
                }));

            try {
                const response = await fetch('{{ route("admin.maintenance-management.service-master.templates.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        name: this.templateForm.name,
                        description: this.templateForm.description,
                        preset_id: this.templateForm.preset_id,
                        tasks: tasksData,
                    }),
                });

                const data = await response.json();
                if (data.success) {
                    this.cancelTemplateCreation();
                    await this.loadTemplates();
                    this.showToast('Template created successfully', 'success');
                }
            } catch (error) {
                console.error('Error saving template:', error);
                this.showToast('Error saving template', 'error');
            }
        },

        async updateTemplateInfo() {
            if (!this.selectedTemplate) return;

            try {
                const response = await fetch(`/maintenance-management/service-master/templates/${this.selectedTemplate.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        name: this.selectedTemplate.name,
                        description: this.selectedTemplate.description,
                        preset_id: this.selectedTemplate.preset_id,
                    }),
                });

                const data = await response.json();
                if (data.success) {
                    await this.loadTemplates();
                    this.showToast('Template updated successfully', 'success');
                } else {
                    this.showToast('Failed to update template', 'error');
                }
            } catch (error) {
                console.error('Error updating template:', error);
                this.showToast('Error updating template', 'error');
            }
        },

        async deleteTemplate(templateId) {
            if (!confirm('Are you sure you want to delete this template?')) return;

            try {
                const response = await fetch(`/maintenance-management/service-master/templates/${templateId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });

                const data = await response.json();
                if (data.success) {
                    if (this.selectedTemplate?.id == templateId) {
                        this.selectedTemplate = null;
                    }
                    await this.loadTemplates();
                    this.showToast('Template deleted successfully', 'success');
                }
            } catch (error) {
                console.error('Error deleting template:', error);
                this.showToast('Error deleting template', 'error');
            }
        },

        async cloneTemplate(templateId) {
            try {
                // Get the template details
                const response = await fetch(`/maintenance-management/service-master/templates/${templateId}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });

                const data = await response.json();
                if (!data.success) {
                    this.showToast('Failed to load template', 'error');
                    return;
                }

                const template = data.template;
                
                // Create the tasks data from template tasks
                const tasksData = (template.template_tasks || template.templateTasks || []).map(tt => ({
                    task_id: tt.task_id,
                    intervals: tt.intervals || [],
                }));

                // Create the cloned template
                const cloneResponse = await fetch('{{ route("admin.maintenance-management.service-master.templates.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        name: template.name + ' (Copy)',
                        description: template.description,
                        preset_id: template.preset_id,
                        tasks: tasksData,
                    }),
                });

                const cloneData = await cloneResponse.json();
                if (cloneData.success) {
                    await this.loadTemplates();
                    this.selectTemplate(cloneData.template);
                    this.showToast('Template cloned successfully', 'success');
                } else {
                    this.showToast('Failed to clone template', 'error');
                }
            } catch (error) {
                console.error('Error cloning template:', error);
                this.showToast('Error cloning template', 'error');
            }
        },

        async selectTemplate(template) {
            this.selectedTemplate = template;
            await this.loadTemplateTasks(template.id);
        },

        getFilteredTemplateTasks() {
            if (this.selectedCategoryIds.length === 0) return this.templateTasks;

            return this.templateTasks.filter(tt => {
                if (this.selectedCategoryIds.includes('uncategorized') && !tt.task?.category_id) {
                    return true;
                }
                return tt.task?.category_id && this.selectedCategoryIds.includes(tt.task.category_id.toString());
            });
        },

        getTasksNotInTemplate() {
            const templateTaskIds = this.templateTasks.map(tt => tt.task_id);
            let availableTasks = this.tasks.filter(task => !templateTaskIds.includes(task.id));

            if (this.selectedCategoryIds.length > 0) {
                availableTasks = availableTasks.filter(task => {
                    if (this.selectedCategoryIds.includes('uncategorized') && !task.category_id) {
                        return true;
                    }
                    return task.category_id && this.selectedCategoryIds.includes(task.category_id.toString());
                });
            }

            return availableTasks.sort((a, b) => a.name.localeCompare(b.name));
        },

        async addTaskToTemplate(taskId) {
            if (!this.selectedTemplate) return;

            const task = this.tasks.find(t => t.id == taskId);
            const intervals = task?.auto_apply ? (this.selectedTemplate.preset?.intervals || []) : [];

            try {
                const response = await fetch(`/maintenance-management/service-master/templates/${this.selectedTemplate.id}/tasks`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        task_id: taskId,
                        intervals: intervals,
                    }),
                });

                const data = await response.json();
                if (data.success) {
                    await this.loadTemplateTasks(this.selectedTemplate.id);
                    this.showToast('Task added to template', 'success');
                }
            } catch (error) {
                console.error('Error adding task to template:', error);
                this.showToast('Error adding task to template', 'error');
            }
        },

        async toggleTemplateTaskInterval(templateTask, interval) {
            // Optimistically update UI immediately
            const currentIntervals = templateTask.intervals || [];
            const newIntervals = currentIntervals.includes(interval)
                ? currentIntervals.filter(i => i !== interval)
                : [...currentIntervals, interval].sort((a, b) => a - b);

            // Update UI immediately for better UX
            templateTask.intervals = newIntervals;

            // Create a unique key for this template task
            const updateKey = `${templateTask.id}`;
            
            // Cancel any pending update for this template task
            if (this.pendingIntervalUpdates.has(updateKey)) {
                clearTimeout(this.pendingIntervalUpdates.get(updateKey));
            }

            // Debounce the API call
            const timeoutId = setTimeout(async () => {
                try {
                    if (newIntervals.length === 0) {
                        await this.removeTemplateTask(templateTask.id);
                    } else {
                        const response = await fetch(`/maintenance-management/service-master/template-tasks/${templateTask.id}/intervals`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                intervals: newIntervals,
                            }),
                        });

                        const data = await response.json();
                        if (!data.success) {
                            // Revert on failure
                            await this.loadTemplateTasks(this.selectedTemplate.id);
                            this.showToast('Error updating intervals', 'error');
                        }
                    }
                } catch (error) {
                    console.error('Error updating intervals:', error);
                    // Revert on error
                    await this.loadTemplateTasks(this.selectedTemplate.id);
                    this.showToast('Error updating intervals', 'error');
                } finally {
                    this.pendingIntervalUpdates.delete(updateKey);
                }
            }, 300); // Wait 300ms after last click

            this.pendingIntervalUpdates.set(updateKey, timeoutId);
        },

        async removeTemplateTask(templateTaskId) {
            try {
                const response = await fetch(`/maintenance-management/service-master/template-tasks/${templateTaskId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });

                const data = await response.json();
                if (data.success) {
                    await this.loadTemplateTasks(this.selectedTemplate.id);
                    this.showToast('Task removed from template', 'success');
                }
            } catch (error) {
                console.error('Error removing task:', error);
                this.showToast('Error removing task', 'error');
            }
        },

        // Interval Presets
        resetPresetForm() {
            this.presetForm = {
                name: '',
                description: '',
                intervals: [],
                interval_type: 'hour',
            };
            this.intervalInput = '';
            this.isEditingPreset = false;
            this.editingPresetId = null;
        },

        addIntervals() {
            if (!this.intervalInput) return;

            const values = this.intervalInput
                .split(',')
                .map(v => parseInt(v.trim()))
                .filter(v => !isNaN(v) && v > 0);

            const uniqueValues = [...new Set([...this.presetForm.intervals, ...values])].sort((a, b) => a - b);
            this.presetForm.intervals = uniqueValues;
            this.intervalInput = '';
        },

        removeInterval(index) {
            this.presetForm.intervals.splice(index, 1);
        },

        editPreset(preset) {
            this.isEditingPreset = true;
            this.editingPresetId = preset.id;
            this.presetForm = {
                name: preset.name,
                description: preset.description || '',
                intervals: [...preset.intervals],
                interval_type: preset.interval_type || 'hour',
            };
            this.isCreatingIntervalPreset = true;
        },

        async clonePreset(preset) {
            try {
                const response = await fetch('{{ route("admin.maintenance-management.service-master.presets.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        name: preset.name + ' (Copy)',
                        description: preset.description || '',
                        intervals: [...preset.intervals],
                        interval_type: preset.interval_type || 'hour'
                    })
                });

                if (!response.ok) throw new Error('Failed to clone preset');
                
                const data = await response.json();
                this.presets.push(data.preset);
                this.presets.sort((a, b) => a.name.localeCompare(b.name));
                this.showToast('Interval template cloned successfully', 'success');
            } catch (error) {
                console.error('Error:', error);
                this.showToast('Failed to clone interval template', 'error');
            }
        },

        async savePreset() {
            try {
                const url = this.isEditingPreset 
                    ? `/maintenance-management/service-master/presets/${this.editingPresetId}`
                    : '{{ route("admin.maintenance-management.service-master.presets.store") }}';
                
                const method = this.isEditingPreset ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.presetForm),
                });

                const data = await response.json();
                if (data.success) {
                    await this.loadPresets();
                    if (!this.isEditingPreset) {
                        this.templateForm.preset_id = data.preset.id;
                    }
                    this.isCreatingIntervalPreset = false;
                    this.resetPresetForm();
                    this.showToast(
                        this.isEditingPreset ? 'Interval template updated successfully' : 'Interval template created successfully', 
                        'success'
                    );
                }
            } catch (error) {
                console.error('Error saving preset:', error);
                this.showToast('Error saving preset', 'error');
            }
        },

        async deletePreset(presetId) {
            // Check if any templates are using this preset
            const templatesUsingPreset = this.templates.filter(t => t.preset_id == presetId);
            
            if (templatesUsingPreset.length > 0) {
                const templateNames = templatesUsingPreset.map(t => t.name).join(', ');
                this.showToast(
                    `This interval template is currently being used by the following template(s):\n${templateNames}\n\nPlease remove or reassign these templates first.`,
                    'error',
                    'Cannot Delete'
                );
                return;
            }

            if (!confirm('Are you sure you want to delete this interval template?')) return;

            try {
                const response = await fetch(`/maintenance-management/service-master/presets/${presetId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });

                const data = await response.json();
                if (data.success) {
                    await this.loadPresets();
                    if (this.templateForm.preset_id == presetId) {
                        this.templateForm.preset_id = '';
                    }
                    this.showToast('Interval template deleted successfully', 'success');
                } else if (data.message) {
                    this.showToast(data.message, 'error');
                }
            } catch (error) {
                console.error('Error deleting preset:', error);
                this.showToast('Failed to delete interval template', 'error');
            }
        },

        // Settings
        async saveSettings() {
            this.isSaving = true;

            try {
                const response = await fetch('{{ route("admin.maintenance-management.service-master.settings.update") }}', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.settingsForm),
                });

                const data = await response.json();
                if (data.success) {
                    this.showToast('Settings saved successfully', 'success');
                }
            } catch (error) {
                console.error('Error saving settings:', error);
                this.showToast('Error saving settings', 'error');
            } finally {
                this.isSaving = false;
            }
        },
    };
}
</script>
@endpush

<div class="h-screen bg-gray-50 flex flex-col overflow-hidden" x-data="serviceMaster()" x-init="init()">
    <div class="flex-1 overflow-auto p-6">
        {{-- Header --}}
        <div class="flex items-center gap-3">
            <!-- Service Master icon -->
            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="oklch(54.6% .245 262.881)" aria-hidden="true" data-slot="icon">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z"></path>
            </svg>
            <h1 class="text-3xl font-bold text-gray-900">Service Master</h1>

                
        </div>
        <div class="mb-6">
            <p class="text-gray-600 mt-1">Manage equipment service schedules, templates, and maintenance tracking</p>
        </div>

        {{-- Tabs --}}
        <div class="border-b border-gray-200 mb-6">
            <div class="flex gap-6">
                <button
                    @click="activeTab = 'tasks'"
                    :class="activeTab === 'tasks' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-600 hover:text-gray-900'"
                    class="flex items-center gap-2 pb-3 border-b-2 transition-colors"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-wrench w-4 h-4"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
                    <span class="font-medium">Service Tasks</span>
                </button>
                <button
                    @click="activeTab = 'templates'"
                    :class="activeTab === 'templates' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-600 hover:text-gray-900'"
                    class="flex items-center gap-2 pb-3 border-b-2 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span class="font-medium">Service Templates</span>
                </button>

                <button
                    @click="activeTab = 'intervals'"
                    :class="activeTab === 'intervals' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-600 hover:text-gray-900'"
                    class="flex items-center gap-2 pb-3 border-b-2 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="font-medium">Interval Templates</span>
                </button>
                
                <button
                    @click="activeTab = 'settings'"
                    :class="activeTab === 'settings' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-600 hover:text-gray-900'"
                    class="flex items-center gap-2 pb-3 border-b-2 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="font-medium">Settings</span>
                </button>
            </div>
        </div>

        {{-- Tab Content --}}
        <div x-show="activeTab === 'tasks'" x-cloak>
            @include('admin.maintenance_management.service_master.partials._tasks_tab')
        </div>

        <div x-show="activeTab === 'templates'" x-cloak>
            @include('admin.maintenance_management.service_master.partials._templates_tab')
        </div>

        <div x-show="activeTab === 'intervals'" x-cloak>
            @include('admin.maintenance_management.service_master.partials._intervals_tab')
        </div>

        <div x-show="activeTab === 'settings'" x-cloak>
            @include('admin.maintenance_management.service_master.partials._settings_tab')
        </div>
    </div>
</div>
@endsection
