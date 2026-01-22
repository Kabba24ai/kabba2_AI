@php
    $selectedIds = old('options', isset($objProduct) ? $objProduct->options->pluck('id')->toArray() : []);
@endphp

<!-- Options Section -->
<div class="border border-gray-200 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 p-6 space-y-4">
    <h3 class="text-sm font-semibold text-gray-800 dark:text-white border-b pb-2 mb-4">Options</h3>

    {{-- options multi‐select --}}
    <select id="product-options" name="options[]" multiple data-placeholder="Select options"
        class="choices-select w-full rounded-md border">
        @foreach ($options as $opt)
            <option value="{{ $opt->id }}" data-type="{{ $opt->type }}" @selected(in_array($opt->id, $selectedIds))>
                {{ $opt->name }} &mdash; <small>{{ ucfirst($opt->type) }}</small>
            </option>
        @endforeach
    </select>
    @error('options')
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
    @error('options.*')
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror

    <!-- Info Placeholder -->
    <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-md p-6 text-center mt-5">
        <div id="option-preview-tables" class="space-y-4 mt-4">
            <p class="text-gray-700 dark:text-gray-200 font-medium">
                Options section will be populated from separate Options Library
            </p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                This will show rental options with 4 price inputs for different durations
            </p>
        </div>
    </div>
</div>

<div id="comment-modal"
    class="fixed inset-0 hidden z-[999999] items-center justify-center overflow-y-auto bg-black/40 px-4 py-10 h-full">
    <div id="modal-content"
        class="relative bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full max-w-lg p-6 space-y-5 border border-gray-200 dark:border-gray-700">

        <!-- Close Icon (Heroicon) -->
        <button type="button" id="close-comment-modal"
            class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 dark:hover:text-white transition"
            aria-label="Close">
            <x-heroicon-o-x-mark class="w-5 h-5" />
        </button>

        <h2 class="text-md font-semibold text-gray-800 dark:text-white">
            Option Comment & Buttons
        </h2>

        <div class="text-sm text-blue-700 bg-blue-50 border border-blue-200 rounded p-3">
            <strong>Logic:</strong> If there is no comment added to a Pre-Checked option, then the pop-up does not
            appear when the customer unchecks the option. Only if there is a comment does the comment pop-up appear.
        </div>

        <div>
            <label class="text-sm font-medium text-gray-700 dark:text-gray-200 block mb-1 mt-4">
                Customer Pop-up Preview:
            </label>
            <div id="preview-text" class="mb-3 text-gray-800 dark:text-gray-100"></div>
            <div class="flex gap-2">
                <span id="preview-decline" class="bg-red-600 text-white text-sm px-3 py-1 rounded font-medium"></span>
                <span id="preview-accept" class="bg-green-600 text-white text-sm px-3 py-1 rounded font-medium"></span>
            </div>
        </div>
    </div>
</div>

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectEl = document.getElementById('product-options');
            const radios = document.querySelectorAll('input[name="product_type"]');
            if (!selectEl) return;

            // 1) Snapshot all original <option>s from the DOM
            const allOptions = Array.from(selectEl.querySelectorAll('option')).map(opt => ({
                value: opt.value,
                label: opt.textContent.trim(),
                type: opt.dataset.type, // "rental" or "retail"
                selected: opt.selected
            }));

            // 2) Try to grab an existing Choices instance…
            let choices = null;
            if (window.Choices && typeof window.Choices.getInstance === 'function') {
                choices = window.Choices.getInstance(selectEl);
            }
            // …or create one if missing
            if (!choices) {
                if (!window.Choices) {
                    console.error('Choices.js not found');
                    return;
                }
                choices = new window.Choices(selectEl, {
                    removeItemButton: true,
                    shouldSort: false
                });
            }

            // 3) Filtering function
            function filterChoices(type) {
                // completely clear out both choices & selected items
                choices.clearStore();

                // pick only matching items, preserving any initial `selected` flags
                const subset = allOptions
                    .filter(o => o.type === type)
                    .map(o => ({
                        value: o.value,
                        label: o.label,
                        selected: o.selected
                    }));

                // re-feed them in one go
                choices.setChoices(subset, 'value', 'label', true);
            }

            // 4) Wire up your radios
            radios.forEach(radio => {
                radio.addEventListener('change', e => {
                    filterChoices(e.target.value);
                });
            });

            // 5) Initial pass on page load
            const init = Array.from(radios).find(r => r.checked);
            if (init) {
                filterChoices(init.value);
            }

            const selectedIds = @json($selectedIds ?? []);
            const container = document.getElementById('option-preview-tables');

            if (selectedIds.length > 0) {
                renderOptionPreviews(selectedIds);
            }

            async function fetchAndRenderOptionPreview(id) {
                if (!id) return '';

                const optionUrl = '{{ route('admin.product-management.products.fetch-options', ':optionId') }}'
                    .replace(':optionId', id);

                try {
                    const response = await fetch(optionUrl);
                    const result = await response.json();
                    return result.html; // Make sure your controller returns 'html' key
                } catch (error) {
                    console.error('Error loading option preview:', error);
                    notyf.error('Failed to load option preview');
                    return '';
                }
            }

            // 🔁 Reusable function to render previews
            async function renderOptionPreviews(ids = []) {
                container.innerHTML = '';

                // Show loading
                const loading = document.createElement('div');
                loading.id = 'loading-indicator';
                loading.innerHTML = '<p>Loading previews...</p>';
                container.appendChild(loading);

                for (const id of ids) {
                    const html = await fetchAndRenderOptionPreview(id);
                    if (!html) continue;
                    container.insertAdjacentHTML('beforeend', html);
                }

                // Remove loading
                document.getElementById('loading-indicator')?.remove();
            }

            // 🔁 Call it again when options are changed by user
            selectEl.addEventListener('change', () => {
                const changedIds = Array.from(selectEl.selectedOptions).map(opt => opt.value);
                renderOptionPreviews(changedIds);
            });


            const modal = document.getElementById('comment-modal');
            const previewText = document.getElementById('preview-text');
            const previewAccept = document.getElementById('preview-accept');
            const previewDecline = document.getElementById('preview-decline');

            document.addEventListener('click', function(e) {
                const btn = e.target.closest('.comment-btn');
                if (!btn) return;

                if (btn.disabled || btn.classList.contains('cursor-not-allowed')) return;

                const modal = document.getElementById('comment-modal');
                const previewText = document.getElementById('preview-text');
                const previewAccept = document.getElementById('preview-accept');
                const previewDecline = document.getElementById('preview-decline');

                const comment = btn.dataset.comment || '';
                const accept = btn.dataset.accept || 'Keep Safety Harness';
                const decline = btn.dataset.decline || "No Thanks, I'll Take The Risk";

                previewText.textContent = comment;
                previewAccept.textContent = accept;
                previewDecline.textContent = decline;

                modal.classList.remove('hidden');
                modal.classList.add('flex');
            });


            // Optional: close modal on outside click or Esc key
            document.addEventListener('keydown', e => {
                if (e.key === 'Escape') modal.classList.add('hidden');
            });
            modal.addEventListener('click', e => {
                if (e.target === modal) modal.classList.add('hidden');
            });

            document.getElementById('close-comment-modal')?.addEventListener('click', () => {
                document.getElementById('comment-modal').classList.add('hidden');
            });

        });
    </script>
@endpush
