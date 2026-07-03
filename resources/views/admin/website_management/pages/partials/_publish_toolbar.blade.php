{{--
    Publish Toolbar — include in any page editor.
    Requires: $page (WebsitePage model)
    Optional: $backUrl (string) — where the "← Back" link points

    Usage:
        @include('admin.website_management.pages.partials._publish_toolbar', ['page' => $page])

    JS API exposed:
        window.publishToolbar.saveDraft(summary?)
        window.publishToolbar.publish()
        window.publishToolbar.unpublish()
        window.publishToolbar.archive()
        window.publishToolbar.startAutoSave(intervalMs = 60000)
        window.publishToolbar.stopAutoSave()
--}}
<div id="publish-toolbar"
     x-data="publishToolbar()"
     x-init="init()"
     class="bg-white border border-gray-100 rounded-xl shadow-sm px-4 py-3 flex flex-col sm:flex-row sm:items-center gap-3 mb-5">

    {{-- Status badge --}}
    <div class="flex items-center gap-2 shrink-0">
        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold
            @if($page->publish_status === 'published') bg-green-100 text-green-700
            @elseif($page->publish_status === 'archived') bg-orange-100 text-orange-700
            @else bg-gray-100 text-gray-500 @endif"
              x-text="statusLabel"
              :class="{
                'bg-green-100 text-green-700':  currentStatus === 'published',
                'bg-gray-100 text-gray-500':    currentStatus === 'draft',
                'bg-orange-100 text-orange-700':currentStatus === 'archived',
              }">
            {{ match($page->publish_status) { 'published' => '● Live', 'archived' => '◎ Archived', default => '○ Draft' } }}
        </span>
        @if($page->published_at)
            <span class="text-xs text-gray-400 hidden md:inline">
                Published {{ $page->published_at->diffForHumans() }}
            </span>
        @endif
    </div>

    <div class="flex-1 flex flex-wrap items-center gap-2">

        {{-- Save Draft --}}
        <button @click="saveDraft()"
                :disabled="isBusy"
                class="inline-flex items-center gap-1.5 border border-gray-200 hover:bg-gray-50 text-gray-700 px-3 py-1.5 rounded-md text-sm transition-colors disabled:opacity-50">
            <x-heroicon-o-document-text class="w-4 h-4"/>
            <span x-text="isBusy && busyAction === 'draft' ? 'Saving…' : 'Save Draft'"></span>
        </button>

        {{-- Publish (show when not published) --}}
        <button x-show="currentStatus !== 'published'"
                @click="publish()"
                :disabled="isBusy"
                class="inline-flex items-center gap-1.5 bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-md text-sm font-medium transition-colors disabled:opacity-50">
            <x-heroicon-o-globe-alt class="w-4 h-4"/>
            <span x-text="isBusy && busyAction === 'publish' ? 'Publishing…' : 'Publish'"></span>
        </button>

        {{-- Unpublish (show when published) --}}
        <button x-show="currentStatus === 'published'"
                @click="unpublish()"
                :disabled="isBusy"
                class="inline-flex items-center gap-1.5 border border-amber-300 text-amber-700 hover:bg-amber-50 px-3 py-1.5 rounded-md text-sm transition-colors disabled:opacity-50">
            <x-heroicon-o-eye-slash class="w-4 h-4"/>
            <span x-text="isBusy && busyAction === 'unpublish' ? 'Unpublishing…' : 'Unpublish'"></span>
        </button>

        {{-- Archive --}}
        <button x-show="currentStatus !== 'archived'"
                @click="archive()"
                :disabled="isBusy"
                class="inline-flex items-center gap-1.5 border border-gray-200 text-gray-500 hover:bg-gray-50 px-3 py-1.5 rounded-md text-sm transition-colors disabled:opacity-50">
            <x-heroicon-o-archive-box class="w-4 h-4"/>
            Archive
        </button>

        {{-- Inline feedback --}}
        <span x-show="feedbackMsg"
              x-text="feedbackMsg"
              :class="feedbackType === 'error' ? 'text-red-600' : 'text-green-600'"
              class="text-xs font-medium transition-all">
        </span>
    </div>

    {{-- Auto-save status + Revision History --}}
    <div class="flex items-center gap-3 shrink-0">
        <span x-show="autoSaveLabel"
              x-text="autoSaveLabel"
              class="text-xs text-gray-400 hidden sm:inline"></span>

        <a href="{{ route('admin.website-management.pages.revisions.index', $page->unique_id) }}"
           class="inline-flex items-center gap-1.5 text-xs text-blue-600 hover:text-blue-800 hover:underline transition-colors">
            <x-heroicon-o-clock class="w-3.5 h-3.5"/>
            Revision History
        </a>
    </div>
