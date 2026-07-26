{{-- Responsibility Decisions master data — business-managed list that drives
     the Service Ticket Responsibility stage. financial_path + approval_type are
     enforced today; the remaining flags are reserved configuration. --}}
<div x-data="responsibilityDecisions()" class="p-6">
    {{-- Local toast (this component is nested; it does not share serviceMaster()'s toast) --}}
    <div x-show="toast.show" x-cloak x-transition
         :class="toast.type === 'error' ? 'bg-red-600' : 'bg-green-600'"
         class="fixed top-4 right-4 z-[10000] text-white text-sm px-4 py-2 rounded-lg shadow-lg"
         x-text="toast.message"></div>

    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Responsibility Decisions</h2>
            <p class="text-sm text-gray-500">The options shown at the ticket Responsibility stage. Keys are immutable; names, mappings, and order are editable.</p>
        </div>
        <button @click="openCreate()"
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New Decision
        </button>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                <tr>
                    <th class="px-4 py-3">Decision</th>
                    <th class="px-4 py-3">Key</th>
                    <th class="px-4 py-3">Financial Path</th>
                    <th class="px-4 py-3">Approval</th>
                    <th class="px-4 py-3">Order</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <template x-for="d in decisions" :key="d.id">
                    <tr :class="!d.is_active && 'bg-gray-50/60'">
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900" x-text="d.name"></div>
                            <div class="text-xs text-gray-400" x-text="d.description"></div>
                        </td>
                        <td class="px-4 py-3"><code class="text-xs text-gray-500" x-text="d.key"></code></td>
                        <td class="px-4 py-3 text-gray-600" x-text="pathLabel(d.financial_path)"></td>
                        <td class="px-4 py-3 text-gray-600" x-text="approvalLabel(d.approval_type)"></td>
                        <td class="px-4 py-3 text-gray-600" x-text="d.sort_order"></td>
                        <td class="px-4 py-3">
                            <span x-show="d.is_active" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Active</span>
                            <span x-show="!d.is_active" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Inactive</span>
                            <span x-show="d.tickets_count > 0" class="ml-1 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-600" x-text="'In use · ' + d.tickets_count"></span>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <button @click="openEdit(d)" class="text-blue-600 hover:underline text-xs font-medium">Edit</button>
                            <button @click="toggleActive(d)" class="ml-3 text-gray-500 hover:underline text-xs font-medium" x-text="d.is_active ? 'Deactivate' : 'Activate'"></button>
                            <button x-show="d.tickets_count === 0 && !d.is_system" @click="remove(d)" class="ml-3 text-red-600 hover:underline text-xs font-medium">Delete</button>
                            <span x-show="d.is_system" class="ml-3 text-xs text-gray-400" title="Canonical concept — cannot be deleted">System</span>
                        </td>
                    </tr>
                </template>
                <tr x-show="decisions.length === 0">
                    <td colspan="7" class="px-4 py-6 text-center text-gray-400">No responsibility decisions yet.</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Create / Edit modal --}}
    <div x-show="showForm" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-500/75 px-4" @click.self="closeForm()">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="px-6 py-4 border-b flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-900" x-text="form.id ? 'Edit Responsibility Decision' : 'New Responsibility Decision'"></h3>
                <button @click="closeForm()" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
            </div>
            <div class="px-6 py-4 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Name <span class="text-red-500">*</span></label>
                    <input type="text" x-model="form.name" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm" placeholder="e.g. Customer Pay">
                </div>
                {{-- Explicit, permanent business identifier — required on create, never editable after. --}}
                <div x-show="!form.id">
                    <label class="block text-xs font-medium text-gray-500 mb-1">System Key <span class="text-red-500">*</span> <span class="text-gray-400">(permanent, lowercase snake_case)</span></label>
                    <input type="text" x-model="form.key" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm" placeholder="e.g. damage_waiver">
                    <p class="text-xs text-gray-400 mt-1">Chosen deliberately and frozen once created — it is never derived from the name.</p>
                </div>
                <div x-show="form.id">
                    <label class="block text-xs font-medium text-gray-500 mb-1">System Key (immutable)</label>
                    <input type="text" :value="form.key" disabled class="w-full border border-gray-200 bg-gray-50 text-gray-400 rounded-md px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Description</label>
                    <textarea x-model="form.description" rows="2" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Financial Path <span class="text-gray-400">(enforced)</span></label>
                        <select x-model="form.financial_path" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-white">
                            <option :value="null">None</option>
                            <template x-for="opt in financialPaths" :key="opt.value">
                                <option :value="opt.value" x-text="opt.label"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Approval Type <span class="text-gray-400">(enforced)</span></label>
                        <select x-model="form.approval_type" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-white">
                            <option :value="null">None</option>
                            <template x-for="opt in approvalTypes" :key="opt.value">
                                <option :value="opt.value" x-text="opt.label"></option>
                            </template>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Badge Color (CSS classes)</label>
                        <input type="text" x-model="form.color" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm" placeholder="bg-orange-100 text-orange-700">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Sort Order</label>
                        <input type="number" min="0" x-model.number="form.sort_order" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Financial Reporting Category <span class="text-gray-400">(reporting metadata — not enforced)</span></label>
                    <input type="text" list="srd-reporting-categories" x-model="form.financial_reporting_category" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm" placeholder="e.g. Customer Damage">
                    <datalist id="srd-reporting-categories">
                        <template x-for="c in reportingCategories" :key="c"><option :value="c"></option></template>
                    </datalist>
                </div>

                <div class="border-t pt-3">
                    <p class="text-xs font-semibold text-gray-500 mb-2">Reserved workflow flags <span class="font-normal text-gray-400">— stored as configuration, not enforced yet</span></p>
                    <div class="grid grid-cols-2 gap-2 text-sm text-gray-700">
                        <label class="inline-flex items-center gap-2"><input type="checkbox" x-model="form.allows_repair"> Allows repair</label>
                        <label class="inline-flex items-center gap-2"><input type="checkbox" x-model="form.requires_diagnostic_fee"> Requires diagnostic fee</label>
                        <label class="inline-flex items-center gap-2"><input type="checkbox" x-model="form.is_terminal_resolution"> Terminal resolution</label>
                        <label class="inline-flex items-center gap-2"><input type="checkbox" x-model="form.requires_customer_authorization"> Requires customer auth</label>
                        <label class="inline-flex items-center gap-2"><input type="checkbox" x-model="form.requires_oem_authorization"> Requires OEM auth</label>
                    </div>
                </div>

                <label class="inline-flex items-center gap-2 text-sm text-gray-700 pt-1"><input type="checkbox" x-model="form.is_active"> Active</label>
            </div>
            <div class="px-6 py-4 border-t flex justify-end gap-3 bg-gray-50 rounded-b-xl">
                <button @click="closeForm()" class="px-4 py-2 rounded-lg text-sm border border-gray-300 bg-white text-gray-700 hover:bg-gray-100">Cancel</button>
                <button @click="save()" class="px-4 py-2 rounded-lg text-sm bg-blue-600 text-white hover:bg-blue-700">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
function responsibilityDecisions() {
    return {
        decisions: @json($responsibilityDecisions),
        financialPaths: @json($financialPathOptions),
        approvalTypes: @json($approvalTypeOptions),
        showForm: false,
        form: {},
        toast: { show: false, message: '', type: 'success' },

        reportingCategories: ['Customer Damage', 'Warranty', 'Internal', 'Goodwill', 'Damage Waiver', 'Other'],

        blankForm() {
            return {
                id: null, key: '', name: '', description: '', color: '',
                financial_path: null, approval_type: null,
                financial_reporting_category: '', sort_order: 0,
                allows_repair: true, requires_diagnostic_fee: false,
                is_terminal_resolution: false, requires_customer_authorization: false,
                requires_oem_authorization: false, is_active: true,
            };
        },

        pathLabel(v) {
            if (!v) return '—';
            const o = this.financialPaths.find(p => p.value === v);
            return o ? o.label : v;
        },
        approvalLabel(v) {
            if (!v) return '—';
            const o = this.approvalTypes.find(p => p.value === v);
            return o ? o.label : v;
        },

        openCreate() { this.form = this.blankForm(); this.showForm = true; },
        openEdit(d) { this.form = JSON.parse(JSON.stringify(d)); this.showForm = true; },
        closeForm() { this.showForm = false; },

        showToast(message, type = 'success') {
            this.toast = { show: true, message, type };
            setTimeout(() => { this.toast.show = false; }, 3000);
        },

        csrf() { return document.querySelector('meta[name="csrf-token"]').content; },

        async save() {
            if (!this.form.name || !this.form.name.trim()) {
                this.showToast('A decision name is required.', 'error');
                return;
            }
            const isEdit = !!this.form.id;
            if (!isEdit && !/^[a-z][a-z0-9_]*$/.test(this.form.key || '')) {
                this.showToast('A valid system key is required (lowercase snake_case).', 'error');
                return;
            }
            const url = isEdit
                ? '{{ route('admin.maintenance-management.service-master.responsibility-decisions.update', ['id' => 'PLACEHOLDER']) }}'.replace('PLACEHOLDER', this.form.id)
                : '{{ route('admin.maintenance-management.service-master.responsibility-decisions.store') }}';
            try {
                const res = await fetch(url, {
                    method: isEdit ? 'PUT' : 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf(), 'Accept': 'application/json' },
                    body: JSON.stringify(this.form),
                });
                const data = await res.json();
                if (!res.ok || !data.success) {
                    this.showToast(data.message || 'Failed to save decision.', 'error');
                    return;
                }
                this.showForm = false;
                await this.reload();
                this.showToast('Decision saved.', 'success');
            } catch (e) {
                this.showToast('Error saving decision.', 'error');
            }
        },

        async toggleActive(d) {
            const payload = JSON.parse(JSON.stringify(d));
            payload.is_active = !d.is_active;
            const url = '{{ route('admin.maintenance-management.service-master.responsibility-decisions.update', ['id' => 'PLACEHOLDER']) }}'.replace('PLACEHOLDER', d.id);
            try {
                const res = await fetch(url, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf(), 'Accept': 'application/json' },
                    body: JSON.stringify(payload),
                });
                const data = await res.json();
                if (!res.ok || !data.success) { this.showToast(data.message || 'Failed to update.', 'error'); return; }
                await this.reload();
                this.showToast(payload.is_active ? 'Decision activated.' : 'Decision deactivated.', 'success');
            } catch (e) { this.showToast('Error updating decision.', 'error'); }
        },

        async remove(d) {
            if (!confirm('Delete "' + d.name + '"? This cannot be undone.')) return;
            const url = '{{ route('admin.maintenance-management.service-master.responsibility-decisions.destroy', ['id' => 'PLACEHOLDER']) }}'.replace('PLACEHOLDER', d.id);
            try {
                const res = await fetch(url, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': this.csrf(), 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (!res.ok || !data.success) { this.showToast(data.message || 'Cannot delete.', 'error'); return; }
                await this.reload();
                this.showToast('Decision deleted.', 'success');
            } catch (e) { this.showToast('Error deleting decision.', 'error'); }
        },

        async reload() {
            const res = await fetch('{{ route('admin.maintenance-management.service-master.index') }}?tab=responsibility&_ajax=1', {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (data.success) this.decisions = data.responsibilityDecisions;
        },
    };
}
</script>
