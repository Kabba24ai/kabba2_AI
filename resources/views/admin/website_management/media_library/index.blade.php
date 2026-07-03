@extends('admin.layouts.app')

@section('title', 'Media Library')

@section('content')

<div class="px-4 sm:px-6 lg:px-8 py-6"
     x-data="mediaLibrary()"
     x-init="init()"
     @dragover.window.prevent="isDragging = true"
     @dragleave.window="isDragging = false"
     @drop.window.prevent="handleDrop($event)">

    {{-- Page Header --}}
    <div class="bg-white rounded-md p-5 shadow-sm border border-gray-100 mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-3 mb-1">
                <x-heroicon-o-photo class="w-8 h-8 text-blue-600"/>
                <h1 class="text-2xl font-semibold text-gray-900">Media Library</h1>
            </div>
            <p class="text-gray-500 text-sm">Centralized image library for all builders.</p>
        </div>
        <div class="flex items-center gap-2">
            <label class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-md text-sm font-medium shadow-sm transition-colors cursor-pointer">
                <x-heroicon-o-arrow-up-tray class="w-4 h-4"/>
                Upload Images
                <input type="file" multiple accept="image/*" class="sr-only" @change="handleFileInput($event)">
            </label>
        </div>
    </div>

    {{-- Upload progress bar area --}}
    <template x-if="uploads.length > 0">
        <div class="mb-4 space-y-2">
            <template x-for="(upload, i) in uploads" :key="i">
                <div class="bg-white border border-gray-200 rounded-md px-4 py-3 flex items-center gap-3 shadow-sm">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate" x-text="upload.name"></p>
                        <div class="mt-1.5 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-blue-500 rounded-full transition-all"
                                 :style="'width: ' + upload.progress + '%'"></div>
                        </div>
                    </div>
                    <span x-show="!upload.done && !upload.error" class="text-xs text-gray-400" x-text="upload.progress + '%'"></span>
                    <span x-show="upload.done && !upload.error" class="text-xs text-green-600">Done</span>
                    <span x-show="upload.error" class="text-xs text-red-500" x-text="upload.error"></span>
                </div>
            </template>
        </div>
    </template>

    {{-- Search bar --}}
    <div class="bg-white rounded-md border border-gray-100 shadow-sm px-4 py-3 mb-4 flex items-center gap-3">
        <x-heroicon-o-magnifying-glass class="w-4 h-4 text-gray-400 shrink-0"/>
        <input type="text"
               x-model="search"
               @input.debounce.350ms="fetchImages(1)"
               placeholder="Search by filename, alt text, or title..."
               class="flex-1 text-sm text-gray-700 placeholder-gray-400 border-none outline-none focus:ring-0 bg-transparent">
        <template x-if="search">
            <button @click="search = ''; fetchImages(1)" class="text-gray-300 hover:text-gray-500">
                <x-heroicon-o-x-mark class="w-4 h-4"/>
            </button>
        </template>
        <span class="text-xs text-gray-400 shrink-0" x-text="total + ' files'"></span>
    </div>

    {{-- Two-column: folders sidebar + grid --}}
    <div class="flex gap-5 items-start">

        {{-- Folders Sidebar --}}
        <aside class="w-48 shrink-0 space-y-1">
            <div class="flex items-center justify-between mb-2 px-1">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Folders</span>
                <button @click="showNewFolderModal = true"
                        class="text-gray-400 hover:text-blue-600 transition-colors" title="New folder">
                    <x-heroicon-o-folder-plus class="w-4 h-4"/>
                </button>
            </div>

            <button @click="selectFolder(null)"
                    :class="activeFolder === null ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-600 hover:bg-gray-50'"
                    class="w-full text-left flex items-center gap-2 px-3 py-1.5 rounded-md text-sm transition-colors">
                <x-heroicon-o-squares-2x2 class="w-4 h-4 shrink-0"/>
                All Files
                <span class="ml-auto text-xs text-gray-400" x-text="total"></span>
            </button>

            <template x-for="folder in folderList" :key="folder.id">
                <button @click="selectFolder(folder.id)"
                        :class="activeFolder === folder.id ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-600 hover:bg-gray-50'"
                        class="w-full text-left flex items-center gap-2 px-3 py-1.5 rounded-md text-sm transition-colors">
                    <x-heroicon-o-folder class="w-4 h-4 shrink-0"/>
                    <span class="truncate" x-text="folder.name"></span>
                    <span class="ml-auto text-xs text-gray-400" x-text="folder.count"></span>
                </button>
            </template>

            @if($folders->isEmpty())
            <p class="text-xs text-gray-400 px-3 py-2">No folders yet.</p>
            @endif
        </aside>

        {{-- Media Grid --}}
        <div class="flex-1 min-w-0">

            {{-- Drop overlay --}}
            <div x-show="isDragging"
                 class="fixed inset-0 bg-blue-600/10 border-4 border-dashed border-blue-400 z-40 flex items-center justify-center pointer-events-none">
                <div class="bg-white rounded-xl shadow-lg px-8 py-6 text-center">
                    <x-heroicon-o-arrow-up-tray class="w-10 h-10 text-blue-500 mx-auto mb-2"/>
                    <p class="text-blue-700 font-semibold">Drop images to upload</p>
                </div>
            </div>

            {{-- Loading state --}}
            <div x-show="isLoading && images.length === 0" class="grid grid-cols-4 sm:grid-cols-5 md:grid-cols-6 xl:grid-cols-8 gap-3">
                <template x-for="n in 24" :key="n">
                    <div class="aspect-square bg-gray-100 rounded-md animate-pulse"></div>
                </template>
            </div>

            {{-- Empty state --}}
            <div x-show="!isLoading && images.length === 0"
                 class="bg-white rounded-xl border-2 border-dashed border-gray-200 py-16 text-center">
                <x-heroicon-o-photo class="w-12 h-12 text-gray-300 mx-auto mb-3"/>
                <p class="text-gray-500 text-sm font-medium">No images found</p>
                <p class="text-gray-400 text-xs mt-1">Upload images or clear the search filter</p>
                <label class="mt-4 inline-flex items-center gap-1.5 text-sm text-blue-600 hover:underline cursor-pointer">
                    <x-heroicon-o-plus class="w-4 h-4"/>
                    Upload your first image
                    <input type="file" multiple accept="image/*" class="sr-only" @change="handleFileInput($event)">
                </label>
            </div>

            {{-- Image grid --}}
            <div x-show="images.length > 0"
                 class="grid grid-cols-4 sm:grid-cols-5 md:grid-cols-6 xl:grid-cols-8 gap-2.5">
                <template x-for="img in images" :key="img.unique_id">
                    <button type="button"
                            @click="selectImage(img)"
                            :class="selectedImage && selectedImage.unique_id === img.unique_id
                                ? 'ring-2 ring-blue-500 ring-offset-1'
                                : 'hover:ring-2 hover:ring-blue-300 hover:ring-offset-1'"
                            class="group relative aspect-square bg-gray-100 rounded-md overflow-hidden transition-all">
                        <img :src="img.url"
                             :alt="img.alt_text || img.original_file_name"
                             class="w-full h-full object-cover"
                             loading="lazy"
                             x-show="img.is_image">
                        <div x-show="!img.is_image"
                             class="w-full h-full flex items-center justify-center text-gray-400 text-xs font-mono uppercase"
                             x-text="img.file_extension"></div>
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors"></div>
                    </button>
                </template>
            </div>

            {{-- Load more --}}
            <div x-show="currentPage < lastPage" class="mt-6 text-center">
                <button @click="loadMore()"
                        :disabled="isLoading"
                        class="inline-flex items-center gap-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 px-5 py-2 rounded-md text-sm font-medium transition-colors disabled:opacity-50">
                    <x-heroicon-o-arrow-path class="w-4 h-4" x-bind:class="isLoading ? 'animate-spin' : ''"/>
                    Load more
                </button>
            </div>

        </div>
    </div>

    {{-- ── Details Slide-Over ───────────────────────────────────────────── --}}
    <div x-show="showDetails"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-x-4"
         x-transition:enter-end="opacity-100 translate-x-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-x-0"
         x-transition:leave-end="opacity-0 translate-x-4"
         class="fixed right-0 top-0 bottom-0 w-80 bg-white border-l border-gray-200 shadow-xl z-30 flex flex-col overflow-hidden"
         @keydown.escape.window="showDetails = false">

        {{-- Header --}}
        <div class="flex items-center justify-between px-4 py-3.5 border-b border-gray-100 shrink-0">
            <h3 class="text-sm font-semibold text-gray-900">Image Details</h3>
            <button @click="showDetails = false" class="text-gray-400 hover:text-gray-600">
                <x-heroicon-o-x-mark class="w-5 h-5"/>
            </button>
        </div>

        {{-- Body --}}
        <div class="flex-1 overflow-y-auto p-4 space-y-4">

            {{-- Preview --}}
            <template x-if="selectedImage">
                <div>
                    {{-- Image preview --}}
                    <div class="bg-gray-50 rounded-lg overflow-hidden mb-3 aspect-video flex items-center justify-center">
                        <img :src="selectedImage.url" :alt="selectedImage.alt_text || ''"
                             class="max-w-full max-h-48 object-contain" x-show="selectedImage.is_image">
                        <div x-show="!selectedImage.is_image"
                             class="text-gray-400 text-2xl font-mono uppercase" x-text="selectedImage.file_extension"></div>
                    </div>

                    {{-- File info --}}
                    <div class="text-xs text-gray-500 space-y-1 bg-gray-50 rounded-md p-3 mb-4">
                        <div class="flex justify-between">
                            <span class="text-gray-400">Filename</span>
                            <span class="font-medium text-gray-700 truncate max-w-[140px]" x-text="selectedImage.original_file_name"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Size</span>
                            <span class="font-medium text-gray-700" x-text="selectedImage.file_size_formatted"></span>
                        </div>
                        <template x-if="selectedImage.width && selectedImage.height">
                            <div class="flex justify-between">
                                <span class="text-gray-400">Dimensions</span>
                                <span class="font-medium text-gray-700" x-text="selectedImage.width + '×' + selectedImage.height"></span>
                            </div>
                        </template>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Uploaded</span>
                            <span class="font-medium text-gray-700" x-text="selectedImage.created_at_date"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Used in</span>
                            <span :class="usageCount > 0 ? 'text-amber-600' : 'text-gray-700'"
                                  class="font-medium" x-text="usageCount + ' location(s)'"></span>
                        </div>
                    </div>

                    {{-- Metadata edit form --}}
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Alt Text</label>
                            <input type="text" x-model="editAltText" placeholder="Describe the image..."
                                   class="w-full text-sm border border-gray-200 rounded-md px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-blue-400">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Title</label>
                            <input type="text" x-model="editTitle" placeholder="Image title..."
                                   class="w-full text-sm border border-gray-200 rounded-md px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-blue-400">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Caption</label>
                            <input type="text" x-model="editCaption" placeholder="Caption..."
                                   class="w-full text-sm border border-gray-200 rounded-md px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-blue-400">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Folder</label>
                            <select x-model="editFolderId"
                                    class="w-full text-sm border border-gray-200 rounded-md px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-blue-400 bg-white">
                                <option value="">— No folder —</option>
                                <template x-for="folder in folderList" :key="folder.id">
                                    <option :value="folder.id" x-text="folder.name"></option>
                                </template>
                            </select>
                        </div>

                        {{-- Save button --}}
                        <button @click="saveMetadata()"
                                :disabled="isSaving"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2 rounded-md transition-colors disabled:opacity-50 flex items-center justify-center gap-2">
                            <x-heroicon-o-check class="w-4 h-4"/>
                            <span x-text="isSaving ? 'Saving...' : (saveMsg || 'Save Changes')"></span>
                        </button>
                    </div>

                    {{-- Divider --}}
                    <div class="border-t border-gray-100 my-4"></div>

                    {{-- Copy URL --}}
                    <div class="mb-3">
                        <label class="block text-xs font-medium text-gray-600 mb-1">URL</label>
                        <div class="flex items-center gap-2">
                            <input type="text" :value="selectedImage.url" readonly
                                   class="flex-1 text-xs border border-gray-200 rounded-md px-2 py-1.5 bg-gray-50 text-gray-500 truncate">
                            <button @click="navigator.clipboard.writeText(selectedImage.url)"
                                    class="shrink-0 text-gray-400 hover:text-blue-600 transition-colors" title="Copy URL">
                                <x-heroicon-o-clipboard class="w-4 h-4"/>
                            </button>
                        </div>
                    </div>

                    {{-- Replace file --}}
                    <div class="mb-3">
                        <label class="inline-flex items-center gap-2 text-xs text-gray-600 hover:text-blue-600 cursor-pointer transition-colors">
                            <x-heroicon-o-arrow-path class="w-3.5 h-3.5" x-bind:class="isReplacing ? 'animate-spin' : ''"/>
                            <span x-text="isReplacing ? 'Replacing...' : 'Replace image file'"></span>
                            <input type="file" accept="image/*" class="sr-only"
                                   @change="triggerReplaceFile($event.target.files[0]); $event.target.value = ''"
                                   :disabled="isReplacing">
                        </label>
                        <p class="text-xs text-gray-400 mt-0.5">All usages update automatically.</p>
                    </div>

                    {{-- Delete --}}
                    <div>
                        <template x-if="!showDeleteConfirm">
                            <button @click="showDeleteConfirm = true"
                                    class="inline-flex items-center gap-1.5 text-xs text-red-500 hover:text-red-700 transition-colors">
                                <x-heroicon-o-trash class="w-3.5 h-3.5"/>
                                Delete image
                            </button>
                        </template>
                        <template x-if="showDeleteConfirm">
                            <div class="bg-red-50 border border-red-100 rounded-md p-3">
                                <p class="text-xs text-red-700 font-medium mb-2">Delete this image permanently?</p>
                                <template x-if="deleteError">
                                    <p class="text-xs text-red-600 mb-2" x-text="deleteError"></p>
                                </template>
                                <div class="flex items-center gap-2">
                                    <button @click="deleteImage()"
                                            :disabled="isDeleting"
                                            class="flex-1 bg-red-600 hover:bg-red-700 text-white text-xs font-medium py-1.5 rounded-md transition-colors disabled:opacity-50">
                                        <span x-text="isDeleting ? 'Deleting...' : 'Yes, delete'"></span>
                                    </button>
                                    <button @click="showDeleteConfirm = false; deleteError = ''"
                                            class="flex-1 bg-white border border-gray-200 text-gray-600 text-xs font-medium py-1.5 rounded-md hover:bg-gray-50 transition-colors">
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>

                </div>
            </template>
        </div>
    </div>

    {{-- Slide-over backdrop (subtle) --}}
    <div x-show="showDetails"
         @click="showDetails = false"
         class="fixed inset-0 z-20 bg-transparent"></div>

    {{-- ── New Folder Modal ──────────────────────────────────────────────── --}}
    <div x-show="showNewFolderModal"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" @click="showNewFolderModal = false"></div>
        <div class="relative bg-white rounded-xl shadow-xl w-full max-w-sm p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">New Folder</h3>
            <input type="text" x-model="newFolderName"
                   @keydown.enter="createFolder()"
                   placeholder="Folder name (e.g. Hero Images)"
                   class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 mb-4 focus:outline-none focus:ring-1 focus:ring-blue-400"
                   x-ref="folderInput"
                   x-init="$watch('showNewFolderModal', v => v && $nextTick(() => $refs.folderInput?.focus()))">
            <div class="flex gap-2">
                <button @click="createFolder()"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2 rounded-md transition-colors">
                    Create Folder
                </button>
                <button @click="showNewFolderModal = false; newFolderName = ''"
                        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium py-2 rounded-md transition-colors">
                    Cancel
                </button>
            </div>
        </div>
    </div>