</div>

<script>
(function () {
    // Expose the component factory so callers can reference it
    window._publishToolbarConfig = {
        pageUniqueId: '{{ $page->unique_id }}',
        csrfToken:    '{{ csrf_token() }}',
        initialStatus:'{{ $page->publish_status }}',
        urls: {
            publish:   '{{ route("admin.website-management.pages.publish",   $page->unique_id) }}',
            unpublish: '{{ route("admin.website-management.pages.unpublish", $page->unique_id) }}',
            archive:   '{{ route("admin.website-management.pages.archive",   $page->unique_id) }}',
            saveDraft: '{{ route("admin.website-management.pages.save-draft",$page->unique_id) }}',
            autoSave:  '{{ route("admin.website-management.pages.auto-save", $page->unique_id) }}',
        },
    };
})();

function publishToolbar() {
    const cfg = window._publishToolbarConfig;

    return {
        currentStatus: cfg.initialStatus,
        isBusy:        false,
        busyAction:    '',
        feedbackMsg:   '',
        feedbackType:  'success',
        autoSaveLabel: '',
        autoSaveTimer: null,

        get statusLabel() {
            return { published: '● Live', draft: '○ Draft', archived: '◎ Archived' }[this.currentStatus] ?? this.currentStatus;
        },

        init() {
            window.publishToolbar = this;
            this.startAutoSave();
        },

        // ── Public API ──────────────────────────────────────────────────
        saveDraft(summary) {
            return this._post(cfg.urls.saveDraft, { change_summary: summary ?? '' }, 'draft')
                .then(d => { if (d.success) this._feedback('Draft saved — Revision #' + d.revision_number); });
        },

        publish() {
            return this._post(cfg.urls.publish, {}, 'publish')
                .then(d => {
                    if (d.success) {
                        this.currentStatus = 'published';
                        this._feedback('Page is now live ✓');
                    }
                });
        },

        unpublish() {
            return this._post(cfg.urls.unpublish, {}, 'unpublish')
                .then(d => {
                    if (d.success) {
                        this.currentStatus = 'draft';
                        this._feedback('Page taken offline.');
                    }
                });
        },

        archive() {
            if (!confirm('Archive this page? It will be taken offline.')) return;
            return this._post(cfg.urls.archive, {}, 'archive')
                .then(d => {
                    if (d.success) {
                        this.currentStatus = 'archived';
                        this._feedback('Page archived.');
                    }
                });
        },

        startAutoSave(intervalMs = 60000) {
            this.stopAutoSave();
            this.autoSaveTimer = setInterval(() => this._autoSaveTick(), intervalMs);
        },

        stopAutoSave() {
            if (this.autoSaveTimer) clearInterval(this.autoSaveTimer);
        },

        // ── Private ─────────────────────────────────────────────────────
        _autoSaveTick() {
            fetch(cfg.urls.autoSave, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': cfg.csrfToken },
                body: JSON.stringify({ data: null }),
            }).then(r => r.json()).then(d => {
                if (d.success) this.autoSaveLabel = d.label;
            }).catch(() => {});
        },

        _post(url, data, action) {
            this.isBusy      = true;
            this.busyAction  = action;
            this.feedbackMsg = '';
            return fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': cfg.csrfToken },
                body: JSON.stringify(data),
            }).then(r => r.json()).then(d => {
                this.isBusy     = false;
                this.busyAction = '';
                if (!d.success) this._feedback(d.message ?? 'An error occurred.', 'error');
                return d;
            }).catch(err => {
                this.isBusy = false;
                this._feedback('Network error. Try again.', 'error');
                return { success: false };
            });
        },

        _feedback(msg, type = 'success') {
            this.feedbackMsg  = msg;
            this.feedbackType = type;
            setTimeout(() => { this.feedbackMsg = ''; }, 4000);
        },
    };
}
</script>
