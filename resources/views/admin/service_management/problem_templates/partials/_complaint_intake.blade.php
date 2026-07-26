{{-- Canonical shared complaint intake — Reported Problem (grouped checklist) +
     Complaint Details (customer_complaint) + Complaint Evidence (evidence[]).
     Consumed by BOTH Standard Service and Field Service. The host supplies the
     effective-equipment resolver via serviceComplaintIntake.init(prefix, getUnit).

     Config: ['prefix' => 'st'|'fs']. Requires $labelClass / $inputClass in scope
     (both hosts define them; falls back if absent). --}}
@php
    $p  = $prefix;
    $lc = $labelClass ?? 'block text-sm font-medium text-gray-700 mb-1';
    $ic = $inputClass ?? 'w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm bg-white text-gray-900 focus:ring focus:border-blue-400 outline-none';
@endphp

<h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2 mb-1">
    <span class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
        <x-heroicon-o-exclamation-triangle class="w-4 h-4" />
    </span>
    Reported Problem
</h2>
<p class="text-xs text-gray-400 mb-4">
    What is wrong with the machine — not how it will be fixed. Check every reported complaint;
    diagnosis, causes, and repairs happen on the workbench.
</p>

<div id="{{ $p }}-complaint-empty" class="rounded-lg border border-dashed border-gray-200 bg-gray-50 px-4 py-6 text-center">
    <p class="text-sm text-gray-400">Select equipment to see the applicable complaints.</p>
</div>
<div id="{{ $p }}-complaint-status" class="hidden mb-3 rounded-lg border px-4 py-2.5">
    <p id="{{ $p }}-complaint-status-text" class="text-sm"></p>
</div>
<div id="{{ $p }}-complaint-list" class="hidden grid grid-cols-1 sm:grid-cols-2 gap-3"></div>
@error('complaints')<p class="text-sm text-red-600 mt-2">{{ $message }}</p>@enderror
@error('complaints.*')<p class="text-sm text-red-600 mt-2">{{ $message }}</p>@enderror

<div id="{{ $p }}-complaint-chips-wrap" class="hidden mt-4">
    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1.5">Selected Complaints</p>
    <div id="{{ $p }}-complaint-chips" class="flex flex-wrap gap-2"></div>
</div>

<div class="mt-4">
    <label class="{{ $lc }}">Complaint Details</label>
    <textarea name="customer_complaint" rows="4" class="{{ $ic }}"
        placeholder="Describe what the customer or employee observed, when it happens, warning codes, noises, or additional details not covered by the selected complaints.">{{ old('customer_complaint') }}</textarea>
    @error('customer_complaint')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
</div>

<div class="mt-4">
    <label class="{{ $lc }}">Complaint Evidence</label>
    <div id="{{ $p }}-evidence-drop" class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-4 py-5 text-center transition">
        <p class="text-sm text-gray-500">Drag &amp; drop photos or videos here, or</p>
        <div class="flex items-center justify-center gap-2 mt-2">
            <button type="button" id="{{ $p }}-evidence-photo-btn"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-300 bg-white text-xs font-medium text-gray-600 hover:bg-gray-100 transition">
                <x-heroicon-o-camera class="w-4 h-4" /> Upload Photos
            </button>
            <button type="button" id="{{ $p }}-evidence-video-btn"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-300 bg-white text-xs font-medium text-gray-600 hover:bg-gray-100 transition">
                <x-heroicon-o-video-camera class="w-4 h-4" /> Upload Video
            </button>
        </div>
        <input type="file" name="evidence[]" id="{{ $p }}-evidence-photos" class="hidden" accept="image/*" capture="environment" multiple>
        <input type="file" name="evidence[]" id="{{ $p }}-evidence-videos" class="hidden" accept="video/*" multiple>
    </div>
    <div id="{{ $p }}-evidence-previews" class="hidden mt-3 grid grid-cols-2 sm:grid-cols-4 gap-3"></div>
    @error('evidence.*')<p class="text-sm text-red-600 mt-2">{{ $message }}</p>@enderror
