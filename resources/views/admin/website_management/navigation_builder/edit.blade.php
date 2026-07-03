@extends('admin.layouts.app')

@section('title', $menu->name . ' — Navigation Builder')

@section('content')

<div class="px-4 sm:px-6 lg:px-8 py-6"
     x-data="navBuilder()"
     x-init="init()">

    {{-- ── Page Header ──────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-md p-4 shadow-sm border border-gray-100 mb-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.website-management.navigation-builder.index') }}"
               class="text-gray-400 hover:text-gray-600 transition-colors shrink-0">
                <x-heroicon-o-arrow-left class="w-5 h-5"/>
            </a>
            <div>
                <h1 class="text-lg font-semibold text-gray-900">{{ $menu->name }}</h1>
                <p class="text-xs text-gray-400 font-mono">key: <span class="text-gray-600">{{ $menu->menu_key }}</span></p>
            </div>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                {{ $menu->status === 'Active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                {{ $menu->status }}
            </span>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            {{-- Sort status --}}
            <span x-show="isSorting" class="text-xs text-gray-400 flex items-center gap-1">
                <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                Saving order…
            </span>
            <span x-show="sortMsg" x-text="sortMsg" class="text-xs text-green-600"></span>

            <button @click="showSettingsModal = true"
                    class="inline-flex items-center gap-1.5 border border-gray-200 hover:bg-gray-50 text-gray-700 px-3 py-1.5 rounded-md text-sm transition-colors">
                <x-heroicon-o-cog-6-tooth class="w-4 h-4"/>
                Settings
            </button>
            <button @click="openAdd(null)"
                    class="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-md text-sm font-medium transition-colors">
                <x-heroicon-o-plus class="w-4 h-4"/>
                Add Item
            </button>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-md px-4 py-3 text-sm mb-5 flex items-center gap-2">
        <x-heroicon-o-check-circle class="w-4 h-4 shrink-0"/>
        {{ session('success') }}
    </div>
    @endif

    {{-- ── Tree Container ───────────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden" id="nav-tree">
        @include('admin.website_management.navigation_builder.partials._tree', [
            'items' => $tree,
            'depth' => 0,
        ])
    </div>

    <p class="text-xs text-gray-400 mt-3 flex items-center gap-1.5">
        <x-heroicon-o-arrows-up-down class="w-3.5 h-3.5"/>
        Drag items to reorder. Drag into a parent item to nest. Order saves automatically.
    </p>

    {{-- ── Add / Edit Item Slide-Over ───────────────────────────────────── --}}
    <div x-show="showPanel"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-x-4"
         x-transition:enter-end="opacity-100 translate-x-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-x-0"
         x-transition:leave-end="opacity-0 translate-x-4"
         @keydown.escape.window="showPanel = false"
         class="fixed right-0 top-0 bottom-0 w-96 bg-white border-l border-gray-200 shadow-xl z-30 flex flex-col overflow-hidden">

        {{-- Panel Header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 shrink-0">
            <h3 class="text-sm font-semibold text-gray-900"
                x-text="panelMode === 'add' ? (parentUniqueId ? 'Add Child Item' : 'Add Menu Item') : 'Edit Item'">
            </h3>
            <button @click="showPanel = false" class="text-gray-400 hover:text-gray-600">
                <x-heroicon-o-x-mark class="w-5 h-5"/>
            </button>
        </div>

        {{-- Panel Body --}}
        <div class="flex-1 overflow-y-auto p-5 space-y-4">

            {{-- Title --}}
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Title <span class="text-red-500">*</span></label>
                <input type="text" x-model="form.title" placeholder="e.g. About Us"
                       class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400"
                       x-ref="titleInput">
            </div>

            {{-- Type --}}
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Link Type</label>
                <div class="grid grid-cols-3 gap-1.5">
                    <template x-for="t in linkTypes" :key="t.value">
                        <button type="button"
                                @click="form.type = t.value; form.url = ''; form.page_id = ''"
                                :class="form.type === t.value
                                    ? 'bg-blue-600 text-white border-blue-600'
                                    : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'"
                                class="border rounded-md px-2 py-1.5 text-xs font-medium transition-colors"
                                x-text="t.label">
                        </button>
                    </template>
                </div>
            </div>

            {{-- Internal Page Picker --}}
            <div x-show="form.type === 'internal_page'">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Select Page</label>
                <select x-model="form.page_id"
                        class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400 bg-white">
                    <option value="">— Choose a page —</option>
                    <template x-for="page in pages" :key="page.id">
                        <option :value="page.id" x-text="page.title + ' (/' + page.slug + ')'"></option>
                    </template>
                </select>
                <template x-if="form.page_id">
                    <p class="text-xs text-gray-400 mt-1">
                        URL: <span class="font-mono"
                            x-text="'/' + (pages.find(p => p.id == form.page_id)?.slug || '')"></span>
                    </p>
                </template>
            </div>

            {{-- URL field --}}
            <div x-show="form.type !== 'internal_page'">
                <label class="block text-xs font-semibold text-gray-600 mb-1">
                    <span x-text="urlFieldLabel"></span>
                </label>
                <input type="text" x-model="form.url"
                       :placeholder="urlFieldPlaceholder"
                       class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400 font-mono">
                <p class="text-xs text-gray-400 mt-1" x-text="urlFieldHint"></p>
            </div>

            {{-- Icon --}}
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Icon <span class="text-gray-400 font-normal">(optional)</span></label>
                <input type="text" x-model="form.icon"
                       placeholder="heroicon-o-home or fa fa-home"
                       class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400 font-mono">
                <p class="text-xs text-gray-400 mt-1">Heroicon name (e.g. <code>heroicon-o-home</code>) or Font Awesome class.</p>
            </div>

            {{-- Visibility + Target row --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Visibility</label>
                    <select x-model="form.visibility"
                            class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400 bg-white">
                        <option value="both">Both</option>
                        <option value="desktop_only">Desktop Only</option>
                        <option value="mobile_only">Mobile Only</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Target</label>
                    <select x-model="form.target"
                            class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400 bg-white">
                        <option value="_self">Same Tab</option>
                        <option value="_blank">New Tab</option>
                    </select>
                </div>
            </div>

            {{-- CSS Class --}}
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">CSS Class <span class="text-gray-400 font-normal">(optional)</span></label>
                <input type="text" x-model="form.css_class"
                       placeholder="e.g. btn-cta highlight"
                       class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400 font-mono">
            </div>

            {{-- Rel --}}
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Rel <span class="text-gray-400 font-normal">(optional)</span></label>
                <input type="text" x-model="form.rel"
                       placeholder="noopener noreferrer"
                       class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400 font-mono">
            </div>

            {{-- Status --}}
            <div class="flex items-center justify-between pt-1 border-t border-gray-100">
                <label class="text-xs font-semibold text-gray-600">Status</label>
                <div class="flex items-center gap-2 cursor-pointer select-none"
                     @click="form.status = form.status === 'Active' ? 'Inactive' : 'Active'">
                    <button type="button"
                            :class="form.status === 'Active' ? 'bg-green-500' : 'bg-gray-300'"
                            class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors focus:outline-none">
                        <span :class="form.status === 'Active' ? 'translate-x-4' : 'translate-x-0.5'"
                              class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-transform"></span>
                    </button>
                    <span class="text-xs font-medium w-14"
                          :class="form.status === 'Active' ? 'text-green-600' : 'text-gray-400'"
                          x-text="form.status"></span>
                </div>
            </div>

            {{-- Error --}}
            <template x-if="saveError">
                <p class="text-xs text-red-600 bg-red-50 border border-red-100 rounded-md px-3 py-2" x-text="saveError"></p>
            </template>

        </div>

        {{-- Panel Footer --}}
        <div class="border-t border-gray-100 px-5 py-4 flex items-center gap-3 shrink-0 bg-gray-50">
            <button @click="showPanel = false"
                    class="flex-1 py-2 text-sm text-gray-600 bg-white border border-gray-200 rounded-md hover:bg-gray-50 transition-colors">
                Cancel
            </button>
            <button @click="saveItem()"
                    :disabled="isSaving || !form.title"
                    class="flex-1 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-text="isSaving ? 'Saving…' : (panelMode === 'add' ? 'Add Item' : 'Save Changes')"></span>
            </button>
        </div>
    </div>

    {{-- Slide-over backdrop --}}
    <div x-show="showPanel" @click="showPanel = false"
         class="fixed inset-0 z-20 bg-transparent"></div>

    {{-- ── Settings Modal ────────────────────────────────────────────────── --}}
    <div x-show="showSettingsModal"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" @click="showSettingsModal = false"></div>
        <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md p-6">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-base font-semibold text-gray-900">Menu Settings</h3>
                <button @click="showSettingsModal = false" class="text-gray-400 hover:text-gray-600">
                    <x-heroicon-o-x-mark class="w-5 h-5"/>
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Menu Name <span class="text-red-500">*</span></label>
                    <input type="text" x-model="settings.name"
                           class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Menu Key <span class="text-red-500">*</span></label>
                    <input type="text" x-model="settings.menu_key"
                           class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400 font-mono"
                           pattern="[a-z0-9_]+">
                    <p class="text-xs text-gray-400 mt-1">Lowercase, numbers, underscores only.</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Description</label>
                    <textarea x-model="settings.description" rows="2"
                              class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400 resize-none"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                        <select x-model="settings.status"
                                class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400 bg-white">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Display Order</label>
                        <input type="number" x-model="settings.display_order" min="0"
                               class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-400">
                    </div>
                </div>

                <template x-if="settingsError">
                    <p class="text-xs text-red-600 bg-red-50 border border-red-100 rounded-md px-3 py-2" x-text="settingsError"></p>
                </template>
                <template x-if="settingsSuccess">
                    <p class="text-xs text-green-700 bg-green-50 border border-green-100 rounded-md px-3 py-2" x-text="settingsSuccess"></p>
                </template>
            </div>

            <div class="flex items-center gap-3 mt-5">
                <button @click="showSettingsModal = false"
                        class="flex-1 py-2 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors">
                    Cancel
                </button>
                <button @click="saveSettings()"
                        :disabled="isSavingSettings"
                        class="flex-1 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors disabled:opacity-50">
                    <span x-text="isSavingSettings ? 'Saving…' : 'Save Settings'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Delete confirmation modal --}}
    <div x-show="deleteConfirm.show"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" @click="deleteConfirm.show = false"></div>
        <div class="relative bg-white rounded-xl shadow-xl w-full max-w-sm p-6 text-center">
            <x-heroicon-o-exclamation-triangle class="w-10 h-10 text-red-500 mx-auto mb-3"/>
            <h3 class="font-semibold text-gray-900 mb-1">Delete item?</h3>
            <p class="text-sm text-gray-500 mb-5">
                "<span x-text="deleteConfirm.title" class="font-medium text-gray-700"></span>" will be deleted.
                Children will be promoted to the parent level.
            </p>
            <div class="flex gap-3">
                <button @click="deleteConfirm.show = false"
                        class="flex-1 py-2 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-md">
                    Cancel
                </button>
                <button @click="confirmDelete()"
                        :disabled="isDeleting"
                        class="flex-1 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md disabled:opacity-50">
                    <span x-text="isDeleting ? 'Deleting…' : 'Delete'"></span>
                </button>
            </div>
        </div>
    </div>

</div>

{{-- ── JavaScript ────────────────────────────────────────────────────────── --}}
<script>
function navBuilder() {
    return {
        // ── Config (injected from Blade) ─────────────────────────────────
        menuUniqueId: '{{ $menu->unique_id }}',
        csrfToken:    '{{ csrf_token() }}',
        urls: {
            tree:      '{{ route("admin.website-management.navigation-builder.tree", $menu->unique_id) }}',
            itemStore: '{{ route("admin.website-management.navigation-builder.item.store") }}',
            itemSort:  '{{ route("admin.website-management.navigation-builder.item.sort") }}',
            itemUpdate:    '{{ route("admin.website-management.navigation-builder.item.update", "__ID__") }}',
            itemDelete:    '{{ route("admin.website-management.navigation-builder.item.delete", "__ID__") }}',
            itemDuplicate: '{{ route("admin.website-management.navigation-builder.item.duplicate", "__ID__") }}',
            itemToggle:    '{{ route("admin.website-management.navigation-builder.item.toggle", "__ID__") }}',
            menuUpdate:    '{{ route("admin.website-management.navigation-builder.update", $menu->unique_id) }}',
        },
        pages: @json($pages),

        // ── UI state ────────────────────────────────────────────────────
        showPanel:  false,
        panelMode:  'add',
        editingUniqueId: null,
        parentUniqueId:  null,

        showSettingsModal: false,
        isSorting:  false,
        sortMsg:    '',

        // ── Form ────────────────────────────────────────────────────────
        form: {
            title: '', type: 'external_url', page_id: '', url: '',
            icon: '', css_class: '', target: '_self', rel: '',
            visibility: 'both', status: 'Active',
        },
        isSaving:  false,
        saveError: '',

        // ── Settings form ────────────────────────────────────────────────
        settings: {
            name:          '{{ addslashes($menu->name) }}',
            menu_key:      '{{ $menu->menu_key }}',
            description:   '{{ addslashes($menu->description ?? "") }}',
            status:        '{{ $menu->status }}',
            display_order: {{ $menu->display_order }},
        },
        isSavingSettings: false,
        settingsError:    '',
        settingsSuccess:  '',

        // ── Delete state ─────────────────────────────────────────────────
        deleteConfirm: { show: false, uniqueId: null, title: '' },
        isDeleting: false,

        // ── Link type helpers ────────────────────────────────────────────
        linkTypes: [
            { value: 'external_url',  label: 'URL' },
            { value: 'internal_page', label: 'Page' },
            { value: 'anchor',        label: 'Anchor' },
            { value: 'email',         label: 'Email' },
            { value: 'phone',         label: 'Phone' },
        ],

        get urlFieldLabel() {
            const m = { external_url: 'URL', anchor: 'Anchor', email: 'Email Address', phone: 'Phone Number' };
            return m[this.form.type] || 'URL';
        },
        get urlFieldPlaceholder() {
            const m = {
                external_url: 'https://example.com',
                anchor:       '#section-id',
                email:        'hello@example.com',
                phone:        '+1 555 000 0000',
            };
            return m[this.form.type] || '';
        },
        get urlFieldHint() {
            const m = {
                anchor:  'Use # followed by the element ID on the page.',
                email:   'Will be prefixed with mailto: automatically.',
                phone:   'Will be prefixed with tel: automatically.',
            };
            return m[this.form.type] || '';
        },

        // ── Init ────────────────────────────────────────────────────────
        init() {
            window.navBuilderApp = this;
            this.$nextTick(() => this.initSortables());

            // Event delegation on the tree container
            document.getElementById('nav-tree').addEventListener('click', (e) => {
                const btn  = e.target.closest('[data-action]');
                if (!btn) return;
                const li     = btn.closest('.tree-item');
                const action = btn.dataset.action;
                const id     = li?.dataset.id;
                const data   = JSON.parse(li?.querySelector('.item-data')?.textContent || '{}');

                if      (action === 'edit')      this.openEdit(data);
                else if (action === 'add-child')  this.openAdd(id);
                else if (action === 'duplicate')  this.duplicateItem(id);
                else if (action === 'toggle')     this.toggleItem(id);
                else if (action === 'delete')     this.requestDelete(id, data.title);
            });
        },

        // ── Sortable ─────────────────────────────────────────────────────
        initSortables() {
            document.querySelectorAll('#nav-tree .sortable-list').forEach(ul => {
                if (ul._sortable) ul._sortable.destroy();
                ul._sortable = Sortable.create(ul, {
                    group:      { name: 'nav-items', pull: true, put: true },
                    handle:     '.drag-handle',
                    animation:  150,
                    ghostClass: 'opacity-30',
                    onEnd:      () => this.autoSort(),
                    filter:     '.empty-root,.empty-placeholder',
                });
            });
        },

        autoSort() {
            const rootList = document.querySelector('#nav-tree > .sortable-list');
            if (!rootList) return;
            const tree = this.serializeTree(rootList);
            this.isSorting = true;
            this.sortMsg   = '';
            this.post(this.urls.itemSort, { menu_unique_id: this.menuUniqueId, tree })
                .then(d => {
                    this.isSorting = false;
                    if (d.success) {
                        this.sortMsg = 'Order saved ✓';
                        setTimeout(() => { this.sortMsg = ''; }, 2500);
                    }
                })
                .catch(() => { this.isSorting = false; });
        },

        serializeTree(ul) {
            return Array.from(ul.children ?? [])
                .filter(li => li.classList.contains('tree-item') && li.dataset.id)
                .map(li => ({
                    id:       li.dataset.id,
                    children: this.serializeTree(li.querySelector(':scope > .sortable-list') ?? { children: [] }),
                }));
        },

        // ── Panel ────────────────────────────────────────────────────────
        openAdd(parentUniqueId) {
            this.panelMode       = 'add';
            this.editingUniqueId = null;
            this.parentUniqueId  = parentUniqueId || null;
            this.saveError       = '';
            this.form = { title: '', type: 'external_url', page_id: '', url: '', icon: '', css_class: '', target: '_self', rel: '', visibility: 'both', status: 'Active' };
            this.showPanel = true;
            this.$nextTick(() => this.$refs.titleInput?.focus());
        },

        openEdit(item) {
            this.panelMode       = 'edit';
            this.editingUniqueId = item.unique_id;
            this.parentUniqueId  = null;
            this.saveError       = '';
            this.form = {
                title:      item.title      || '',
                type:       item.type       || 'external_url',
                page_id:    item.page_id    || '',
                url:        item.url        || '',
                icon:       item.icon       || '',
                css_class:  item.css_class  || '',
                target:     item.target     || '_self',
                rel:        item.rel        || '',
                visibility: item.visibility || 'both',
                status:     item.status     || 'Active',
            };
            this.showPanel = true;
            this.$nextTick(() => this.$refs.titleInput?.focus());
        },

        async saveItem() {
            if (!this.form.title.trim()) return;
            this.isSaving  = true;
            this.saveError = '';

            const payload = { ...this.form };
            let url, method = 'POST';

            if (this.panelMode === 'add') {
                url = this.urls.itemStore;
                payload.menu_unique_id   = this.menuUniqueId;
                payload.parent_unique_id = this.parentUniqueId || null;
            } else {
                url = this.urls.itemUpdate.replace('__ID__', this.editingUniqueId);
            }

            try {
                const data = await this.post(url, payload);
                this.isSaving = false;
                if (data.success) {
                    this.showPanel = false;
                    await this.refreshTree();
                } else {
                    this.saveError = data.message || 'Save failed. Check the form and try again.';
                }
            } catch (err) {
                this.isSaving  = false;
                this.saveError = 'An error occurred. Please try again.';
            }
        },

        // ── Item actions ─────────────────────────────────────────────────
        async duplicateItem(uniqueId) {
            const data = await this.post(this.urls.itemDuplicate.replace('__ID__', uniqueId), {});
            if (data.success) await this.refreshTree();
        },

        async toggleItem(uniqueId) {
            const data = await this.post(this.urls.itemToggle.replace('__ID__', uniqueId), {});
            if (data.success) await this.refreshTree();
        },

        requestDelete(uniqueId, title) {
            this.deleteConfirm = { show: true, uniqueId, title: title || 'Item' };
        },

        async confirmDelete() {
            this.isDeleting = true;
            const url  = this.urls.itemDelete.replace('__ID__', this.deleteConfirm.uniqueId);
            const data = await this.del(url);
            this.isDeleting = false;
            this.deleteConfirm.show = false;
            if (data.success) await this.refreshTree();
        },

        // ── Settings ─────────────────────────────────────────────────────
        async saveSettings() {
            this.isSavingSettings = true;
            this.settingsError    = '';
            this.settingsSuccess  = '';
            try {
                const data = await this.post(this.urls.menuUpdate, this.settings);
                this.isSavingSettings = false;
                if (data.success) {
                    this.settingsSuccess = 'Settings saved!';
                    setTimeout(() => { this.settingsSuccess = ''; this.showSettingsModal = false; }, 1500);
                } else {
                    this.settingsError = data.message || 'Save failed.';
                }
            } catch (err) {
                this.isSavingSettings = false;
                this.settingsError = 'An error occurred.';
            }
        },

        // ── Tree refresh ─────────────────────────────────────────────────
        async refreshTree() {
            const res  = await fetch(this.urls.tree, { headers: { 'Accept': 'text/html' } });
            const html = await res.text();
            document.getElementById('nav-tree').innerHTML = html;
            this.$nextTick(() => this.initSortables());
        },

        // ── HTTP helpers ─────────────────────────────────────────────────
        post(url, data) {
            return fetch(url, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken },
                body:    JSON.stringify(data),
            }).then(r => r.json());
        },

        del(url) {
            return fetch(url, {
                method:  'DELETE',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken },
            }).then(r => r.json());
        },
    };
}

// Expand / collapse tree nodes
function navTreeToggle(btn) {
    const li  = btn.closest('.tree-item');
    const sub = li?.querySelector(':scope > .sortable-list');
    if (!sub) return;
    const isOpen = btn.getAttribute('aria-expanded') !== 'false';
    sub.style.display = isOpen ? 'none' : '';
    btn.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
    btn.querySelector('svg')?.classList.toggle('rotate-[-90deg]', isOpen);
}
</script>

@endsection
