@php
    $selectedIds = old(
        'options',
        isset($objProduct) ? $objProduct->options->pluck('id')->toArray() : [],
    );
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

    <!-- Info Placeholder -->
    <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-md p-6 text-center">
        <p class="text-gray-700 dark:text-gray-200 font-medium">
            Options section will be populated from separate Options Library
        </p>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            This will show rental options with 4 price inputs for different durations
        </p>
    </div>
</div>


{{-- <div x-data="$store.optionsStore" class="space-y-6">
    <template x-for="(group, groupIndex) in optionGroups" :key="groupIndex">
        <div class="border rounded-lg bg-white dark:bg-gray-800 shadow-sm">
            <!-- Header -->
            <div class="bg-blue-50 dark:bg-blue-900 px-4 py-2 flex justify-between items-center">
                <span class="text-blue-700 font-semibold text-sm">
                    <template x-text="'#' + (groupIndex + 1) + ' ' + group.name"></template>
                </span>
                <button @click="removeGroup(groupIndex)" class="text-gray-500 hover:text-red-600">
                    <x-heroicon-o-trash class="w-5 h-5" />
                </button>
            </div>

            <!-- Name + Type -->
            <div class="p-4 grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                    <input type="text" x-model="group.name"
                        class="w-full rounded-md border border-gray-300 focus:border-blue-500 focus:ring-2 shadow-sm text-sm px-3 py-2 dark:bg-gray-900 dark:text-white" />
                </div>
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Type</label>
                    <input type="text" x-model="group.type"
                        class="w-full rounded-md border border-gray-300 focus:border-blue-500 focus:ring-2 shadow-sm text-sm px-3 py-2 dark:bg-gray-900 dark:text-white" />
                </div>
            </div>

            <!-- Option Rows Table -->
            <div class="p-4 overflow-x-auto">
                <table class="w-full text-sm border-t border-gray-200 dark:border-gray-700">
                    <thead>
                        <tr class="text-left text-gray-600 dark:text-gray-300">
                            <th class="py-2 pr-4 w-6"></th>
                            <th class="py-2 pr-4">Label</th>
                            <th class="py-2 pr-4">Price</th>
                            <th class="py-2 pr-4">Value</th>
                            <th class="py-2 pr-4">Comment</th>
                            <th class="w-10"></th>
                        </tr>
                    </thead>
                    <tbody x-init="$nextTick(() => initSortable($el, groupIndex))" class="sortable-group">
                        <template x-for="(row, rowIndex) in group.options" :key="rowIndex">
                            <tr class="group cursor-move">
                                <td class="py-2 pr-4 align-top">
                                    <div
                                        class="flex items-center justify-center text-gray-400 group-hover:text-gray-600">
                                        <x-heroicon-o-bars-3 class="w-4 h-4" />
                                    </div>
                                </td>
                                <td class="py-2 pr-4">
                                    <input type="text" x-model="row.label"
                                        class="w-full rounded-md border border-gray-300 focus:border-blue-500 focus:ring-2 px-2 py-1 text-sm dark:bg-gray-900 dark:text-white" />
                                </td>
                                <td class="py-2 pr-4">
                                    <input type="number" min="0" step="0.01" x-model="row.price"
                                        class="w-full rounded-md border border-gray-300 focus:border-blue-500 focus:ring-2 px-2 py-1 text-sm dark:bg-gray-900 dark:text-white" />
                                </td>
                                <td class="py-2 pr-4">
                                    <select x-model="row.value"
                                        class="w-full rounded-md border border-gray-300 focus:border-blue-500 focus:ring-2 px-2 py-1 text-sm dark:bg-gray-900 dark:text-white">
                                        <option value="Checked">Checked</option>
                                        <option value="Blank">Blank</option>
                                    </select>
                                </td>
                                <td class="py-2 pr-4">
                                    <button type="button" @click="toggleComment(groupIndex, rowIndex)"
                                        class="inline-flex items-center gap-1 bg-cyan-500 hover:bg-cyan-600 text-white text-xs px-3 py-1 rounded">
                                        <x-heroicon-o-pencil-square class="w-4 h-4" />
                                        <span x-text="row.comment ? 'Edit Comment' : 'Add Comment'"></span>
                                    </button>
                                </td>
                                <td class="text-center">
                                    <button @click="removeRow(groupIndex, rowIndex)" type="button"
                                        class="text-gray-400 hover:text-red-600">
                                        <x-heroicon-o-trash class="w-5 h-5" />
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>

                <div class="mt-3">
                    <button @click="addRow(groupIndex)" type="button"
                        class="bg-blue-100 text-blue-700 hover:bg-blue-200 px-4 py-1 rounded text-sm font-medium">
                        Add new row
                    </button>
                </div>
            </div>
        </div>
    </template>

    <!-- Add New Option Group -->
    <div class="flex justify-between items-center mt-4">
        <select class="rounded-md border border-gray-300 text-sm px-3 py-1 dark:bg-gray-900 dark:text-white">
            <option>Select Retail Option</option>
        </select>
        <button @click="addGroup()" type="button"
            class="bg-blue-500 text-white hover:bg-blue-600 px-4 py-2 rounded text-sm font-medium">
            Add Retail Option
        </button>
    </div>
</div>


@push('js')
    <script>
        function initSortable(el, groupIndex) {
            if (!el) return;
            Sortable.create(el, {
                animation: 150,
                handle: '.cursor-move',
                onEnd: function(evt) {
                    const group = Alpine.store('optionsStore').optionGroups[groupIndex];
                    const [moved] = group.options.splice(evt.oldIndex, 1);
                    group.options.splice(evt.newIndex, 0, moved);
                }
            });
        }


        function productOptions() {
            return {
                optionGroups: [{
                    name: 'Boom Lift - 40\' Towable',
                    type: 'Retail',
                    options: [{
                            label: 'Prepaid Fuel',
                            price: 0,
                            value: 'Checked',
                            comment: ''
                        },
                        {
                            label: 'Harness - Rental',
                            price: 0,
                            value: 'Checked',
                            comment: ''
                        },
                        {
                            label: 'Harness - Buy',
                            price: 0,
                            value: 'Blank',
                            comment: ''
                        },
                        {
                            label: 'Damage Waiver',
                            price: 0,
                            value: 'Checked',
                            comment: ''
                        },
                    ]
                }],
                addGroup() {
                    this.optionGroups.push({
                        name: '',
                        type: '',
                        options: []
                    });
                },
                removeGroup(index) {
                    this.optionGroups.splice(index, 1);
                },
                addRow(groupIndex) {
                    this.optionGroups[groupIndex].options.push({
                        label: '',
                        price: 0,
                        value: 'Blank',
                        comment: ''
                    });
                },
                removeRow(groupIndex, rowIndex) {
                    this.optionGroups[groupIndex].options.splice(rowIndex, 1);
                },
                toggleComment(groupIndex, rowIndex) {
                    const current = this.optionGroups[groupIndex].options[rowIndex].comment;
                    const input = prompt('Enter comment:', current);
                    if (input !== null) {
                        this.optionGroups[groupIndex].options[rowIndex].comment = input;
                    }
                }
            };
        }

        document.addEventListener('alpine:init', () => {
            Alpine.store('optionsStore', productOptions());
        });
    </script>
@endpush --}}

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
        });
    </script>
@endpush
