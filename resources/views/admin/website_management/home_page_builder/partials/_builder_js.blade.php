{{-- ── Shared builder JS ─────────────────────────────────────────────
     Used by Home Page Builder and Website Management → Footer (which
     hosts the global Footer + Feature Strip editors). Expects a
     $components collection so it can init each component's sortables.
     Include inside a @push('js') block. --}}
<script>
window.HPBuilder = {
    sortUrl:        @js(route('admin.website-management.home-builder.item.sort')),
    sectionSortUrl: @js(route('admin.website-management.home-builder.section.sort')),
    csrf:           @js(csrf_token()),
};

/* Image preview — show live thumbnail when a file is chosen */
function hpPreviewImage(input, previewId, placeholderId) {
    if (!input.files || !input.files[0]) return;
    var reader = new FileReader();
    reader.onload = function (e) {
        var preview = document.getElementById(previewId);
        var holder  = placeholderId ? document.getElementById(placeholderId) : null;
        if (preview) { preview.src = e.target.result; preview.classList.remove('hidden'); }
        if (holder)  { holder.classList.add('hidden'); }
    };
    reader.readAsDataURL(input.files[0]);
}

/* Unsaved changes warning */
(function () {
    var dirty = false;
    document.addEventListener('input',  function (e) { if (e.target.closest('[data-track-changes]')) dirty = true; });
    document.addEventListener('change', function (e) { if (e.target.closest('[data-track-changes]')) dirty = true; });
    document.addEventListener('submit', function (e) { if (e.target.closest('[data-track-changes]')) dirty = false; });
    window.addEventListener('beforeunload', function (e) {
        if (dirty) { e.preventDefault(); e.returnValue = 'You have unsaved changes.'; }
    });
})();

/* Section-level drag & drop (section manager panel) */
function hpInitSectionSortable(containerId) {
    var el = document.getElementById(containerId);
    if (!el || !window.Sortable) return;
    new window.Sortable(el, {
        handle:     '.section-drag-handle',
        animation:  150,
        ghostClass: 'opacity-40',
        dragClass:  'shadow-lg',
        onEnd: function () {
            var sections = Array.from(el.querySelectorAll('[data-section-id]')).map(function (row, idx) {
                return { id: row.dataset.sectionId, order: idx + 1 };
            });
            fetch(window.HPBuilder.sectionSortUrl, {
                method:  'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.HPBuilder.csrf,
                    'Accept':       'application/json',
                },
                body: JSON.stringify({ sections: sections }),
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success && window.notyf) window.notyf.success('Section order saved');
            })
            .catch(function () {
                if (window.notyf) window.notyf.error('Failed to save section order.');
            });
        },
    });
}

/* Item-level drag & drop sorting */
function hpInitSortable(containerId) {
    var el = document.getElementById(containerId);
    if (!el || !window.Sortable) return;
    new window.Sortable(el, {
        handle:     '.drag-handle',
        animation:  150,
        ghostClass: 'opacity-40',
        dragClass:  'shadow-lg',
        onEnd: function () {
            var items = Array.from(el.querySelectorAll('[data-item-id]')).map(function (row, idx) {
                return { id: row.dataset.itemId, order: idx + 1 };
            });
            fetch(window.HPBuilder.sortUrl, {
                method:  'POST',
                headers: {
                    'Content-Type':  'application/json',
                    'X-CSRF-TOKEN':  window.HPBuilder.csrf,
                    'Accept':        'application/json',
                },
                body: JSON.stringify({ items: items }),
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success && window.notyf) window.notyf.success('Order saved');
            })
            .catch(function () {
                if (window.notyf) window.notyf.error('Failed to save order — please refresh.');
            });
        },
    });
}

/* Init sortables after Alpine finishes rendering (small delay) */
document.addEventListener('DOMContentLoaded', function () {
    setTimeout(function () {
        hpInitSectionSortable('section-manager-cards');
        @json($components->flatMap(fn($c) => $c->sortableIds())->values()->all()).forEach(hpInitSortable);
    }, 300);
});
</script>
