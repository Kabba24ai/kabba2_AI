@extends('admin.layouts.app')

@section('title', 'Create Template')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div>
        {{-- Header --}}
        <div class="bg-white rounded-md p-6 shadow-sm border border-gray-100 mb-6">
            <div class="flex items-center space-x-3 ">
                <a href="{{ route('admin.maintenance-management.parts.index') }}" class="p-2 text-gray-400 hover:text-gray-600 transition-colors rounded-lg">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                <h1 class="text-2xl font-semibold text-gray-900">Create New Template</h1>
            </div>
            <p class="text-gray-600 ml-14">Create a reusable parts list template for equipment maintenance</p>
        </div>

        {{-- Form --}}
         <form action="{{ route('admin.maintenance-management.parts.create') }}" method="POST">
            <div class="bg-white rounded-md p-6 shadow-sm border border-gray-100">
            
                @csrf
                @include('admin.maintenance_management.parts.templates.partials._form')            
            </div>

            <div class="rounded-md border border-gray-200 bg-white p-6 shadow-sm mt-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                    <h3 class="text-lg font-semibold text-gray-900">Template Parts</h3>
                    <p class="text-sm text-gray-500">
                        Parts included in this template (<span id="templatePartsCount">0</span> parts)
                    </p>
                    </div>

                    <button onclick="openPartsModal()"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-blue-700 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Existing Parts
                    </button>
                </div>

                <!-- Empty state -->
                <div id="templateEmpty" class="mt-6 border-2 border-dashed border-gray-200 rounded-lg p-12 text-center py-12">
                    <div class="flex flex-col items-center justify-center space-y-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-package h-12 w-12 text-gray-400 mx-auto mb-4"><path d="m7.5 4.27 9 5.15"></path><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path><path d="m3.3 7 8.7 5 8.7-5"></path><path d="M12 22V12"></path>
                        </svg>
                        <p class="text-gray-700 font-medium">No parts added yet</p>
                        <p class="text-gray-500 text-sm">Add parts to this template.</p>
                        <button onclick="openPartsModal()"
                            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-blue-700 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Parts
                        </button>
                    </div>
                </div>

                <!-- Filled: mobile cards -->
                <div id="templateMobile" class="hidden mt-6 md:hidden">
                    <ul id="templatePartsCards" class="divide-y divide-gray-200 rounded-lg border border-gray-200 overflow-hidden">
                    <!-- cards injected here -->
                    </ul>
                </div>

                <!-- Filled: desktop table -->
                <div id="templateTableWrap" class="hidden mt-6  md:block">
                    <div class="overflow-x-auto">
                    <table class="min-w-full text-left">
                        <thead id="templateTableHead" class="hidden bg-gray-50 border-b border-gray-200">
                            <tr class="bg-gray-50 text-gray-700">
                                <th class="w-10 px-4 py-3"></th>
                                <th class="px-4 py-3 text-sm font-semibold">Part Name</th>
                                <th class="px-4 py-3 text-sm font-semibold">Part Number</th>
                                <th class="px-4 py-3 text-sm font-semibold">Category</th>
                                <th class="px-4 py-3 text-sm font-semibold">Unit Cost</th>
                                <th class="px-4 py-3 text-sm font-semibold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="templatePartsTbody" class="divide-y divide-gray-200">
                        <!-- rows injected here -->
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>

           {{-- Form Actions --}}
            <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200">
                <a href="{{ route('admin.maintenance-management.parts.index') }}"
                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 flex items-center gap-2 transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Cancel
                </a>
                <button type="submit"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-save h-4 w-4"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                    Save Template
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===== Modal (simple JS open/close) ===== -->
<div id="partsModal" class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 py-10 hidden">
    <div class="modal-scrollable w-full mx-auto">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/50" onclick="closePartsModal()"></div>

        <!-- Dialog -->
        <div class="relative mx-auto mt-16 w-[95%] max-w-3xl rounded-2xl bg-white shadow-xl">
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Add Parts to Template</h2>
                <button onclick="closePartsModal()" class="text-gray-400 hover:text-gray-600">
                    ✕
                </button>
            </div>

            <!-- Content -->
            <div class="px-6 py-4 space-y-4 max-h-[60vh] overflow-y-auto">
            <!-- Search & Filter (static UI, not wired; you can hook later) -->
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative flex-1 min-w-[220px]">
                <input type="text" placeholder="Search parts..."
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 19a8 8 0 1 1 0-16 8 8 0 0 1 0 16z"/>
                </svg>
                </div>
                <select class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500">
                    <option>All Categories</option>
                    <option>Excavators</option>
                    <option>Generators</option>
                    <option>Compressors</option>
                </select>
            </div>

            <p id="selectedCountLabel" class="text-sm text-gray-500">0 parts selected</p>

            <!-- Parts list (example items) -->
            <div class="space-y-3">
                <!-- Part row -->
                <label class="flex items-center gap-3 rounded-xl border border-gray-200 p-4 hover:border-blue-300 hover:bg-blue-50/50 cursor-pointer">
                    <input type="checkbox" class="part-checkbox h-5 w-5 accent-blue-600"
                            data-id="p1"
                            data-name="Hydraulic Filter"
                            data-sku="HF-2024-001"
                            data-brand="Caterpillar Inc."
                            data-category="Excavators"
                            data-price="45.99">
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-medium text-gray-900">Air Filter Element</h3>
                                <p class="text-xs text-gray-500">AF-2024-012 • Atlas Copco</p>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Compressors</span>
                                <p class="text-sm font-medium text-green-600 mt-1">$67.25</p>
                            </div>
                        </div>
                    </div>
                </label>

                <label class="flex items-center gap-3 rounded-xl border border-gray-200 p-4 hover:border-blue-300 hover:bg-blue-50/50 cursor-pointer">
                    <input type="checkbox" class="part-checkbox h-5 w-5 accent-blue-600"
                            data-id="p2"
                            data-name="Engine Oil Filter"
                            data-sku="OF-2024-005"
                            data-brand="Kohler Power"
                            data-category="Generators"
                            data-price="28.50">
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-medium text-gray-900">Transmission Filter</h3>
                                <p class="text-xs text-gray-500">TF-2024-008 • Parker Hannifin</p>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Loaders</span><p class="text-sm font-medium text-green-600 mt-1">$32.75</p>
                            </div>
                        </div>
                    </div>
                </label>

                <label class="flex items-center gap-3 rounded-xl border border-gray-200 p-4 hover:border-blue-300 hover:bg-blue-50/50 cursor-pointer">
                    <input type="checkbox" class="part-checkbox h-5 w-5 accent-blue-600"
                            data-id="p3"
                            data-name="Air Filter Element"
                            data-sku="AF-2024-012"
                            data-brand="Atlas Copco"
                            data-category="Compressors"
                            data-price="67.25">
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-medium text-gray-900">Hydraulic Pump Seal</h3>
                                <p class="text-xs text-gray-500">HS-2024-015 • John Deere</p>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Bulldozers</span>
                                <p class="text-sm font-medium text-green-600 mt-1">$89.50</p>
                            </div>
                        </div>
                    </div>
                </label>
            </div>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200">
                <button onclick="closePartsModal()" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm">
                    Cancel
                </button>
                <button id="addSelectedBtn"
                        onclick="addSelectedPartsToTemplate()"
                        class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed"
                        disabled>
                    Add 0 Parts to Template
                </button>
            </div>
        </div>
    </div>
