@php
    $trailerCols = $trailerTypes;
    $truckCols   = $activeTruckTypes;   // only truck types with ≥1 active truck registered
    $saveUrl     = route('admin.order-management.dispatch.ai-rules.equipment-rule.save');
    $csrfToken   = csrf_token();
@endphp

<style>
/* Single source of truth for Equipment tab column widths.
   All category tables share these classes → columns stay identical. */
.eq-col-check   { width: 36px;  }
.eq-col-name    { width: 220px; }
.eq-col-brand   { width: 90px;  }
.eq-col-model   { width: 90px;  }
.eq-col-mincap  { width: 90px;  }
.eq-col-trailer { width: 95px;  }
.eq-col-truck   { width: 95px;  }
.eq-col-cdl     { width: 80px;  }
.eq-col-share   { width: 90px;  }
.eq-col-notes   { width: 80px;  }
.eq-col-save    { width: 70px;  }
</style>

@php

    // Short labels for truck type columns — full value stored in title attr
    $truckColLabel = [
        'Any'                => 'Any',
        '1/2 Ton'            => '½ Ton',
        '3/4 Ton'            => '¾ Ton',
        '1 Ton'              => '1 Ton',
        '1 Ton Dually'       => '1T Dually',
        '2 Ton Dually'       => '2T Dually',
        'Medium Duty'        => 'Med Duty',
        '26K Rollback'       => '26K R/B',
        '30 Series Rollback' => '30S R/B',
        '40 Series Rollback' => '40S R/B',
        'Lowboy'             => 'Lowboy',
    ];
@endphp