</div>

@once
@push('js')
<script>
(function () {
    if (window.serviceComplaintIntake) return; // define the shared engine once
    const SYMPTOM_CATEGORIES = @json(\App\Services\ServiceManagement\ServiceProblemLibrary::categories());
    const SYMPTOMS           = @json(\App\Services\ServiceManagement\ServiceProblemLibrary::symptoms());
    const SYMPTOM_PROFILES   = @json(\App\Services\ServiceManagement\ServiceProblemLibrary::profiles());
    const OLD_COMPLAINTS     = @json(collect(old('complaints', []))->map(fn ($v) => (int) $v)->values());

    // init(prefix, getUnit, options?) — getUnit() returns the EFFECTIVE equipment
    // unit ({id,label,symptom_profile_id,product_id,category_ids?}) or null.
    // options.productCategoryMap: [{id, category_ids}] for product→category fallback.
    // Returns { refresh } to re-render when the effective equipment changes.
    window.serviceComplaintIntake = {
        init: function (prefix, getUnit, options) {
            options = options || {};
            const productCategories = {};
            (options.productCategoryMap || []).forEach(function (pr) { productCategories[pr.id] = pr.category_ids || []; });

            const el = id => document.getElementById(prefix + '-' + id);
            const complaintEmpty      = el('complaint-empty');
            const complaintStatus     = el('complaint-status');
            const complaintStatusText = el('complaint-status-text');
            const complaintList       = el('complaint-list');
            const complaintChipsWrap  = el('complaint-chips-wrap');
            const complaintChips      = el('complaint-chips');
            let checkedComplaints = new Set(OLD_COMPLAINTS.map(String));

            function resolveSymptomProfile(unit) {
                if (unit.symptom_profile_id) {
                    const attached = SYMPTOM_PROFILES.find(p => String(p.id) === String(unit.symptom_profile_id));
                    if (attached) return attached;
                }
                const byProduct = SYMPTOM_PROFILES.find(p => p.product_id && String(p.product_id) === String(unit.product_id));
                if (byProduct) return byProduct;
                const categories = (unit.category_ids && unit.category_ids.length) ? unit.category_ids : (productCategories[unit.product_id] || []);
                return SYMPTOM_PROFILES.find(p => p.product_category_id
                    && categories.some(id => String(id) === String(p.product_category_id))) || null;
            }

            function applicableSymptoms(unit) {
                const profile = resolveSymptomProfile(unit);
                if (!profile) return SYMPTOMS.map(s => ({ ...s, _order: s.display_order }));
                const hasCategories = profile.category_ids && profile.category_ids.length;
                if (!hasCategories && profile.items && profile.items.length) {
                    const order = new Map(profile.items.map((id, i) => [String(id), i]));
                    return SYMPTOMS.filter(s => order.has(String(s.id))).map(s => ({ ...s, _order: order.get(String(s.id)) }));
                }
                const categoryIds = (profile.category_ids || []).map(String);
                const additions   = (profile.additions || []).map(String);
                const exclusions  = (profile.exclusions || []).map(String);
                return SYMPTOMS.filter(function (symptom) {
                    const id = String(symptom.id);
                    if (exclusions.includes(id)) return false;
                    if (additions.includes(id)) return true;
                    return categoryIds.includes(String(symptom.category_id));
                }).map(s => ({ ...s, _order: s.display_order }));
            }

            function syncComplaintChips() {
                complaintChips.replaceChildren();
                checkedComplaints.forEach(function (id) {
                    const symptom = SYMPTOMS.find(s => String(s.id) === id);
                    if (!symptom) return;
                    const chip = document.createElement('span');
                    chip.className = 'inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 pl-3 pr-1.5 py-1 text-sm font-medium text-amber-800';
                    chip.appendChild(document.createTextNode(symptom.name));
                    const remove = document.createElement('button');
                    remove.type = 'button';
                    remove.className = 'w-4 h-4 rounded-full flex items-center justify-center text-amber-500 hover:text-amber-700 hover:bg-amber-100 text-xs font-bold leading-none';
                    remove.textContent = '×';
                    remove.title = 'Remove ' + symptom.name;
                    remove.addEventListener('click', function () {
                        checkedComplaints.delete(id);
                        const box = complaintList.querySelector('input[value="' + id + '"]');
                        if (box) box.checked = false;
                        syncComplaintChips();
                    });
                    chip.appendChild(remove);
                    complaintChips.appendChild(chip);
                });
                complaintChipsWrap.classList.toggle('hidden', checkedComplaints.size === 0);
            }

            function setComplaintStatus(unit) {
                if (!unit) { complaintStatus.classList.add('hidden'); return; }
                complaintStatus.classList.remove('hidden', 'border-amber-200', 'bg-amber-50', 'border-blue-200', 'bg-blue-50');
                complaintStatusText.classList.remove('text-amber-800', 'text-blue-700');
                const profile = resolveSymptomProfile(unit);
                if (profile) {
                    complaintStatus.classList.add('border-blue-200', 'bg-blue-50');
                    complaintStatusText.classList.add('text-blue-700');
                    complaintStatusText.textContent = 'Template: ' + profile.name;
                } else {
                    complaintStatus.classList.add('border-amber-200', 'bg-amber-50');
                    complaintStatusText.classList.add('text-amber-800');
                    complaintStatusText.textContent = 'No Problem Template is assigned to ' + (unit.label || 'this equipment') + ' — showing all applicable problems.';
                }
            }

            function syncComplaintList() {
                const unit = getUnit();
                complaintEmpty.classList.toggle('hidden', !!unit);
                complaintList.classList.toggle('hidden', !unit);
                complaintList.replaceChildren();
                if (!unit) { checkedComplaints.clear(); syncComplaintChips(); setComplaintStatus(null); return; }
                setComplaintStatus(unit);

                const applicable = applicableSymptoms(unit);
                checkedComplaints.forEach(function (id) {
                    if (!applicable.some(s => String(s.id) === id)) checkedComplaints.delete(id);
                });

                const groups = new Map();
                applicable.forEach(function (symptom) {
                    if (!groups.has(symptom.category_id)) {
                        const category = SYMPTOM_CATEGORIES.find(c => c.id === symptom.category_id);
                        groups.set(symptom.category_id, {
                            label: category ? category.name : 'Other',
                            order: category ? category.display_order : 999,
                            symptoms: [],
                        });
                    }
                    groups.get(symptom.category_id).symptoms.push(symptom);
                });

                Array.from(groups.values()).sort((a, b) => a.order - b.order).forEach(function (group) {
                    const box = document.createElement('div');
                    box.className = 'rounded-lg border border-gray-200 bg-gray-50 p-3';
                    const heading = document.createElement('p');
                    heading.className = 'text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2';
                    heading.textContent = group.label;
                    box.appendChild(heading);
                    group.symptoms.slice().sort((a, b) => a._order - b._order).forEach(function (symptom) {
                        const row = document.createElement('label');
                        row.className = 'flex items-center gap-2 py-0.5 cursor-pointer';
                        const check = document.createElement('input');
                        check.type = 'checkbox';
                        check.name = 'complaints[]';
                        check.value = symptom.id;
                        check.className = 'text-amber-600 rounded focus:ring-amber-500';
                        check.checked = checkedComplaints.has(String(symptom.id));
                        check.addEventListener('change', function () {
                            check.checked ? checkedComplaints.add(String(symptom.id)) : checkedComplaints.delete(String(symptom.id));
                            syncComplaintChips();
                        });
                        row.appendChild(check);
                        const text = document.createElement('span');
                        text.className = 'text-sm text-gray-700';
                        text.textContent = symptom.name;
                        row.appendChild(text);
                        box.appendChild(row);
                    });
                    complaintList.appendChild(box);
                });
                syncComplaintChips();
            }

            // ── Complaint evidence: previews with remove-before-save ──
            const photoInput = el('evidence-photos');
            const videoInput = el('evidence-videos');
            const dropZone   = el('evidence-drop');
            const previews   = el('evidence-previews');
            el('evidence-photo-btn').addEventListener('click', () => photoInput.click());
            el('evidence-video-btn').addEventListener('click', () => videoInput.click());

            function evidenceFiles() {
                return [
                    ...Array.from(photoInput.files).map((f, i) => ({ file: f, input: photoInput, index: i })),
                    ...Array.from(videoInput.files).map((f, i) => ({ file: f, input: videoInput, index: i })),
                ];
            }
            function removeEvidence(input, index) {
                const keep = new DataTransfer();
                Array.from(input.files).forEach(function (file, i) { if (i !== index) keep.items.add(file); });
                input.files = keep.files;
                syncEvidence();
            }
            function addEvidence(input, files) {
                const merged = new DataTransfer();
                Array.from(input.files).forEach(f => merged.items.add(f));
                Array.from(files).forEach(f => merged.items.add(f));
                input.files = merged.files;
                syncEvidence();
            }
            function syncEvidence() {
                previews.replaceChildren();
                const files = evidenceFiles();
                previews.classList.toggle('hidden', files.length === 0);
                files.forEach(function (entry) {
                    const card = document.createElement('div');
                    card.className = 'rounded-lg border border-gray-200 bg-white p-2';
                    if (entry.file.type.startsWith('image/')) {
                        const img = document.createElement('img');
                        img.src = URL.createObjectURL(entry.file);
                        img.className = 'w-full h-20 object-cover rounded-md bg-gray-50';
                        img.addEventListener('load', () => URL.revokeObjectURL(img.src));
                        card.appendChild(img);
                    } else {
                        const block = document.createElement('div');
                        block.className = 'w-full h-20 rounded-md bg-gray-100 flex items-center justify-center text-[10px] font-bold uppercase tracking-wide text-gray-400';
                        block.textContent = 'Video';
                        card.appendChild(block);
                    }
                    const row = document.createElement('div');
                    row.className = 'flex items-center justify-between gap-1 mt-1.5';
                    const name = document.createElement('p');
                    name.className = 'text-[11px] text-gray-500 truncate';
                    name.textContent = entry.file.name;
                    name.title = entry.file.name;
                    row.appendChild(name);
                    const remove = document.createElement('button');
                    remove.type = 'button';
                    remove.className = 'text-xs font-bold text-red-400 hover:text-red-600 shrink-0';
                    remove.textContent = '×';
                    remove.title = 'Remove ' + entry.file.name;
                    remove.addEventListener('click', () => removeEvidence(entry.input, entry.index));
                    row.appendChild(remove);
                    card.appendChild(row);
                    previews.appendChild(card);
                });
            }
            photoInput.addEventListener('change', syncEvidence);
            videoInput.addEventListener('change', syncEvidence);
            ['dragover', 'dragenter'].forEach(ev => dropZone.addEventListener(ev, function (e) { e.preventDefault(); dropZone.classList.add('border-amber-400', 'bg-amber-50'); }));
            ['dragleave', 'drop'].forEach(ev => dropZone.addEventListener(ev, function (e) { e.preventDefault(); dropZone.classList.remove('border-amber-400', 'bg-amber-50'); }));
            dropZone.addEventListener('drop', function (e) {
                const dropped = Array.from(e.dataTransfer.files);
                addEvidence(photoInput, dropped.filter(f => !f.type.startsWith('video/')));
                addEvidence(videoInput, dropped.filter(f => f.type.startsWith('video/')));
            });

            syncComplaintList();
            return { refresh: syncComplaintList };
        },
    };
})();
</script>
@endpush
@endonce