</div>

@endsection


@push('js')


<!-- JS -->
<script>
  // -------- Modal open/close --------
  function openPartsModal() {
    document.getElementById('partsModal').classList.remove('hidden');
    updateSelectedCount();
  }
  function closePartsModal() {
    document.getElementById('partsModal').classList.add('hidden');
  }

  // -------- Selected counter in modal --------
  function updateSelectedCount() {
    const checks = document.querySelectorAll('.part-checkbox:checked');
    const n = checks.length;
    const label = document.getElementById('selectedCountLabel');
    const btn = document.getElementById('addSelectedBtn');
    if (label) label.textContent = `${n} part${n !== 1 ? 's' : ''} selected`;
    if (btn) {
      btn.textContent = `Add ${n} Part${n !== 1 ? 's' : ''} to Template`;
      btn.disabled = n === 0;
    }
  }

  // Watch modal checkboxes
  document.addEventListener('change', function (e) {
    if (e.target && e.target.classList.contains('part-checkbox')) {
      updateSelectedCount();
    }
  });

  // -------- Helpers for rendering table/mobile rows --------
  const fmt = v => `$${Number(v).toFixed(2)}`;

  // NOTE: drag handle added in first <td>, row made draggable by setupRowDragging()
  function renderTableRow(item) {
    return `
      <tr data-id="${item.id}">
        <td class="px-4 py-3">
          <span class="drag-handle inline-block text-gray-400 cursor-grab active:cursor-grabbing select-none" title="Drag to reorder">⋮⋮</span>
        </td>
        <td class="px-4 py-3 text-sm font-medium text-gray-900">${item.name}</td>
        <td class="px-4 py-3 text-sm text-gray-900">${item.sku}</td>
        <td class="px-4 py-3">
          <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">${item.category}</span>
        </td>
        <td class="px-4 py-3 text-sm font-medium text-green-600">${fmt(item.price)}</td>
        <td class="px-4 py-3 text-right">
          <button class="text-red-600 hover:text-red-700" onclick="removeTemplateRow('${item.id}')" title="Remove">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash2 h-4 w-4"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path><line x1="10" x2="10" y1="11" y2="17"></line><line x1="14" x2="14" y1="11" y2="17"></line></svg>
          </button>
        </td>
        <td class="hidden">
          <input type="hidden" name="template_part_ids[]" value="${item.id}">
        </td>
      </tr>`;
  }

  function renderMobileCard(item) {
    return `
      <li class="p-4" data-id="${item.id}">
        <div class="flex items-start justify-between gap-3">
          <div>
            <p class="font-medium text-gray-900">${item.name}</p>
            <p class="text-xs text-gray-500">${item.sku}</p>
            <div class="mt-2 flex items-center gap-2">
              <span class="rounded-full bg-blue-100 text-blue-700 text-xs px-2 py-0.5">${item.category}</span>
              <span class="rounded-full bg-green-100 text-green-700 text-xs px-2 py-0.5">${fmt(item.price)}</span>
            </div>
          </div>
          <button class="text-red-600 hover:text-red-700" onclick="removeTemplateRow('${item.id}')" title="Remove">✕</button>
        </div>
        <input type="hidden" name="template_part_ids[]" value="${item.id}">
      </li>`;
  }

  // -------- View sync (hide header when empty) --------
  function syncTemplateViews() {
    const tbody = document.getElementById('templatePartsTbody');
    const thead = document.getElementById('templateTableHead');
    const count = tbody ? tbody.querySelectorAll('tr').length : 0;
    const empty = document.getElementById('templateEmpty');
    const tableWrap = document.getElementById('templateTableWrap');
    const mobile = document.getElementById('templateMobile');
    const countEl = document.getElementById('templatePartsCount');

    if (countEl) countEl.textContent = count;

    if (count > 0) {
      empty?.classList.add('hidden');
      tableWrap?.classList.remove('hidden');
      mobile?.classList.remove('hidden');
      thead?.classList.remove('hidden'); // show header only when data
    } else {
      empty?.classList.remove('hidden');
      tableWrap?.classList.add('hidden');
      mobile?.classList.add('hidden');
      thead?.classList.add('hidden'); // hide header
    }
  }

  // -------- Add selected parts --------
  function addSelectedPartsToTemplate() {
    const checks = document.querySelectorAll('.part-checkbox:checked');
    if (!checks.length) return;

    const tbody = document.getElementById('templatePartsTbody');
    const cards = document.getElementById('templatePartsCards');
    if (!tbody || !cards) return;

    const existing = new Set(Array.from(tbody.querySelectorAll('tr')).map(tr => tr.getAttribute('data-id')));

    checks.forEach(cb => {
      const item = {
        id: cb.dataset.id,
        name: cb.dataset.name,
        sku: cb.dataset.sku,
        brand: cb.dataset.brand,
        category: cb.dataset.category,
        price: cb.dataset.price
      };
      if (!existing.has(item.id)) {
        tbody.insertAdjacentHTML('beforeend', renderTableRow(item));
        cards.insertAdjacentHTML('beforeend', renderMobileCard(item));
      }
    });

    closePartsModal();
    syncTemplateViews();
    setupRowDragging(); // <-- enable drag after adding rows
  }

  // -------- Remove rows --------
  function removeTemplateRow(id) {
    const tr = document.querySelector(`#templatePartsTbody tr[data-id="${id}"]`);
    if (tr) tr.remove();
    const card = document.querySelector(`#templatePartsCards li[data-id="${id}"]`);
    if (card) card.remove();
    syncTemplateViews();
    setupRowDragging(); // rebind drag if needed
  }

  // -------- Drag & Drop (by handle) --------
  function setupRowDragging() {
    const tbody = document.getElementById('templatePartsTbody');
    if (!tbody) return;

    // Clean old listeners by cloning rows (optional but keeps things tidy)
    Array.from(tbody.querySelectorAll('tr')).forEach(tr => {
      tr.setAttribute('draggable', 'true');
    });

    let draggedRow = null;
    let dragStartOnHandle = false;

    // Delegate events on tbody for performance
    tbody.addEventListener('dragstart', (e) => {
      const tr = e.target.closest('tr');
      if (!tr) return;

      // allow dragging only if started on the handle
      dragStartOnHandle = !!e.target.closest('.drag-handle');
      if (!dragStartOnHandle) {
        e.preventDefault();
        return;
      }

      draggedRow = tr;
      tr.classList.add('opacity-50');
      e.dataTransfer.effectAllowed = 'move';
      e.dataTransfer.setData('text/plain', tr.dataset.id || ''); // Firefox needs data
    });

    tbody.addEventListener('dragend', () => {
      if (draggedRow) draggedRow.classList.remove('opacity-50');
      draggedRow = null;
      dragStartOnHandle = false;
    });

    tbody.addEventListener('dragover', (e) => {
      if (!draggedRow) return;
      e.preventDefault(); // allow drop
      const target = e.target.closest('tr');
      if (!target || target === draggedRow) return;

      const rect = target.getBoundingClientRect();
      const isAfter = (e.clientY - rect.top) / rect.height > 0.5;

      if (isAfter) {
        tbody.insertBefore(draggedRow, target.nextSibling);
      } else {
        tbody.insertBefore(draggedRow, target);
      }
    });
  }

  // Init
  document.addEventListener('DOMContentLoaded', () => {
    syncTemplateViews();
    setupRowDragging();
  });
</script>

