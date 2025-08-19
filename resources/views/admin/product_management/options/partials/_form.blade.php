{{-- Rental Options List Form Partial --}}

<div class="grid grid-cols-1 md:grid-cols-8 gap-6 mb-8">
    {{-- Name --}}
    <div class="col-span-2">
        {{ html()->label('Name', 'name')->class('block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300 required') }}
        {{ html()->text('name', old('name'))->id('name')->class([
                'w-full rounded-lg border px-4 py-2 text-sm shadow-sm dark:bg-gray-900 dark:text-white',
                'border-red-500' => $errors->has('name'),
            ])->attributes([
                'maxlength' => 240,
                'data-parsley-required' => 'true',
                'data-parsley-maxlength' => 240,
                'autocomplete' => 'off',
                'placeholder' => 'Enter Name',
            ]) }}
        @error('name')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- Description --}}
    <div class="col-span-4">
        {{ html()->label('Description', 'description')->class('block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300') }}
        {{ html()->text('description', old('description'))->id('description')->attributes([
                'maxlength' => 500,
                'data-parsley-maxlength' => 500,
                'placeholder' => 'Enter Description',
            ])->class([
                'w-full rounded-lg border px-4 py-2 text-sm shadow-sm dark:bg-gray-900 dark:text-white',
                'border-red-500' => $errors->has('description'),
            ]) }}
        @error('description')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    @php
        $formOptions = old(
            'options',
            isset($objProductOption) && $objProductOption->items
                ? json_decode(json_encode($objProductOption->items), true)
                : [],
        );

        $formErrors = $errors->getMessages();

        $selectedType = old('type', isset($objProductOption) ? $objProductOption->type : 'Rental');
    @endphp

    <!-- Type Selector -->
    <div class="col-span-1">
        <label for="type"
            class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300 required">Type</label>
        <select name="type" id="type"
            class="w-full rounded-lg border px-4 py-2 text-sm shadow-sm dark:bg-gray-900 dark:text-white @error('type') border-red-500 @enderror"
            required>
            <option value="">Select Type</option>
            <option value="Rental" {{ $selectedType === 'Rental' ? 'selected' : '' }}>Rental</option>
            <option value="Retail" {{ $selectedType === 'Retail' ? 'selected' : '' }}>Retail</option>
        </select>
        @error('type')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- Status --}}
    <div class="col-span-1">
        {{ html()->label('Status', 'status')->class('block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300 required') }}
        {{ html()->select('status', ['Active' => 'Active', 'Inactive' => 'Inactive'], old('status', $objProductOption->status ?? 'Active'))->id('status')->class([
                'w-full rounded-lg border px-4 py-2 text-sm shadow-sm dark:bg-gray-900 dark:text-white',
                'border-red-500' => $errors->has('status'),
            ])->required() }}
        @error('status')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>
</div>

<!-- Options Table -->
<div id="options-wrapper" class="border border-gray-200 dark:border-gray-800 rounded-md overflow-x-auto mt-6"
    data-options='@json($formOptions)' data-errors='@json($formErrors)'
    data-type="{{ $selectedType }}">

    <div class="p-4">
        <h4 class="font-medium text-gray-800 dark:text-white mb-1">Options</h4>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
            Add and configure individual options for this list.
        </p>

        <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
            <thead id="options-head"></thead>
            <tbody id="options-body" class="bg-white dark:bg-gray-950"></tbody>
        </table>

        <button type="button" id="add-option" class="mt-3 text-blue-600 hover:underline text-sm font-medium">
            + Add new row
        </button>
    </div>
</div>

<!-- Comment Modal -->
<div id="comment-modal"
    class="fixed inset-0 hidden z-[99999] flex items-center justify-center overflow-y-auto bg-black/40 px-4 py-10">
    <div id="modal-content"
        class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full max-w-lg p-6 space-y-5 border border-gray-200 dark:border-gray-700">
    </div>
</div>


