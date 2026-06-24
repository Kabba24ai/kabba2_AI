@extends('admin.layouts.app')

@push('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush

@section('title', 'Create Task')

@section('content')

@include('flash::message')
@include('admin.partials.formErrors')

<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.tasks.index') }}" class="text-gray-500 hover:text-gray-700">
        <x-heroicon-o-arrow-left class="w-5 h-5" />
    </a>
    <h3 class="text-xl font-semibold text-gray-800">Create Task</h3>
</div>

<div class="max-w-2xl bg-white rounded-lg border border-gray-200 shadow-sm p-6">
    <form method="POST" action="{{ route('admin.tasks.store') }}">
        @csrf

        {{-- Category --}}
        <div class="mb-5">
            <label class="block text-sm font-medium text-gray-700 mb-1">Task Category <span class="text-red-500">*</span></label>
            <select name="category" required class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                <option value="">Select Task Category</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->value }}" {{ old('category') === $cat->value ? 'selected' : '' }}>{{ $cat->label() }}</option>
                @endforeach
            </select>
        </div>

        {{-- Title --}}
        <div class="mb-5">
            <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
            <input type="text" name="title" value="{{ old('title') }}" required
                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500"
                placeholder="What needs to be done?">
        </div>

        {{-- Assign Equipment to Task --}}
        <div class="mb-5 rounded-md border border-gray-200 bg-gray-50 p-4">
            <label class="block text-sm font-semibold text-gray-700 mb-3">Assign Equipment to Task <span class="text-xs font-normal text-gray-400">(optional)</span></label>
            <div class="grid grid-cols-2 gap-3 mb-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Equipment Category</label>
                    <select id="equip_category_filter" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                        <option value="">All Categories</option>
                        @foreach ($productCategories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Equipment Unit</label>
                    <select id="equip_unit_select" name="related_equipment_id" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                        <option value="">Select Equipment</option>
                    </select>
                </div>
            </div>
            <div id="equip_summary" class="hidden text-xs text-gray-600 bg-white border border-gray-200 rounded px-3 py-2">
                <span id="equip_summary_text"></span>
            </div>
        </div>

        {{-- Description --}}
        <div class="mb-5">
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea name="description" rows="3"
                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500"
                placeholder="Optional details...">{{ old('description') }}</textarea>
        </div>

        {{-- Priority + Status --}}
        <div class="grid grid-cols-2 gap-4 mb-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Priority <span class="text-red-500">*</span></label>
                <select name="priority" required class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                    @foreach ($priorities as $pri)
                        <option value="{{ $pri->value }}" {{ old('priority', 'normal') === $pri->value ? 'selected' : '' }}>{{ $pri->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                <select name="status" required class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                    @foreach ($statuses as $st)
                        <option value="{{ $st->value }}" {{ old('status', 'open') === $st->value ? 'selected' : '' }}>{{ $st->label() }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Assigned To + Due Date --}}
        <div class="grid grid-cols-2 gap-4 mb-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Assign To</label>
                <select name="assigned_to_user_id" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                    <option value="">Unassigned</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" {{ old('assigned_to_user_id') == $user->id ? 'selected' : '' }}>{{ $user->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                <input type="text" id="due_date" name="due_date" value="{{ old('due_date') }}"
                    placeholder="Select date & time"
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
            </div>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit"
                class="inline-flex items-center rounded-lg bg-brand-500 px-5 py-2 text-sm font-medium text-white shadow hover:bg-brand-600">
                Create Task
            </button>
            <a href="{{ route('admin.tasks.index') }}" class="inline-flex items-center rounded-lg border border-gray-300 px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Cancel
            </a>
        </div>

    </form>
</div>

@endsection

@push('js')
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.js"></script>
<script>
    flatpickr('#due_date', {
        enableTime: true,
        dateFormat: 'Y-m-d H:i:S',
        altInput: true,
        altFormat: 'F j, Y h:i K',
        minDate: 'today',
        time_24hr: false,
    });

    const allEquipment = @json($equipmentList);

    const categoryFilter = document.getElementById('equip_category_filter');
    const unitSelect     = document.getElementById('equip_unit_select');
    const summary        = document.getElementById('equip_summary');
    const summaryText    = document.getElementById('equip_summary_text');

    function rebuildEquipmentDropdown(selectedEquipId) {
        const catId = categoryFilter.value ? parseInt(categoryFilter.value) : null;
        const filtered = catId ? allEquipment.filter(e => e.category_id === catId) : allEquipment;

        unitSelect.innerHTML = '<option value="">Select Equipment</option>';
        filtered.forEach(e => {
            const opt = document.createElement('option');
            opt.value = e.id;
            opt.textContent = e.equipment_id + ' — ' + e.name;
            if (selectedEquipId && parseInt(selectedEquipId) === e.id) opt.selected = true;
            unitSelect.appendChild(opt);
        });
        updateSummary();
    }

    function updateSummary() {
        const id = parseInt(unitSelect.value);
        const eq = allEquipment.find(e => e.id === id);
        if (eq) {
            let txt = '<strong>' + eq.equipment_id + '</strong> — ' + eq.name;
            if (eq.serial) txt += ' &nbsp;·&nbsp; S/N: ' + eq.serial;
            if (eq.status) txt += ' &nbsp;·&nbsp; Status: ' + eq.status;
            summaryText.innerHTML = txt;
            summary.classList.remove('hidden');
        } else {
            summary.classList.add('hidden');
        }
    }

    categoryFilter.addEventListener('change', () => rebuildEquipmentDropdown(null));
    unitSelect.addEventListener('change', updateSummary);

    rebuildEquipmentDropdown('{{ old('related_equipment_id') }}');
</script>
@endpush
