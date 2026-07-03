{{--
    Global Media Picker Modal
    ─────────────────────────────────────────────────────────────
    Usage (in any Blade page):
        @include('admin.partials._media_picker_modal')

    JS API (from Alpine or vanilla JS):
        window.MediaPicker.open(callback, options)

    callback receives: { id, unique_id, url, alt_text, title, width, height, ... }

    options (optional):
        { type: 'image' }  — future filter support

    Example (Alpine):
        @click="window.MediaPicker.open(media => { mediaId = media.id; previewUrl = media.url; })"
--}}

<div id="media-picker-modal"
     x-data="mediaPickerModal()"
     x-init="init()"
     x-show="open"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @keydown.escape.window="open && close()"
     class="fixed inset-0 z-[9000] flex items-center justify-center p-4"
     style="display: none;">

    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/50" @click="close()"></div>

    {{-- Modal --}}
    <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-5xl max-h-[85vh] flex flex-col overflow-hidden">

        {{-- Header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 shrink-0">
            <div class="flex items-center gap-2.5">
                <x-heroicon-o-photo class="w-5 h-5 text-blue-600"/>
                <h2 class="text-base font-semibold text-gray-900">Choose from Media Library</h2>
            </div>
            <div class="flex items-center gap-3">
                {{-- Upload new tab --}}
                <button @click="activeTab = 'library'"
                        :class="activeTab === 'library' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700'"
                        class="text-sm font-medium pb-0.5 transition-colors">Library</button>
                <button @click="activeTab = 'upload'"
                        :class="activeTab === 'upload' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700'"
                        class="text-sm font-medium pb-0.5 transition-colors">Upload New</button>
                <button @click="close()" class="ml-3 text-gray-400 hover:text-gray-600">
                    <x-heroicon-o-x-mark class="w-5 h-5"/>
                </button>
            </div>
        </div>

        {{-- Body --}}
        <div class="flex-1 flex overflow-hidden min-h-0">

            {{-- ── Library Tab ──────────────────────────────────────────── --}}
            <div class="flex-1 flex flex-col min-w-0" x-show="activeTab === 'library'">

                {{-- Search + Folder filter --}}
                <div class="px-4 py-3 border-b border-gray-100 flex items-center gap-3 shrink-0">
                    <x-heroicon-o-magnifying-glass class="w-4 h-4 text-gray-400 shrink-0"/>
                    <input type="text"
                           x-model="search"
                           @input.debounce.350ms="fetchMedia(1)"
                           placeholder="Search images..."
                           class="flex-1 text-sm text-gray-700 border-none outline-none focus:ring-0 bg-transparent placeholder-gray-400">
                    <select x-model="activeFolderId"
                            @change="fetchMedia(1)"
                            class="text-xs border border-gray-200 rounded-md px-2 py-1 bg-white focus:outline-none focus:ring-1 focus:ring-blue-400 text-gray-600">
                        <option value="">All folders</option>
                        <template x-for="folder in folders" :key="folder.id">
                            <option :value="folder.id" x-text="folder.name"></option>
                        </template>
                    </select>
                </div>

                {{-- Grid --}}
                <div class="flex-1 overflow-y-auto p-4">

                    {{-- Loading skeleton --}}
                    <div x-show="isLoading && images.length === 0"
                         class="grid grid-cols-5 sm:grid-cols-6 md:grid-cols-8 gap-2">
                        <template x-for="n in 24" :key="n">
                            <div class="aspect-square bg-gray-100 rounded-md animate-pulse"></div>
                        </template>
                    </div>

                    {{-- Empty --}}
                    <div x-show="!isLoading && images.length === 0" class="py-16 text-center">
                        <x-heroicon-o-photo class="w-10 h-10 text-gray-300 mx-auto mb-3"/>
                        <p class="text-gray-500 text-sm">No images found. Try uploading one.</p>
                    </div>

                    {{-- Images grid --}}
                    <div x-show="images.length > 0"
                         class="grid grid-cols-5 sm:grid-cols-6 md:grid-cols-8 gap-2">
                        <template x-for="img in images" :key="img.unique_id">
                            <button type="button"
                                    @click="toggleSelect(img)"
                                    :class="selected && selected.unique_id === img.unique_id
                                        ? 'ring-2 ring-blue-500 ring-offset-1 scale-95'
                                        : 'hover:ring-2 hover:ring-blue-300 hover:ring-offset-1'"
                                    class="group relative aspect-square bg-gray-100 rounded-md overflow-hidden transition-all">
                                <img x-show="img.is_image"
                                     :src="img.url" :alt="img.alt_text || img.original_file_name"
                                     class="w-full h-full object-cover" loading="lazy">
                                <div x-show="!img.is_image"
                                     class="w-full h-full flex items-center justify-center text-gray-400 text-xs font-mono uppercase"
                                     x-text="img.file_extension"></div>
                                {{-- Selected checkmark --}}
                                <div x-show="selected && selected.unique_id === img.unique_id"
                                     class="absolute top-1 right-1 w-5 h-5 bg-blue-600 rounded-full flex items-center justify-center">
                                    <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                            </button>
                        </template>
                    </div>

                    {{-- Load more --}}
                    <div x-show="currentPage < lastPage" class="mt-4 text-center">
                        <button @click="fetchMedia(currentPage + 1)"
                                :disabled="isLoading"
                                class="inline-flex items-center gap-1.5 text-xs text-gray-500 hover:text-blue-600 disabled:opacity-50 transition-colors">
                            <x-heroicon-o-arrow-path class="w-3.5 h-3.5" x-bind:class="isLoading ? 'animate-spin' : ''"/>
                            Load more
                        </button>
                    </div>
                </div>

                {{-- Footer with selected preview + confirm --}}
                <div class="border-t border-gray-100 px-4 py-3 flex items-center gap-3 bg-gray-50 shrink-0">
                    <template x-if="selected">
                        <div class="flex items-center gap-3 flex-1 min-w-0">
                            <img :src="selected.url" class="w-10 h-10 object-cover rounded-md border border-gray-200 shrink-0">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate" x-text="selected.original_file_name"></p>
                                <p class="text-xs text-gray-400" x-text="selected.file_size_formatted + (selected.width ? ' · ' + selected.width + '×' + selected.height : '')"></p>
                            </div>
                        </div>
                    </template>
                    <template x-if="!selected">
                        <p class="text-sm text-gray-400 flex-1">No image selected</p>
                    </template>
                    <div class="flex items-center gap-2 shrink-0">
                        <button @click="close()"
                                class="px-4 py-2 text-sm text-gray-600 bg-white border border-gray-200 rounded-md hover:bg-gray-50 transition-colors">
                            Cancel
                        </button>
                        <button @click="confirmSelection()"
                                :disabled="!selected"
                                class="px-4 py-2 text-sm text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                            Use Selected Image
                        </button>
                    </div>
                </div>
            </div>

            {{-- ── Upload Tab ────────────────────────────────────────────── --}}
            <div class="flex-1 flex flex-col items-center justify-center p-8" x-show="activeTab === 'upload'">

                {{-- Drop zone --}}
                <div class="w-full max-w-lg border-2 border-dashed border-gray-300 rounded-xl p-10 text-center
                            hover:border-blue-400 transition-colors cursor-pointer"
                     :class="isUploadDragging ? 'border-blue-500 bg-blue-50' : ''"
                     @dragover.prevent="isUploadDragging = true"
                     @dragleave.prevent="isUploadDragging = false"
                     @drop.prevent="isUploadDragging = false; handlePickerUpload($event.dataTransfer.files)">
                    <x-heroicon-o-arrow-up-tray class="w-10 h-10 text-gray-400 mx-auto mb-3"/>
                    <p class="text-gray-700 font-medium mb-1">Drop images here</p>
                    <p class="text-gray-400 text-sm mb-4">JPG, PNG, GIF, WebP, SVG — max 10 MB each</p>
                    <label class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-md text-sm font-medium cursor-pointer transition-colors">
                        <x-heroicon-o-folder-open class="w-4 h-4"/>
                        Browse Files
                        <input type="file" multiple accept="image/*" class="sr-only"
                               @change="handlePickerUpload($event.target.files); $event.target.value = ''">
                    </label>
                </div>

                {{-- Upload progress --}}
                <div class="w-full max-w-lg mt-4 space-y-2" x-show="pickerUploads.length > 0">
                    <template x-for="(u, i) in pickerUploads" :key="i">
                        <div class="bg-white border border-gray-200 rounded-md px-4 py-3 flex items-center gap-3">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-700 truncate font-medium" x-text="u.name"></p>
                                <div class="mt-1.5 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-blue-500 transition-all rounded-full" :style="'width:' + u.progress + '%'"></div>
                                </div>
                            </div>
                            <span x-show="!u.done && !u.error" class="text-xs text-gray-400" x-text="u.progress + '%'"></span>
                            <span x-show="u.done && !u.error" class="text-xs text-green-600 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                                Done
                            </span>
                            <span x-show="u.error" class="text-xs text-red-500" x-text="u.error"></span>
                        </div>
                    </template>
                </div>

                {{-- Last uploaded + use button --}}
                <template x-if="lastUploaded">
                    <div class="w-full max-w-lg mt-4 bg-green-50 border border-green-200 rounded-lg p-4 flex items-center gap-3">
                        <img :src="lastUploaded.url" class="w-12 h-12 object-cover rounded-md border border-green-200 shrink-0">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-green-800 truncate" x-text="lastUploaded.original_file_name"></p>
                            <p class="text-xs text-green-600">Upload complete</p>
                        </div>
                        <button @click="selected = lastUploaded; confirmSelection()"
                                class="shrink-0 bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-md text-sm font-medium transition-colors">
                            Use This Image
                        </button>
                    </div>
                </template>

            </div>
        </div>
    </div>
</div>

<script>
(function() {
    // ── Global JS API ─────────────────────────────────────────────────────
    window.MediaPicker = {
        _callback: null,
        open(callback, options) {
            this._callback = callback;
            const modal = document.querySelector('#media-picker-modal')?._x_dataStack?.[0];
            if (modal) modal.openModal();
        },
        _resolve(media) {
            if (this._callback) { this._callback(media); this._callback = null; }
        },
    };

    function mediaPickerModal() {
        return {
            open: false,
            activeTab: 'library',

            pickerUrl:  '{{ route("admin.website-management.media-library.picker") }}',
            uploadUrl:  '{{ route("admin.website-management.media-library.upload") }}',
            csrfToken:  '{{ csrf_token() }}',

            // Library state
            images:       [],
            folders:      [],
            isLoading:    false,
            search:       '',
            activeFolderId: '',
            currentPage:  1,
            lastPage:     1,
            selected:     null,

            // Upload state
            isUploadDragging: false,
            pickerUploads:    [],
            lastUploaded:     null,

            init() {
                // Expose open function for window.MediaPicker
            },

            openModal() {
                this.open       = true;
                this.selected   = null;
                this.activeTab  = 'library';
                this.lastUploaded = null;
                if (this.images.length === 0) this.fetchMedia(1);
            },

            close() {
                this.open = false;
            },

            toggleSelect(img) {
                this.selected = (this.selected?.unique_id === img.unique_id) ? null : img;
            },

            confirmSelection() {
                if (!this.selected) return;
                window.MediaPicker._resolve(this.selected);
                this.close();
            },

            async fetchMedia(page) {
                this.isLoading = true;
                const params = new URLSearchParams({ page, per_page: 40 });
                if (this.search)         params.set('search', this.search);
                if (this.activeFolderId) params.set('folder_id', this.activeFolderId);

                try {
                    const res  = await fetch(`${this.pickerUrl}?${params}`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.images      = page === 1 ? data.data : [...this.images, ...data.data];
                        this.currentPage = data.meta.current_page;
                        this.lastPage    = data.meta.last_page;
                        if (data.folders) this.folders = data.folders;
                    }
                } finally {
                    this.isLoading = false;
                }
            },

            handlePickerUpload(files) {
                Array.from(files).forEach(file => {
                    const u = { name: file.name, progress: 0, done: false, error: null };
                    this.pickerUploads.push(u);

                    const fd  = new FormData();
                    fd.append('file', file);

                    const xhr = new XMLHttpRequest();
                    xhr.upload.onprogress = e => {
                        if (e.lengthComputable) u.progress = Math.round(e.loaded / e.total * 100);
                    };
                    xhr.onload = () => {
                        u.done = true;
                        try {
                            const data = JSON.parse(xhr.responseText);
                            if (data.success) {
                                this.lastUploaded = data.media;
                                this.images.unshift(data.media);
                            } else {
                                u.error = data.message || 'Upload failed';
                            }
                        } catch (e) { u.error = 'Upload failed'; }
                    };
                    xhr.onerror = () => { u.error = 'Network error'; };
                    xhr.open('POST', this.uploadUrl);
                    xhr.setRequestHeader('X-CSRF-TOKEN', this.csrfToken);
                    xhr.setRequestHeader('Accept', 'application/json');
                    xhr.send(fd);
                });
            },
        };
    }

    // Make mediaPickerModal available globally for Alpine
    window.mediaPickerModal = mediaPickerModal;
})();
</script>
