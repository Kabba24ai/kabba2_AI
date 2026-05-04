@extends('admin.layouts.app')

@section('title', 'Update Equipment')

@section('content')
    <div class="h-screen bg-gray-50 flex flex-col overflow-hidden">
        <div class="flex-1">
            {{-- Header --}}
            <div class="bg-white border-b border-gray-200 px-6 py-4">
                <div class="max-w-5xl flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <a href="{{ route('admin.maintenance-management.equipment.index') }}"
                            class="flex items-center space-x-2 text-gray-600 hover:text-gray-800 transition-colors">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            <span>Back to Equipment</span>
                        </a>
                        <div class="h-6 border-l border-gray-300"></div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Equipment Details</h1>
                            <p class="text-sm text-gray-600">Edit equipment information</p>
                        </div>
                    </div>

                    <button type="submit" form="productForm" name="save_as_new" value="1"
                        class="inline-flex items-center px-5 py-2 rounded-md text-white bg-green-600 hover:bg-green-700 text-sm font-semibold shadow transition">
                        Save as New
                        <svg class="w-4 h-4 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"></path>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Main Content --}}
            <div class="p-6">
                {{ html()->modelForm($equipment, 'PUT')->attributes([
                        'action' => route('admin.maintenance-management.equipment.edit', $equipment->unique_id),
                        'id' => 'productForm',
                        'autocomplete' => 'off',
                        'data-parsley-validate' => true,
                        'class' => 'max-w-5xl',
                    ])->acceptsFiles()->open() }}
                @csrf
                @method('PUT')

                {{-- Main actions --}}
                <div class="mb-6 flex justify-end gap-3">
                    <button type="submit" name="action" value="save"
                        class="inline-flex items-center px-6 py-2 rounded-md border border-blue-600 text-blue-600 bg-white hover:bg-blue-50 text-sm font-semibold shadow-sm transition">
                        Save
                    </button>

                    <button type="submit" name="action" value="save_exit"
                        class="inline-flex items-center px-6 py-2 rounded-md text-white bg-blue-600 hover:bg-blue-700 text-sm font-semibold shadow transition">
                        Save &amp; Exit
                        <svg class="w-4 h-4 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9">
                            </path>
                        </svg>
                    </button>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-6 pt-5">
                        <nav class="-mb-px flex flex-wrap gap-6" aria-label="Equipment tabs">
                            <button type="button" data-tab-btn="overview"
                                class="tab-btn inline-flex items-center border-b-2 border-blue-600 px-1 pb-3 text-sm font-semibold text-blue-600 transition-colors">
                                Overview
                            </button>
                            <button type="button" data-tab-btn="specification"
                                class="tab-btn inline-flex items-center border-b-2 border-transparent px-1 pb-3 text-sm font-semibold text-gray-500 transition-colors hover:border-gray-300 hover:text-gray-700">
                                Specification
                            </button>
                            <button type="button" data-tab-btn="key-comparison"
                                class="tab-btn inline-flex items-center border-b-2 border-transparent px-1 pb-3 text-sm font-semibold text-gray-500 transition-colors hover:border-gray-300 hover:text-gray-700">
                                Key Comparison
                            </button>
                        </nav>
                    </div>

                    <div class="p-6 max-h-[calc(100vh-270px)] overflow-y-auto">
                        <div data-tab-panel="overview">
                            @include('admin.maintenance_management.equipment.partials._form')
                        </div>

                        <div data-tab-panel="specification" class="hidden">
                            <div class="space-y-6">

                                {{-- Section A: AI Lookup Input --}}
                                <div class="rounded-xl border border-blue-100 bg-blue-50 p-5">
                                    <div class="mb-3 flex items-center gap-2">
                                        <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        <span class="text-sm font-semibold text-blue-800">AI Lookup Input — what will be sent to ChatGPT</span>
                                    </div>
                                    <pre id="spec-lookup-payload" class="rounded-lg bg-white border border-blue-200 px-4 py-3 text-xs text-gray-700 font-mono overflow-x-auto whitespace-pre-wrap">{{ $equipmentLookupPayload }}</pre>
                                    <div class="mt-4 flex items-center justify-between gap-3">
                                        <span id="spec-status-msg" class="text-xs text-gray-500"></span>
                                        <button type="button" id="btn-spec-generate"
                                            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-blue-700 disabled:opacity-60">
                                            <svg id="spec-spinner" class="hidden h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                            </svg>
                                            <svg id="spec-bolt" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                            <span id="spec-btn-label">AI Generate Specifications</span>
                                        </button>
                                    </div>
                                </div>

                                {{-- Section B + C: Results table --}}
                                <div id="spec-results-wrap" class="{{ count(json_decode($existingSpecs, true) ?? []) === 0 ? 'hidden' : '' }}">
                                    <div class="mb-3 flex items-center gap-2">
                                        <svg class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6a2 2 0 012-2h2a2 2 0 012 2v6m-6 0h6" />
                                        </svg>
                                        <span class="text-sm font-semibold text-gray-700">AI Retrieved Specifications</span>
                                        <span class="text-xs text-gray-400 ml-2">Approved values become Kabba-owned data</span>
                                    </div>
                                    <div class="rounded-xl border border-gray-200 overflow-hidden">
                                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-48">Specification</th>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Value</th>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-20">Unit</th>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-24">Confidence</th>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Source</th>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-28">Status</th>
                                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider w-48">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody id="spec-table-body" class="divide-y divide-gray-100 bg-white">
                                                {{-- Rows injected by JS --}}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div data-tab-panel="key-comparison" class="hidden">
                            <div class="max-w-4xl space-y-6">
                                <div>
                                    <h2 class="text-lg font-semibold text-gray-900">Key Comparison</h2>
                                    <p class="mt-1 text-sm text-gray-600">Build a comparison using the current equipment
                                        data and other relevant products.</p>
                                </div>

                                <div class="rounded-xl border border-gray-200 bg-gray-50 p-5 space-y-4">
                                    <div>
                                        <label for="equipment_key_comparison_notes"
                                            class="mb-2 block text-sm font-medium text-gray-700">
                                            Comparison Notes
                                        </label>
                                        <textarea id="equipment_key_comparison_notes" name="equipment_key_comparison_notes" rows="12"
                                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            placeholder="Describe what should be compared for this equipment.">{{ old('equipment_key_comparison_notes') }}</textarea>
                                    </div>

                                    <div>
                                        <label for="similar_equipment_ids"
                                            class="mb-2 block text-sm font-medium text-gray-700">
                                            Similar Products (from Equipment Name)
                                        </label>
                                        @php
                                            $selectedSimilarEquipmentIds = array_map('strval', old('similar_equipment_ids', $defaultSimilarEquipmentIds ?? []));
                                        @endphp
                                        <select id="similar_equipment_ids" name="similar_equipment_ids[]" multiple
                                            class="choices-select w-full rounded-md border border-gray-300 bg-white text-sm text-gray-900">
                                            @forelse($similarEquipmentOptions as $id => $label)
                                                <option value="{{ $id }}"
                                                    @selected(in_array((string) $id, $selectedSimilarEquipmentIds, true))>
                                                    {{ $label }}
                                                </option>
                                            @empty
                                                <option value="" disabled>No similar equipment found in this category</option>
                                            @endforelse
                                        </select>
                                    </div>

                                    <div class="flex justify-end">
                                        <button type="button"
                                            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-blue-700">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                            <span>AI Generate</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                {{ html()->form()->close() }}
            </div>
        </div>
    </div>
