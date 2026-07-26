@extends('admin.layouts.app')

@section('title', 'Problem Templates')

@section('content')
    @include('flash::message')

    <div class="max-w-5xl mx-auto px-4 py-6">
        @include('admin.service_management.problem_templates.partials._nav', ['active' => 'templates'])

        <div class="mb-5 mt-4">
            <h1 class="text-xl font-bold text-gray-900">Problem Templates</h1>
            <p class="text-sm text-gray-500 mt-0.5">Reusable, ordered problem sets. Edit any template — changes flow to every equipment unit using it.</p>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl divide-y divide-gray-100">
            @forelse ($templates as $t)
                <div class="flex items-center justify-between gap-4 px-5 py-4" data-tpl-row="{{ $t->id }}">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.service-management.problem-templates.builder', $t) }}" class="text-sm font-semibold text-gray-900 hover:text-blue-600 hover:underline">{{ $t->name }}</a>
                            @unless ($t->is_active)
                                <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 border border-gray-200">Inactive</span>
                            @endunless
                        </div>
                        @if ($t->description)
                            <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $t->description }}</p>
                        @endif
                        <p class="text-xs text-gray-400 mt-0.5">
                            {{ $t->item_count }} {{ $t->item_count === 1 ? 'problem' : 'problems' }}
                            · attached to {{ $t->unit_count }} {{ $t->unit_count === 1 ? 'unit' : 'units' }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ route('admin.service-management.problem-templates.builder', $t) }}"
                           class="px-3 py-1.5 text-sm rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50">Edit</a>
                        <button type="button" data-tpl-toggle data-active="{{ $t->is_active ? '1' : '0' }}"
                                class="px-3 py-1.5 text-sm rounded-md border {{ $t->is_active ? 'border-gray-300 text-gray-600 hover:bg-gray-50' : 'border-green-300 text-green-700 hover:bg-green-50' }}">
                            {{ $t->is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                        <button type="button" data-tpl-delete class="px-3 py-1.5 text-sm rounded-md border border-red-200 text-red-600 hover:bg-red-50">Delete</button>
                    </div>
                </div>
            @empty
                <div class="px-5 py-14 text-center text-sm text-gray-400">
                    No templates yet.
                    <a href="{{ route('admin.service-management.problem-templates.create') }}" class="text-blue-600 hover:underline">Create your first template</a>.
                </div>
            @endforelse
        </div>
    </div>
@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const CSRF = @json(csrf_token());
    const UPDATE_URL  = @json(route('admin.service-management.problem-templates.update', ['profile' => '__ID__']));
    const DESTROY_URL = @json(route('admin.service-management.problem-templates.destroy', ['profile' => '__ID__']));

    async function req(method, url, body) {
        const res = await fetch(url, { method, headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }, body: body ? JSON.stringify(body) : undefined });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || data.success === false) throw new Error(data.message || 'Request failed.');
        return data;
    }
    const toast = (m, ok = true) => { if (window.notyf) (ok ? notyf.success(m) : notyf.error(m)); };

    document.querySelector('[data-tpl-row]') && document.body.addEventListener('click', async function (e) {
        const row = e.target.closest('[data-tpl-row]'); if (!row) return;
        const id = row.dataset.tplRow;

        if (e.target.matches('[data-tpl-toggle]')) {
            const activate = e.target.dataset.active !== '1';
            try {
                await req('PUT', UPDATE_URL.replace('__ID__', id), { is_active: activate });
                window.location.reload();
            } catch (err) { toast(err.message, false); }
        } else if (e.target.matches('[data-tpl-delete]')) {
            if (e.target.dataset.armed !== '1') {
                e.target.dataset.armed = '1'; e.target.textContent = 'Confirm?';
                setTimeout(() => { e.target.dataset.armed = ''; e.target.textContent = 'Delete'; }, 3000);
                return;
            }
            try { await req('DELETE', DESTROY_URL.replace('__ID__', id)); row.remove(); toast('Template deleted.'); }
            catch (err) { toast(err.message, false); }
        }
    });
});
</script>
@endpush