@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const wrapper = document.getElementById('options-wrapper');
            const tbody = document.getElementById('options-body');
            const thead = document.getElementById('options-head');
            const addBtn = document.getElementById('add-option');
            const modal = document.getElementById('comment-modal');
            const modalContent = document.getElementById('modal-content');
            const typeSelect = document.getElementById('type');
            const dataErrors = JSON.parse(wrapper.dataset.errors || '{}');

            if (Object.keys(dataErrors).length > 0) {
                Object.entries(dataErrors).forEach(([field, messages]) => {
                    messages.forEach(msg => notyf.error(msg));
                });
            }

            let options = JSON.parse(wrapper.dataset.options || '[]');
            options = options.map(opt => ({
                ...opt,
                comment: (opt.comment === null || opt.comment === 'null') ? '' : opt.comment,
                accept_label: (opt.accept_label === null || opt.accept_label === 'null') ? '' : opt
                    .accept_label,
                decline_label: (opt.decline_label === null || opt.decline_label === 'null') ? '' : opt
                    .decline_label
            }));

            const errors = JSON.parse(wrapper.dataset.errors || '{}');
            let selectedType = wrapper.dataset.type;
            let currentIndex = null;

            if (!options.length) {
                for (let i = 0; i < 4; i++) {
                    options.push(createBlankOption());
                }
            }

            typeSelect.addEventListener('change', (e) => {
                selectedType = e.target.value;
                wrapper.dataset.type = selectedType;
                renderOptions();
            });

            function createBlankOption() {
                return {
                    id: null, // <-- NEW: mark as new (no DB id yet)
                    key: Date.now() + Math.random(),
                    label: '',
                    daily: 0,
                    weekend: 0,
                    weekly: 0,
                    monthly: 0,
                    retail_price: 0,
                    charged: 'Unlimited',
                    value: 'Blank',
                    comment: '',
                    decline_label: "No Thanks, I'll Take The Risk",
                    accept_label: "Keep Safety Harness"
                };
            }


            function renderHead() {
                thead.innerHTML = `
            <tr class="bg-gray-100 dark:bg-gray-800 text-xs uppercase">
                <th class="px-3 py-2 w-6">#</th>
                <th class="px-3 py-2 w-8">Drag</th>
                <th class="px-3 py-2 w-48">Label</th>
                ${selectedType === 'Rental' ? `
                                                            <th class="px-3 py-2 w-20">Daily</th>
                                                            <th class="px-3 py-2 w-20">W/E Spcl.</th>
                                                            <th class="px-3 py-2 w-20">Weekly</th>
                                                            <th class="px-3 py-2 w-20">Monthly</th>
                                                        ` : `
                                                            <th colspan="4" class="px-3 py-2">Retail Price</th>
                                                        `}
                <th class="px-3 py-2 w-36">Charged Per Order</th>
                <th class="px-3 py-2 w-32">Value</th>
                <th class="px-3 py-2 w-12">Comment</th>
                <th class="px-3 py-2 w-10">Actions</th>
            </tr>
        `;
            }

            function syncInputsToOptions() {
                tbody.querySelectorAll('tr').forEach((row, i) => {
                    if (!options[i]) return;
                    options[i].label = row.querySelector(`input[name="options[${i}][label]"]`)?.value || '';
                    if (selectedType === 'Rental') {
                        options[i].daily = row.querySelector(`input[name="options[${i}][daily]"]`)?.value ||
                            0;
                        options[i].weekend = row.querySelector(`input[name="options[${i}][weekend]"]`)
                            ?.value || 0;
                        options[i].weekly = row.querySelector(`input[name="options[${i}][weekly]"]`)
                            ?.value || 0;
                        options[i].monthly = row.querySelector(`input[name="options[${i}][monthly]"]`)
                            ?.value || 0;
                    } else {
                        options[i].retail_price = row.querySelector(
                            `input[name="options[${i}][retail_price]"]`)?.value || 0;
                    }
                    options[i].charged = row.querySelector(`select[name="options[${i}][charged]"]`)
                        ?.value || 'Unlimited';
                    options[i].value = row.querySelector(`select[name="options[${i}][value]"]`)?.value ||
                        'Blank';
                    // comment, decline_label, accept_label are handled by modal, so we leave them
                });
            }


            function renderOptions() {
                renderHead();
                tbody.innerHTML = '';
                options.forEach((opt, index) => {
                    const commentBtnColor = opt.value !== 'Checked' ?
                        'text-gray-300 cursor-not-allowed' :
                        (opt.comment ? 'text-blue-600' : 'text-gray-400');

                    const row = document.createElement('tr');
                    row.className = 'border-t border-gray-100 dark:border-gray-800';

                    const error = getError(`options.${index}.label`);

                    row.innerHTML = `
                <td class="px-3 py-2">
                    ${index + 1}
                    <input type="hidden" name="options[${index}][id]" value="${opt.id ?? ''}">
                    <input type="hidden" name="options[${index}][sort_order]" value="${index}">
                </td>

                <td class="px-3 py-2 text-center cursor-move">⋮⋮</td>
                <td class="px-3 py-2">
                    <input type="text" name="options[${index}][label]" value="${opt.label}"
                        class="w-full rounded-md border px-2 py-1 text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        placeholder="Option Name" required />
                    ${error}
                </td>

                ${selectedType === 'Rental' ? `
                                                            <td class="px-3 py-2"><input type="number" name="options[${index}][daily]" value="${opt.daily}" class="w-full rounded-md border border-gray-300 dark:border-gray-700 px-2 py-1 text-sm bg-white dark:bg-gray-800 text-gray-800 dark:text-white" /></td>
                                                            <td class="px-3 py-2"><input type="number" name="options[${index}][weekend]" value="${opt.weekend}" class="w-full rounded-md border border-gray-300 dark:border-gray-700 px-2 py-1 text-sm bg-white dark:bg-gray-800 text-gray-800 dark:text-white" /></td>
                                                            <td class="px-3 py-2"><input type="number" name="options[${index}][weekly]" value="${opt.weekly}" class="w-full rounded-md border border-gray-300 dark:border-gray-700 px-2 py-1 text-sm bg-white dark:bg-gray-800 text-gray-800 dark:text-white" /></td>
                                                            <td class="px-3 py-2"><input type="number" name="options[${index}][monthly]" value="${opt.monthly}" class="w-full rounded-md border border-gray-300 dark:border-gray-700 px-2 py-1 text-sm bg-white dark:bg-gray-800 text-gray-800 dark:text-white" /></td>
                                                        ` : `
                                                            <td colspan="4" class="px-3 py-2"><input type="number" name="options[${index}][retail_price]" value="${opt.retail_price}" class="w-full rounded-md border border-gray-300 dark:border-gray-700 px-2 py-1 text-sm bg-white dark:bg-gray-800 text-gray-800 dark:text-white" /></td>
                                                        `}

                <td class="px-3 py-2">
                    <select name="options[${index}][charged]" class="w-full rounded-md border border-gray-300 dark:border-gray-700 px-2 py-1 text-sm bg-white dark:bg-gray-800 text-gray-800 dark:text-white">
                        <option value="Unlimited"${opt.charged === 'Unlimited' ? ' selected' : ''}>Unlimited</option>
                        <option value="1 Time Max"${opt.charged === '1 Time Max' ? ' selected' : ''}>1 Time Max</option>
                    </select>
                </td>

                <td class="px-3 py-2">
                    <select name="options[${index}][value]" class="w-full rounded-md border border-gray-300 dark:border-gray-700 px-2 py-1 text-sm bg-white dark:bg-gray-800 text-gray-800 dark:text-white" data-index="${index}">
                        <option value="Blank"${opt.value === 'Blank' ? ' selected' : ''}>Blank</option>
                        <option value="Checked"${opt.value === 'Checked' ? ' selected' : ''}>Checked</option>
                    </select>
                </td>

                <td class="px-3 py-2 text-center">
                    <button type="button" class="comment-btn ${commentBtnColor} transition hover:text-blue-700" data-index="${index}" ${opt.value !== 'Checked' ? 'disabled' : ''}>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 5a2 2 0 012-2h12a2 2 0 012 2v8a2 2 0 01-2 2H6l-4 4V5z" />
                        </svg>
                    </button>
                    <input type="hidden" name="options[${index}][comment]" value="${opt.comment}">
                    <input type="hidden" name="options[${index}][accept_label]" value="${opt.accept_label}">
                    <input type="hidden" name="options[${index}][decline_label]" value="${opt.decline_label}">
                </td>

                <td class="px-3 py-2 text-center text-red-600 cursor-pointer remove-btn" data-index="${index}">✕</td>
            `;

                    tbody.appendChild(row);
                });

                attachEventListeners();
            }

            function getError(path) {
                return errors[path] ? `<p class="text-xs text-red-600 mt-1">${errors[path][0]}</p>` : '';
            }

            function attachEventListeners() {
                tbody.querySelectorAll('.remove-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        syncInputsToOptions();
                        const index = parseInt(btn.dataset.index);
                        options.splice(index, 1);
                        renderOptions();
                    });
                });

                tbody.querySelectorAll('.comment-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const index = parseInt(btn.dataset.index);
                        currentIndex = index;
                        showCommentModal(index);
                    });
                });

                tbody.querySelectorAll('select[name$="[value]"]').forEach(select => {
                    select.addEventListener('change', () => {
                        syncInputsToOptions();
                        const index = parseInt(select.dataset.index);
                        options[index].value = select.value;
                        renderOptions();
                    });
                });
            }

            function showCommentModal(index) {
                const opt = options[index];

                modalContent.innerHTML = `
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Option Comment & Buttons</h3>

        <div class="bg-blue-50 text-blue-700 border border-blue-200 p-3 text-sm rounded dark:bg-blue-900 dark:text-blue-200 dark:border-blue-700">
            <strong>Logic:</strong> If there is no comment added to a Pre-Checked option, then the pop-up does not appear when the customer unchecks the option. Only if there is a comment does the comment pop-up appear.
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Comment Text</label>
            <textarea id="modal-comment" rows="3" class="w-full rounded-md border px-3 py-2 text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                placeholder="Enter Comment Text...">${opt.comment}</textarea>
            <p class="text-xs text-gray-500 mt-1">Leave blank to disable the pop-up confirmation for this option.</p>
        </div>

        <div id="no-popup-warning" class="hidden bg-yellow-100 text-yellow-800 p-3 rounded text-sm dark:bg-yellow-900 dark:text-yellow-200 mt-2">
            <strong>No Pop-up:</strong> Since there's no comment, customers can uncheck this option without any confirmation pop-up.
        </div>

        <div id="comment-preview" class="hidden space-y-4 mt-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Decline Button Text (Red)</label>
                    <input id="modal-decline" type="text" class="w-full rounded-md border px-3 py-2 text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white" value="${opt.decline_label}" placeholder="No Thanks, I'll Take The Risk">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Accept Button Text (Green)</label>
                    <input id="modal-accept" type="text" class="w-full rounded-md border px-3 py-2 text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white" value="${opt.accept_label}" placeholder="Keep Safety Harness">
                </div>
            </div>

            <div class="border border-gray-200 dark:border-gray-700 rounded-md p-4 mt-2 bg-white dark:bg-gray-800">
                <p class="text-sm text-gray-800 dark:text-white mb-2">Customer Pop-up Preview:</p>
                <div class="space-y-3">
                    <p id="preview-comment" class="text-sm text-gray-700 dark:text-gray-300">${opt.comment}</p>
                    <div class="flex gap-2">
                        <button type="button" id="preview-decline" class="bg-red-600 text-white px-4 py-1 rounded text-sm">
                            ${opt.decline_label}
                        </button>
                        <button type="button" id="preview-accept" class="bg-green-600 text-white px-4 py-1 rounded text-sm">
                            ${opt.accept_label}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-2 mt-6">
            <button type="button" id="modal-cancel" class="px-4 py-2 text-sm rounded border border-gray-300 bg-white dark:bg-gray-700 dark:text-white">Cancel</button>
            <button type="button" id="modal-save" class="px-4 py-2 text-sm rounded bg-blue-600 text-white hover:bg-blue-700">Save Comment</button>
        </div>
    `;

                const commentInput = modalContent.querySelector('#modal-comment');
                const declineInput = modalContent.querySelector('#modal-decline');
                const acceptInput = modalContent.querySelector('#modal-accept');

                const previewSection = modalContent.querySelector('#comment-preview');
                const warning = modalContent.querySelector('#no-popup-warning');
                const previewText = modalContent.querySelector('#preview-comment');
                const previewDecline = modalContent.querySelector('#preview-decline');
                const previewAccept = modalContent.querySelector('#preview-accept');

                function updatePreview() {
                    const val = commentInput.value.trim();
                    const decline = declineInput.value.trim() || 'No Thanks';
                    const accept = acceptInput.value.trim() || 'Yes, Keep It';

                    if (val === '') {
                        previewSection.classList.add('hidden');
                        warning.classList.remove('hidden');
                    } else {
                        previewSection.classList.remove('hidden');
                        warning.classList.add('hidden');
                        previewText.innerText = val;
                        previewDecline.innerText = decline;
                        previewAccept.innerText = accept;
                    }
                }

                commentInput.addEventListener('input', updatePreview);
                declineInput.addEventListener('input', updatePreview);
                acceptInput.addEventListener('input', updatePreview);

                document.getElementById('modal-cancel').onclick = () => modal.classList.add('hidden');
                document.getElementById('modal-save').onclick = () => {
                    opt.comment = commentInput.value.trim();
                    opt.decline_label = declineInput.value.trim();
                    opt.accept_label = acceptInput.value.trim();
                    modal.classList.add('hidden');
                    renderOptions();
                };

                updatePreview();
                modal.classList.remove('hidden');
            }


            addBtn.addEventListener('click', () => {
                syncInputsToOptions();
                options.push(createBlankOption());
                renderOptions();
            });


            new Sortable(tbody, {
                handle: '.cursor-move',
                animation: 150,
                onEnd: evt => {
                    syncInputsToOptions();
                    const moved = options.splice(evt.oldIndex, 1)[0];
                    options.splice(evt.newIndex, 0, moved);
                    options.forEach((opt, index) => {
                        opt.sort_order = index;
                    });
                    renderOptions();
                }
            });



            renderOptions();
        });
    </script>
@endpush