@endsection

@push('js')
<script>
    document.addEventListener('DOMContentLoaded', () => {

        /* ──────────────── TAB SWITCHING ──────────────── */
        const tabButtons = Array.from(document.querySelectorAll('[data-tab-btn]'));
        const tabPanels  = Array.from(document.querySelectorAll('[data-tab-panel]'));

        function setActive(tabName) {
            tabPanels.forEach(p => p.classList.toggle('hidden', p.getAttribute('data-tab-panel') !== tabName));
            tabButtons.forEach(btn => {
                const on = btn.getAttribute('data-tab-btn') === tabName;
                btn.classList.toggle('border-blue-600', on);
                btn.classList.toggle('text-blue-600', on);
                btn.classList.toggle('border-transparent', !on);
                btn.classList.toggle('text-gray-500', !on);
            });
        }

        tabButtons.forEach(btn => btn.addEventListener('click', () => setActive(btn.getAttribute('data-tab-btn'))));
        setActive('overview');

        /* ──────────────── SPECIFICATION AI ──────────────── */
        const GENERATE_URL     = @json($specGenerateUrl);
        const APPROVE_BASE_URL = @json($specApproveBaseUrl);
        const CSRF             = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

        let specState = @json(json_decode($existingSpecs)); // array of spec objects

        const tbody       = document.getElementById('spec-table-body');
        const resultsWrap = document.getElementById('spec-results-wrap');
        const genBtn      = document.getElementById('btn-spec-generate');
        const btnLabel    = document.getElementById('spec-btn-label');
        const spinner     = document.getElementById('spec-spinner');
        const bolt        = document.getElementById('spec-bolt');
        const statusMsg   = document.getElementById('spec-status-msg');
        const lookupPayloadEl = document.getElementById('spec-lookup-payload');

        function valueOf(selector) {
            const el = document.querySelector(selector);
            return el ? String(el.value ?? '').trim() : '';
        }

        function selectedText(selector) {
            const el = document.querySelector(selector);
            if (!el || el.selectedIndex < 0) return '';
            return String(el.options[el.selectedIndex]?.text ?? '').trim();
        }

        function buildLookupPayload() {
            return {
                brand: valueOf('[name="brand"]'),
                model: valueOf('[name="model"]'),
                model_year: valueOf('[name="model_year"]'),
                category: selectedText('[name="product_category_id"]') === 'Select Category' ? '' : selectedText('[name="product_category_id"]'),
                equipment_name: valueOf('[name="equipment_name"]'),
                equipment_id: valueOf('[name="equipment_id"]'),
                serial_number: valueOf('[name="serial_number"]') || null,
                vin: valueOf('[name="vehicle_identification_number"]') || null,
            };
        }

        function refreshLookupPayloadPreview() {
            if (!lookupPayloadEl) return;
            lookupPayloadEl.textContent = JSON.stringify(buildLookupPayload(), null, 2);
        }

        /* ---- helpers ---- */
        function confidenceColor(score) {
            if (score === null || score === undefined) return 'text-gray-400';
            if (score >= 0.85) return 'text-green-600';
            if (score >= 0.6)  return 'text-yellow-600';
            return 'text-red-500';
        }

        function confidenceLabel(score) {
            if (score === null || score === undefined) return '—';
            return Math.round(score * 100) + '%';
        }

        function escHtml(str) {
            if (!str) return '';
            return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }

        /* ---- render all rows ---- */
        function renderSpecs(specs) {
            tbody.innerHTML = '';
            specs.forEach(spec => renderRow(spec));
            resultsWrap.classList.toggle('hidden', specs.length === 0);
        }

        /* ---- render single row (or replace) ---- */
        function renderRow(spec) {
            const existing = document.getElementById('spec-row-' + spec.id);
            const row = buildRow(spec);
            if (existing) {
                existing.replaceWith(row);
            } else {
                tbody.appendChild(row);
            }
        }

        function buildRow(spec) {
            const tr = document.createElement('tr');
            tr.id = 'spec-row-' + spec.id;
            tr.className = spec.is_approved ? 'bg-green-50' : '';

            const approvedBadge = spec.is_approved
                ? `<span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700">
                       <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 00-1.414 0L8 12.586 4.707 9.293a1 1 0 00-1.414 1.414l4 4a1 1 0 001.414 0l8-8a1 1 0 000-1.414z" clip-rule="evenodd"/></svg>
                       Approved</span>`
                : `<span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500">Pending</span>`;

            const overrideBadge = spec.is_manual_override
                ? `<span class="ml-1 inline-flex items-center rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-700">Manual</span>`
                : '';

            const sourceCell = spec.source_url
                ? `<a href="${escHtml(spec.source_url)}" target="_blank" rel="noopener" class="text-blue-600 hover:underline truncate block max-w-xs text-xs">${escHtml(spec.source_url.replace(/^https?:\/\//, ''))}</a>`
                : '<span class="text-gray-400 text-xs">—</span>';

            const approvedMeta = spec.is_approved && spec.approved_by_name
                ? `<div class="text-xs text-gray-400 mt-0.5">by ${escHtml(spec.approved_by_name)} · ${escHtml(spec.approved_at ?? '')}</div>`
                : '';

            // action buttons – hide Approve if already approved
            const approveBtn = spec.is_approved
                ? ''
                : `<button type="button" data-action="approve" data-id="${spec.id}"
                       class="inline-flex items-center gap-1 rounded-md bg-green-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-green-700 transition-colors">
                       <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                       Approve
                   </button>`;

            tr.innerHTML = `
                <td class="px-4 py-3 font-medium text-gray-800 align-top">${escHtml(spec.spec_label)}</td>
                <td class="px-4 py-3 align-top">
                    <span class="spec-display-value">${escHtml(spec.value ?? '—')}</span>
                    <div class="spec-edit-row hidden mt-1 flex gap-2">
                        <input type="text" class="spec-value-input rounded-md border border-gray-300 px-2 py-1 text-xs w-32 focus:ring-2 focus:ring-blue-500" value="${escHtml(spec.value ?? '')}" placeholder="value">
                        <input type="text" class="spec-unit-input rounded-md border border-gray-300 px-2 py-1 text-xs w-20 focus:ring-2 focus:ring-blue-500" value="${escHtml(spec.unit ?? '')}" placeholder="unit">
                        <input type="text" class="spec-source-input rounded-md border border-gray-300 px-2 py-1 text-xs w-48 focus:ring-2 focus:ring-blue-500" value="${escHtml(spec.source_url ?? '')}" placeholder="source URL">
                        <button type="button" data-action="save" data-id="${spec.id}" class="rounded-md bg-blue-600 px-3 py-1 text-xs font-semibold text-white hover:bg-blue-700">Save</button>
                        <button type="button" data-action="cancel-edit" data-id="${spec.id}" class="rounded-md border border-gray-300 px-3 py-1 text-xs text-gray-600 hover:bg-gray-50">Cancel</button>
                    </div>
                </td>
                <td class="px-4 py-3 text-gray-600 align-top">
                    <span class="spec-display-unit">${escHtml(spec.unit ?? '—')}</span>
                </td>
                <td class="px-4 py-3 align-top">
                    <span class="${confidenceColor(spec.confidence_score)} font-semibold text-xs">${confidenceLabel(spec.confidence_score)}</span>
                    ${spec.last_verified_at ? `<div class="text-xs text-gray-400">${escHtml(spec.last_verified_at)}</div>` : ''}
                </td>
                <td class="px-4 py-3 align-top">${sourceCell}</td>
                <td class="px-4 py-3 align-top">
                    ${approvedBadge}${overrideBadge}
                    ${approvedMeta}
                </td>
                <td class="px-4 py-3 align-top text-right">
                    <div class="inline-flex items-center gap-1.5 flex-wrap justify-end">
                        ${approveBtn}
                        <button type="button" data-action="edit" data-id="${spec.id}"
                            class="inline-flex items-center gap-1 rounded-md border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-50 transition-colors">
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Edit
                        </button>
                        <button type="button" data-action="retry" data-id="${spec.id}" data-key="${escHtml(spec.spec_key)}"
                            class="inline-flex items-center gap-1 rounded-md border border-orange-300 px-3 py-1.5 text-xs font-semibold text-orange-600 hover:bg-orange-50 transition-colors">
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Try Again
                        </button>
                    </div>
                </td>`;

            return tr;
        }

        /* ---- generate all ---- */
        async function generateSpecs(forceKey = null) {
            genBtn.disabled = true;
            spinner.classList.remove('hidden');
            bolt.classList.add('hidden');
            btnLabel.textContent = 'Generating…';
            statusMsg.textContent = '';
            statusMsg.classList.remove('text-red-500');

            const url = forceKey ? GENERATE_URL + '?force_key=' + encodeURIComponent(forceKey) : GENERATE_URL;
            const lookupPayload = buildLookupPayload();
            refreshLookupPayloadPreview();

            try {
                const res  = await fetch(url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ lookup_payload: lookupPayload }),
                });
                const data = await res.json();

                if (!res.ok || !data.success) {
                    statusMsg.textContent = data.message ?? 'Generation failed.';
                    statusMsg.classList.add('text-red-500');
                    return;
                }

                specState = data.specs;
                renderSpecs(specState);
                statusMsg.textContent = '✓ Specifications updated.';
                statusMsg.classList.remove('text-red-500');

            } catch (err) {
                statusMsg.textContent = 'Network error: ' + err.message;
                statusMsg.classList.add('text-red-500');
            } finally {
                genBtn.disabled = false;
                spinner.classList.add('hidden');
                bolt.classList.remove('hidden');
                btnLabel.textContent = 'AI Generate Specifications';
            }
        }

        /* ---- approve ---- */
        async function approveSpec(id) {
            const btn = tbody.querySelector(`[data-action="approve"][data-id="${id}"]`);
            if (btn) btn.disabled = true;

            try {
                const res  = await fetch(`${APPROVE_BASE_URL}/${id}/approve`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (data.success) renderRow(data.spec);
            } catch (err) {
                if (btn) btn.disabled = false;
            }
        }

        /* ---- save edit ---- */
        async function saveSpec(id, row) {
            const value  = row.querySelector('.spec-value-input').value;
            const unit   = row.querySelector('.spec-unit-input').value;
            const source = row.querySelector('.spec-source-input').value;

            try {
                const res  = await fetch(`${APPROVE_BASE_URL}/${id}`, {
                    method: 'PUT',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ value, unit, source_url: source }),
                });
                const data = await res.json();
                if (data.success) renderRow(data.spec);
            } catch (err) {}
        }

        /* ---- event delegation on tbody ---- */
        tbody.addEventListener('click', async (e) => {
            const btn = e.target.closest('[data-action]');
            if (!btn) return;

            const action = btn.dataset.action;
            const id     = parseInt(btn.dataset.id);
            const row    = document.getElementById('spec-row-' + id);

            if (action === 'approve') await approveSpec(id);
            if (action === 'retry')   await generateSpecs(btn.dataset.key);

            if (action === 'edit') {
                row.querySelector('.spec-display-value').classList.add('hidden');
                row.querySelector('.spec-display-unit').classList.add('hidden');
                row.querySelector('.spec-edit-row').classList.remove('hidden');
            }

            if (action === 'cancel-edit') {
                row.querySelector('.spec-display-value').classList.remove('hidden');
                row.querySelector('.spec-display-unit').classList.remove('hidden');
                row.querySelector('.spec-edit-row').classList.add('hidden');
            }

            if (action === 'save') await saveSpec(id, row);
        });

        genBtn.addEventListener('click', () => generateSpecs());

        ['input', 'change'].forEach((eventName) => {
            ['[name="brand"]', '[name="model"]', '[name="model_year"]', '[name="product_category_id"]', '[name="equipment_name"]', '[name="equipment_id"]', '[name="serial_number"]', '[name="vehicle_identification_number"]']
                .forEach((selector) => {
                    const el = document.querySelector(selector);
                    if (el) {
                        el.addEventListener(eventName, refreshLookupPayloadPreview);
                    }
                });
        });

        refreshLookupPayloadPreview();

        /* ---- initial render from server-side data ---- */
        if (specState && specState.length > 0) {
            renderSpecs(specState);
        }
    });
</script>
@endpush