<div class="space-y-1">
    <div class="flex items-start justify-between gap-4 mb-4 flex-wrap">
        <div>
            <h2 class="text-lg font-semibold">Equipment Transport Rules</h2>
            <p class="text-sm text-gray-500">
                Define transport requirements per piece of equipment. AI uses these rules to match trailers and trucks to orders.
                Scroll the table horizontally to see all columns.
            </p>
        </div>
        <div class="flex items-center gap-2 shrink-0 mt-1">
            <label class="text-xs text-gray-500 font-medium whitespace-nowrap">Filter by Category:</label>
            <select id="eq-category-filter"
                class="border border-gray-300 rounded-lg py-1.5 px-3 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                <option value="all">All Categories</option>
                @foreach ($equipmentByCategory as $catId => $catGroup)
                    <option value="{{ $catId }}">{{ $catGroup->first()->productCategory?->title ?? 'Uncategorized' }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @forelse ($equipmentByCategory as $categoryId => $equipmentGroup)
        @php
            $firstEquipment = $equipmentGroup->first();
            $categoryTitle  = $firstEquipment->productCategory?->title ?? 'Uncategorized';
            $configuredCount = $equipmentGroup->filter(fn($e) => isset($equipmentRules[$e->id]))->count();
        @endphp

        <div class="bg-white rounded-xl shadow-sm border mb-4" data-category-id="{{ $categoryId }}">

            {{-- Category Header --}}
            <div class="flex items-center gap-3 px-5 py-3 border-b bg-gray-50 rounded-t-xl">
                <x-heroicon-o-wrench-screwdriver class="w-4 h-4 text-gray-500 shrink-0" />
                <span class="font-semibold text-sm">{{ $categoryTitle }}</span>
                <span class="text-xs text-gray-400">{{ $equipmentGroup->count() }} items</span>
                @if ($configuredCount > 0)
                    <span class="ml-auto text-xs text-green-600 font-medium bg-green-50 border border-green-200 px-2 py-0.5 rounded-full">
                        {{ $configuredCount }}/{{ $equipmentGroup->count() }} Configured
                    </span>
                @else
                    <span class="ml-auto text-xs text-gray-400 font-medium bg-gray-100 border border-gray-200 px-2 py-0.5 rounded-full">
                        No rules set
                    </span>
                @endif
            </div>

            {{-- Scrollable Table --}}
            <div class="overflow-x-auto">
                <table class="border-collapse text-xs" style="table-layout: fixed;">
                    <colgroup>
                        <col class="eq-col-check">
                        <col class="eq-col-name">
                        <col class="eq-col-brand">
                        <col class="eq-col-model">
                        <col class="eq-col-mincap">
                        @foreach ($trailerCols as $t)
                            <col class="eq-col-trailer">
                        @endforeach
                        @foreach ($truckCols as $tc)
                            <col class="eq-col-truck">
                        @endforeach
                        <col class="eq-col-cdl">
                        <col class="eq-col-share">
                        <col class="eq-col-notes">
                        <col class="eq-col-save">
                    </colgroup>
                    <thead>
                        {{-- Row 1: group spans --}}
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-0 py-2 text-center border-r border-gray-200">
                                <input type="checkbox" class="select-all-cat rounded border-gray-300 text-indigo-600"
                                    data-category="{{ $categoryId }}" title="Select all in category">
                            </th>
                            <th class="px-2 py-2 text-left text-gray-600 font-semibold border-r border-gray-200 text-xs">Equipment Name</th>
                            <th class="px-2 py-2 text-left text-gray-600 font-semibold border-r border-gray-200 text-xs">Brand</th>
                            <th class="px-2 py-2 text-left text-gray-600 font-semibold border-r border-gray-200 text-xs">Model</th>
                            <th class="px-2 py-2 text-left text-gray-600 font-semibold border-r border-gray-200 text-xs" title="Minimum Trailer Capacity (lbs)">Min Cap (lbs)</th>
                            <th colspan="{{ count($trailerCols) }}" class="px-1 py-1.5 text-center text-indigo-700 bg-indigo-50 font-semibold border-r border-indigo-200 text-xs">
                                Allowed Trailer Types
                            </th>
                            <th colspan="{{ count($truckCols) }}" class="px-1 py-1.5 text-center text-blue-700 bg-blue-50 font-semibold border-r border-blue-200 text-xs">
                                Allowed Truck Types
                            </th>
                            <th colspan="2" class="px-1 py-1.5 text-center text-gray-700 bg-gray-50 font-semibold border-r border-gray-200 text-xs">
                                Special Conditions
                            </th>
                            <th class="px-1 py-1.5 text-center text-gray-500 border-r border-gray-200 text-xs">Notes</th>
                            <th class="px-1 py-1.5 text-center text-gray-500 text-xs">Save</th>
                        </tr>
                        {{-- Row 2: individual column labels — horizontal text --}}
                        <tr class="bg-gray-50 border-b border-gray-200 text-gray-500" style="font-size:10px;">
                            <th class="border-r border-gray-200"></th>
                            <th class="border-r border-gray-200"></th>
                            <th class="border-r border-gray-200"></th>
                            <th class="border-r border-gray-200"></th>
                            <th class="border-r border-gray-200"></th>
                            {{-- Trailer col labels --}}
                            @foreach ($trailerCols as $t)
                                <th class="py-1 px-1 text-center font-medium text-indigo-600 border-r border-indigo-100 leading-tight" title="{{ $t }}">
                                    {{ $t }}
                                </th>
                            @endforeach
                            {{-- Truck col labels --}}
                            @foreach ($truckCols as $tc)
                                <th class="py-1 px-1 text-center font-medium text-blue-600 border-r border-blue-100 leading-tight" title="{{ $tc }}">
                                    {{ $truckColLabel[$tc] ?? $tc }}
                                </th>
                            @endforeach
                            {{-- Condition labels --}}
                            <th class="py-1 px-1 text-center font-medium text-gray-600 border-r border-gray-200 leading-tight" title="CDL Required">CDL Req.</th>
                            <th class="py-1 px-1 text-center font-medium text-gray-600 border-r border-gray-200 leading-tight" title="Can Share Trailer">Share Trailer</th>
                            <th class="border-r border-gray-200"></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($equipmentGroup as $eq)
                            @php $rule = $equipmentRules[$eq->id] ?? null; @endphp
                            <tr class="hover:bg-gray-50 eq-row" data-equipment-id="{{ $eq->id }}" data-category="{{ $categoryId }}">
                                {{-- Select checkbox --}}
                                <td class="px-0 py-1.5 text-center border-r border-gray-100">
                                    <input type="checkbox" class="row-select rounded border-gray-300 text-indigo-600"
                                        data-equipment-id="{{ $eq->id }}" data-category="{{ $categoryId }}">
                                </td>

                                {{-- Equipment Name --}}
                                <td class="px-2 py-1.5 border-r border-gray-100">
                                    <span class="font-medium text-gray-800 truncate block" title="{{ $eq->equipment_name }}">
                                        {{ $eq->equipment_name }}
                                    </span>
                                    @if ($rule)
                                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-green-400 ml-0.5" title="Rule configured"></span>
                                    @endif
                                </td>

                                {{-- Brand --}}
                                <td class="px-2 py-1.5 border-r border-gray-100 text-gray-600 truncate">{{ $eq->brand }}</td>

                                {{-- Model --}}
                                <td class="px-2 py-1.5 border-r border-gray-100 text-gray-600 truncate">{{ $eq->model }}</td>

                                {{-- Min Trailer Capacity --}}
                                <td class="px-1 py-1.5 border-r border-gray-100">
                                    <input type="number" min="0" step="100"
                                        class="eq-field border border-gray-200 rounded px-1.5 py-0.5 text-xs focus:ring-indigo-500 focus:border-indigo-500"
                                        style="width:72px;"
                                        name="min_trailer_capacity" value="{{ $rule?->min_trailer_capacity ? (int)$rule->min_trailer_capacity : '' }}"
                                        placeholder="lbs">
                                </td>

                                {{-- Trailer type checkboxes --}}
                                @foreach ($trailerCols as $t)
                                    <td class="px-0 py-1.5 text-center border-r border-indigo-50">
                                        <input type="checkbox" name="allowed_trailer_types[]" value="{{ $t }}"
                                            class="eq-field rounded border-gray-300 text-indigo-600"
                                            {{ in_array($t, $rule?->allowed_trailer_types ?? []) ? 'checked' : '' }}>
                                    </td>
                                @endforeach

                                {{-- Truck type checkboxes --}}
                                @foreach ($truckCols as $tc)
                                    <td class="px-0 py-1.5 text-center border-r border-blue-50">
                                        <input type="checkbox" name="allowed_truck_types[]" value="{{ $tc }}"
                                            class="eq-field rounded border-gray-300 text-blue-600"
                                            {{ in_array($tc, $rule?->allowed_truck_types ?? []) ? 'checked' : '' }}>
                                    </td>
                                @endforeach

                                {{-- CDL Required --}}
                                <td class="px-0 py-1.5 text-center border-r border-gray-100">
                                    <input type="checkbox" name="cdl_required" value="1"
                                        class="eq-field rounded border-gray-300 text-red-600"
                                        {{ $rule?->cdl_required ? 'checked' : '' }}>
                                </td>

                                {{-- Can Share Trailer --}}
                                <td class="px-0 py-1.5 text-center border-r border-gray-100">
                                    <input type="checkbox" name="can_share_trailer" value="1"
                                        class="eq-field rounded border-gray-300 text-green-600"
                                        {{ $rule?->can_share_trailer ? 'checked' : '' }}>
                                </td>

                                {{-- Notes icon --}}
                                <td class="px-0 py-1.5 text-center border-r border-gray-100">
                                    <button type="button"
                                        class="notes-btn p-1 rounded hover:bg-gray-100"
                                        data-equipment-id="{{ $eq->id }}"
                                        data-equipment-name="{{ $eq->equipment_name }}"
                                        data-notes="{{ htmlspecialchars($rule?->special_notes ?? '', ENT_QUOTES) }}"
                                        title="{{ $rule?->special_notes ? 'View/Edit Note' : 'Add Note' }}">
                                        @if ($rule?->special_notes)
                                            <x-heroicon-s-chat-bubble-left-ellipsis class="w-4 h-4 text-indigo-500" />
                                        @else
                                            <x-heroicon-o-pencil-square class="w-4 h-4 text-gray-300 hover:text-gray-500" />
                                        @endif
                                    </button>
                                </td>

                                {{-- Save button --}}
                                <td class="px-1 py-1.5 text-center">
                                    <button type="button"
                                        class="eq-save-btn bg-indigo-600 hover:bg-indigo-700 text-white px-2 py-0.5 rounded text-xs font-medium"
                                        data-equipment-id="{{ $eq->id }}">
                                        Save
                                    </button>
                                </td>
                            </tr>
                        @endforeach

                        {{-- Bulk Update Row --}}
                        <tr class="bulk-row bg-amber-50 border-t-2 border-amber-200" data-category="{{ $categoryId }}">
                            <td class="px-0 py-1.5 text-center border-r border-amber-200" colspan="5">
                                <span class="text-xs font-semibold text-amber-700 flex items-center gap-1 px-2">
                                    <x-heroicon-o-squares-2x2 class="w-3.5 h-3.5" />
                                    Bulk Update
                                </span>
                            </td>
                            {{-- Trailer bulk checkboxes --}}
                            @foreach ($trailerCols as $t)
                                <td class="px-0 py-1.5 text-center border-r border-amber-100">
                                    <input type="checkbox" name="bulk_trailer[]" value="{{ $t }}"
                                        class="bulk-field rounded border-gray-300 text-indigo-600">
                                </td>
                            @endforeach
                            {{-- Truck bulk checkboxes --}}
                            @foreach ($truckCols as $tc)
                                <td class="px-0 py-1.5 text-center border-r border-amber-100">
                                    <input type="checkbox" name="bulk_truck[]" value="{{ $tc }}"
                                        class="bulk-field rounded border-gray-300 text-blue-600">
                                </td>
                            @endforeach
                            {{-- Condition bulk checkboxes --}}
                            <td class="px-0 py-1.5 text-center border-r border-amber-100">
                                <input type="checkbox" name="bulk_cdl" class="bulk-field rounded border-gray-300 text-red-600">
                            </td>
                            <td class="px-0 py-1.5 text-center border-r border-amber-100">
                                <input type="checkbox" name="bulk_share" class="bulk-field rounded border-gray-300 text-green-600">
                            </td>
                            <td class="px-1 py-1.5 text-center border-r border-amber-100"></td>
                            <td class="px-1 py-1.5 text-center">
                                <button type="button"
                                    class="bulk-apply-btn bg-amber-500 hover:bg-amber-600 text-white px-2 py-0.5 rounded text-xs font-medium whitespace-nowrap"
                                    data-category="{{ $categoryId }}"
                                    title="Apply bulk settings to checked rows">
                                    Apply
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    @empty
        <div class="bg-white rounded-xl shadow-sm p-8 text-center text-gray-400 text-sm italic">
            No equipment found.
        </div>
    @endforelse
</div>

{{-- Notes Modal --}}
<div id="eq-notes-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/40">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-5">
        <h3 class="font-semibold text-sm text-gray-800 mb-0.5" id="eq-notes-title">Notes</h3>
        <p class="text-xs text-gray-400 mb-3" id="eq-notes-subtitle"></p>
        <textarea id="eq-notes-text" rows="5"
            class="w-full border border-gray-300 rounded-lg py-2 px-3 text-sm focus:ring-indigo-500 focus:border-indigo-500"
            placeholder="Any special transport instructions for AI..."></textarea>
        <p id="eq-notes-error" class="text-xs text-red-600 mt-1 hidden"></p>
        <div class="flex justify-end gap-2 mt-4">
            <button type="button" onclick="closeEqNotesModal()"
                class="px-4 py-1.5 text-sm border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                Cancel
            </button>
            <button type="button" onclick="saveEqNote()"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-1.5 rounded-lg text-sm font-medium">
                Save Note
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    const SAVE_URL  = @json($saveUrl);
    const CSRF      = @json($csrfToken);
    let notesEquipmentId = null;

    // ── per-row save ──────────────────────────────────────────────
    document.querySelectorAll('.eq-save-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            saveEquipmentRow(this.dataset.equipmentId, this);
        });
    });

    function saveEquipmentRow(equipmentId, btn) {
        const row = btn.closest('tr');
        const data = collectRowData(row, equipmentId);

        btn.textContent = '…';
        btn.disabled = true;

        fetch(SAVE_URL, {
            method:  'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body:    JSON.stringify(data),
        })
        .then(r => {
            if (!r.ok) throw new Error('Server error ' + r.status);
            return r.json();
        })
        .then(() => {
            btn.textContent = '✓ Saved';
            btn.classList.replace('bg-indigo-600', 'bg-green-600');
            btn.classList.replace('hover:bg-indigo-700', 'hover:bg-green-700');
            setTimeout(() => {
                btn.textContent = 'Save';
                btn.classList.replace('bg-green-600', 'bg-indigo-600');
                btn.classList.replace('hover:bg-green-700', 'hover:bg-indigo-700');
                btn.disabled = false;
            }, 2000);
        })
        .catch(err => {
            console.error('Equipment rule save failed:', err);
            btn.textContent = '✗ Error';
            btn.classList.replace('bg-indigo-600', 'bg-red-600');
            btn.classList.replace('hover:bg-indigo-700', 'hover:bg-red-700');
            btn.disabled = false;
        });
    }

    function collectRowData(row, equipmentId) {
        return {
            equipment_id:           parseInt(equipmentId),
            min_trailer_capacity:   row.querySelector('[name="min_trailer_capacity"]')?.value || null,
            allowed_trailer_types:  [...row.querySelectorAll('[name="allowed_trailer_types[]"]:checked')].map(c => c.value),
            allowed_truck_types:    [...row.querySelectorAll('[name="allowed_truck_types[]"]:checked')].map(c => c.value),
            cdl_required:           row.querySelector('[name="cdl_required"]')?.checked ? 1 : 0,
            can_share_trailer:      row.querySelector('[name="can_share_trailer"]')?.checked ? 1 : 0,
            special_notes:          row.dataset.notes || null,
        };
    }

    // ── select-all per category ───────────────────────────────────
    document.querySelectorAll('.select-all-cat').forEach(cb => {
        cb.addEventListener('change', function () {
            const cat = this.dataset.category;
            document.querySelectorAll(`.row-select[data-category="${cat}"]`)
                .forEach(r => r.checked = this.checked);
        });
    });

    // ── bulk apply ────────────────────────────────────────────────
    document.querySelectorAll('.bulk-apply-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const cat      = this.dataset.category;
            const bulkRow  = document.querySelector(`.bulk-row[data-category="${cat}"]`);
            const selected = [...document.querySelectorAll(`.row-select[data-category="${cat}"]:checked`)]
                              .map(cb => cb.closest('tr'));

            if (selected.length === 0) {
                alert('Select at least one row using the checkboxes on the left before applying bulk settings.');
                return;
            }

            const bulkTrailers   = [...bulkRow.querySelectorAll('[name="bulk_trailer[]"]:checked')].map(c => c.value);
            const bulkTrucks     = [...bulkRow.querySelectorAll('[name="bulk_truck[]"]:checked')].map(c => c.value);
            const bulkCdl        = bulkRow.querySelector('[name="bulk_cdl"]')?.checked;
            const bulkShare      = bulkRow.querySelector('[name="bulk_share"]')?.checked;

            selected.forEach(row => {
                // Apply trailer types
                row.querySelectorAll('[name="allowed_trailer_types[]"]').forEach(cb => {
                    cb.checked = bulkTrailers.includes(cb.value);
                });
                // Apply truck types
                row.querySelectorAll('[name="allowed_truck_types[]"]').forEach(cb => {
                    cb.checked = bulkTrucks.includes(cb.value);
                });
                if (bulkCdl !== undefined)   row.querySelector('[name="cdl_required"]').checked     = bulkCdl;
                if (bulkShare !== undefined) row.querySelector('[name="can_share_trailer"]').checked = bulkShare;
            });

            // Auto-save all selected rows
            const equipmentIds = selected.map(r => parseInt(r.dataset.equipmentId));
            const firstRow     = selected[0];

            const payload = {
                equipment_ids:          equipmentIds,
                allowed_trailer_types:  bulkTrailers,
                allowed_truck_types:    bulkTrucks,
                cdl_required:           bulkCdl  ? 1 : 0,
                can_share_trailer:      bulkShare ? 1 : 0,
            };

            btn.textContent = '…';
            btn.disabled = true;

            fetch(SAVE_URL, {
                method:  'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body:    JSON.stringify(payload),
            })
            .then(r => {
                if (!r.ok) throw new Error('Server error ' + r.status);
                return r.json();
            })
            .then(data => {
                btn.textContent = `✓ Applied (${data.saved})`;
                setTimeout(() => { btn.textContent = 'Apply'; btn.disabled = false; }, 2500);
            })
            .catch(err => {
                console.error('Bulk save failed:', err);
                btn.textContent = '✗ Error';
                btn.disabled = false;
            });
        });
    });

    // ── notes modal ───────────────────────────────────────────────
    document.querySelectorAll('.notes-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            notesEquipmentId = this.dataset.equipmentId;
            document.getElementById('eq-notes-title').textContent   = this.dataset.equipmentName;
            document.getElementById('eq-notes-subtitle').textContent = 'Special transport instructions for AI';
            document.getElementById('eq-notes-text').value          = this.dataset.notes || '';
            document.getElementById('eq-notes-error').classList.add('hidden');
            document.getElementById('eq-notes-modal').classList.remove('hidden');
        });
    });

    window.closeEqNotesModal = function () {
        document.getElementById('eq-notes-modal').classList.add('hidden');
        notesEquipmentId = null;
    };

    window.saveEqNote = function () {
        const notes = document.getElementById('eq-notes-text').value;
        const errEl = document.getElementById('eq-notes-error');

        fetch(SAVE_URL, {
            method:  'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body:    JSON.stringify({ equipment_id: parseInt(notesEquipmentId), special_notes: notes }),
        })
        .then(r => {
            if (!r.ok) throw new Error('Server error ' + r.status);
            return r.json();
        })
        .then(() => {
            // Update the notes button icon and data attribute
            const notesBtn = document.querySelector(`.notes-btn[data-equipment-id="${notesEquipmentId}"]`);
            if (notesBtn) {
                notesBtn.dataset.notes = notes;
            }
            closeEqNotesModal();
        })
        .catch(err => {
            console.error('Note save failed:', err);
            errEl.textContent = 'Failed to save note. Please try again.';
            errEl.classList.remove('hidden');
        });
    };

    // Close modal on backdrop click
    document.getElementById('eq-notes-modal').addEventListener('click', function (e) {
        if (e.target === this) closeEqNotesModal();
    });

    // ── category filter ───────────────────────────────────────────
    document.getElementById('eq-category-filter').addEventListener('change', function () {
        const val = this.value;
        document.querySelectorAll('[data-category-id]').forEach(block => {
            block.style.display = (val === 'all' || block.dataset.categoryId === val) ? '' : 'none';
        });
    });
})();
</script>