</div>

<script>
function mediaLibrary() {
    return {
        pickerUrl:  '{{ route("admin.website-management.media-library.picker") }}',
        uploadUrl:  '{{ route("admin.website-management.media-library.upload") }}',
        foldersUrl: '{{ route("admin.website-management.media-library.folders.store") }}',
        baseUrl:    '{{ rtrim(route("admin.website-management.media-library.index"), "/") }}',
        csrfToken:  '{{ csrf_token() }}',

        images:      @json($media->getCollection()->map->toLibraryArray()->values()),
        isLoading:   false,
        currentPage: {{ $media->currentPage() }},
        lastPage:    {{ $media->lastPage() }},
        total:       {{ $media->total() }},

        search:         '{{ $filters["search"] ?? "" }}',
        activeFolder:   @json($filters['folder_id'] ?? null),

        isDragging:  false,
        uploads:     [],

        selectedImage: null,
        showDetails:   false,
        usageCount:    0,

        editAltText:     '',
        editTitle:       '',
        editCaption:     '',
        editDescription: '',
        editFolderId:    '',
        isSaving:        false,
        saveMsg:         '',

        showNewFolderModal: false,
        newFolderName:      '',
        folderList: @json($folders->map(fn($f) => ['id' => $f->id, 'name' => $f->name, 'count' => $f->media_count ?? 0])->values()),

        showDeleteConfirm: false,
        isDeleting:        false,
        deleteError:       '',

        showReplaceModal: false,
        isReplacing:      false,

        init() {
            // images already server-rendered; only refresh if filters are active
            @if(!empty($filters['search']) || !empty($filters['folder_id']))
            this.fetchImages(1);
            @endif
        },

        async fetchImages(page) {
            this.isLoading = true;
            const params = new URLSearchParams({ page, per_page: 40 });
            if (this.search)           params.set('search', this.search);
            if (this.activeFolder !== null && this.activeFolder !== '')
                                        params.set('folder_id', this.activeFolder);

            try {
                const res  = await fetch(`${this.pickerUrl}?${params}`, {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken }
                });
                const data = await res.json();
                if (data.success) {
                    this.images      = page === 1 ? data.data : [...this.images, ...data.data];
                    this.currentPage = data.meta.current_page;
                    this.lastPage    = data.meta.last_page;
                    this.total       = data.meta.total;
                    if (data.folders && page === 1) this.folderList = data.folders;
                }
            } finally {
                this.isLoading = false;
            }
        },

        loadMore() {
            if (this.currentPage < this.lastPage && !this.isLoading) {
                this.fetchImages(this.currentPage + 1);
            }
        },

        selectFolder(id) {
            this.activeFolder = id;
            this.fetchImages(1);
        },

        selectImage(img) {
            this.selectedImage   = img;
            this.editAltText     = img.alt_text     || '';
            this.editTitle       = img.title        || '';
            this.editCaption     = img.caption      || '';
            this.editDescription = img.description  || '';
            this.editFolderId    = img.folder_id    || '';
            this.showDetails     = true;
            this.usageCount      = 0;
            this.deleteError     = '';
            this.saveMsg         = '';
            this.showDeleteConfirm = false;

            fetch(`${this.baseUrl}/${img.unique_id}`, {
                headers: { 'Accept': 'application/json' }
            }).then(r => r.json()).then(d => { if (d.success) this.usageCount = d.usage_count; });
        },

        async saveMetadata() {
            this.isSaving = true;
            const res = await fetch(`${this.baseUrl}/${this.selectedImage.unique_id}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify({
                    alt_text:        this.editAltText,
                    title:           this.editTitle,
                    caption:         this.editCaption,
                    description:     this.editDescription,
                    media_folder_id: this.editFolderId || null,
                }),
            });
            const data = await res.json();
            this.isSaving = false;
            if (data.success) {
                this.saveMsg       = 'Saved!';
                this.selectedImage = data.media;
                const idx = this.images.findIndex(i => i.unique_id === data.media.unique_id);
                if (idx !== -1) this.images[idx] = data.media;
                setTimeout(() => { this.saveMsg = ''; }, 2000);
            }
        },

        async deleteImage() {
            this.isDeleting  = true;
            this.deleteError = '';
            const res = await fetch(`${this.baseUrl}/${this.selectedImage.unique_id}`, {
                method: 'DELETE',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken }
            });
            const data = await res.json();
            this.isDeleting = false;
            if (data.success) {
                this.images = this.images.filter(i => i.unique_id !== this.selectedImage.unique_id);
                this.total--;
                this.showDeleteConfirm = false;
                this.showDetails       = false;
                this.selectedImage     = null;
            } else {
                this.deleteError       = data.message || 'Cannot delete this image.';
                this.showDeleteConfirm = false;
            }
        },

        handleDrop(e) {
            this.isDragging = false;
            const files = e.dataTransfer?.files;
            if (files?.length) this.uploadFiles(files);
        },

        handleFileInput(e) {
            this.uploadFiles(e.target.files);
            e.target.value = '';
        },

        uploadFiles(files) {
            Array.from(files).forEach(file => {
                const upload = { name: file.name, progress: 0, done: false, error: null };
                this.uploads.push(upload);

                const fd  = new FormData();
                fd.append('file', file);

                const xhr = new XMLHttpRequest();
                xhr.upload.onprogress = e => {
                    if (e.lengthComputable) upload.progress = Math.round(e.loaded / e.total * 100);
                };
                xhr.onload = () => {
                    try {
                        const data = JSON.parse(xhr.responseText);
                        upload.done = true;
                        if (data.success) {
                            this.images.unshift(data.media);
                            this.total++;
                        } else {
                            upload.error = data.message || 'Upload failed';
                        }
                    } catch (err) {
                        upload.error = 'Upload failed';
                    }
                    setTimeout(() => {
                        this.uploads = this.uploads.filter(u => u !== upload);
                    }, 3000);
                };
                xhr.onerror = () => {
                    upload.error = 'Network error';
                    setTimeout(() => { this.uploads = this.uploads.filter(u => u !== upload); }, 3000);
                };
                xhr.open('POST', this.uploadUrl);
                xhr.setRequestHeader('X-CSRF-TOKEN', this.csrfToken);
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.send(fd);
            });
        },

        triggerReplaceFile(file) {
            if (!file) return;
            this.isReplacing = true;
            const fd = new FormData();
            fd.append('file', file);
            fetch(`${this.baseUrl}/${this.selectedImage.unique_id}/replace`, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken },
                body: fd,
            }).then(r => r.json()).then(data => {
                this.isReplacing = false;
                if (data.success) {
                    this.selectedImage = data.media;
                    const idx = this.images.findIndex(i => i.unique_id === data.media.unique_id);
                    if (idx !== -1) this.images[idx] = data.media;
                }
            }).catch(() => { this.isReplacing = false; });
        },

        async createFolder() {
            if (!this.newFolderName.trim()) return;
            const res = await fetch(this.foldersUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify({ name: this.newFolderName }),
            });
            const data = await res.json();
            if (data.success) {
                this.folderList.push({ id: data.folder.id, name: data.folder.name, count: 0 });
                this.newFolderName      = '';
                this.showNewFolderModal = false;
            }
        },
    };
}
</script>

@endsection
