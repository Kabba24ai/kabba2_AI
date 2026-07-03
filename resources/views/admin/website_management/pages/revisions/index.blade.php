@extends('admin.layouts.app')

@section('title', 'Revision History — ' . $page->title)

@section('content')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6"
     x-data="revisionIndex()">

    {{-- Header --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center gap-3">
            <a href="javascript:history.back()"
               class="text-gray-400 hover:text-gray-600 transition-colors">
                <x-heroicon-o-arrow-left class="w-5 h-5"/>
            </a>
            <div>
                <h1 class="text-lg font-semibold text-gray-900">Revision History</h1>
                <p class="text-sm text-gray-400">
                    <span class="font-medium text-gray-600">{{ $page->title }}</span>
                    &mdash; <code class="text-xs bg-gray-100 px-1 rounded">/{{ $page->slug }}</code>
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <span x-show="selected.length === 2"
                  class="text-xs text-gray-500 hidden sm:inline">2 selected</span>
            <button x-show="selected.length === 2"
                    @click="compareSelected()"
                    class="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-md text-sm font-medium transition-colors">
                <x-heroicon-o-arrows-right-left class="w-4 h-4"/>
                Compare Selected
            </button>
            <span class="text-xs text-gray-400" x-show="selected.length === 1">Select one more to compare</span>
        </div>
    </div>

    {{-- Status ribbon --}}
    @include('admin.website_management.pages.partials._publish_toolbar', ['page' => $page])

    {{-- Revision Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        @if($revisions->isEmpty())
            <div class="py-14 text-center">
                <x-heroicon-o-clock class="w-10 h-10 text-gray-300 mx-auto mb-2"/>
                <p class="text-gray-500 text-sm">No revisions yet.</p>
                <p class="text-gray-400 text-xs mt-1">Revisions are created when you save a draft or publish.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 w-10">
                                <span class="sr-only">Select</span>
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">#</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">By</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Notes</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($revisions as $revision)
                        <tr class="hover:bg-gray-50/50 transition-colors"
                            :class="selected.includes('{{ $revision->unique_id }}') ? 'bg-blue-50/40' : ''">
                            <td class="px-4 py-3 text-center">
                                <input type="checkbox"
                                       value="{{ $revision->unique_id }}"
                                       @change="toggleSelect('{{ $revision->unique_id }}')"
                                       :checked="selected.includes('{{ $revision->unique_id }}')"
                                       :disabled="selected.length >= 2 && !selected.includes('{{ $revision->unique_id }}')"
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-400 disabled:opacity-40">
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-mono font-semibold text-gray-800">#{{ $revision->revision_number }}</span>
                                @if($revision->id === $page->published_revision_id)
                                    <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-green-100 text-green-700">LIVE</span>
                                @endif
                                @if($revision->trashed())
                                    <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-red-100 text-red-500">DELETED</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                <span title="{{ $revision->created_at->format('Y-m-d H:i:s') }}">
                                    {{ $revision->created_at->diffForHumans() }}
                                </span>
                                <br>
                                <span class="text-xs text-gray-400">{{ $revision->created_at->format('M j, Y') }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ $revision->author?->name ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                @if($revision->is_published_snapshot)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Published</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Draft</span>
                                @endif
                                @if($revision->restored_from_revision_id)
                                    <span class="mt-0.5 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] bg-amber-100 text-amber-700">Restore</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-500 max-w-xs truncate">
                                {{ $revision->change_summary ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('admin.website-management.pages.revisions.show', [$page->unique_id, $revision->unique_id]) }}"
                                       class="text-xs text-blue-600 hover:underline">View</a>

                                    @unless($revision->trashed())
                                    <span class="text-gray-300">|</span>
                                    <button @click="restore('{{ $revision->unique_id }}', {{ $revision->revision_number }})"
                                            class="text-xs text-amber-600 hover:underline">
                                        Restore
                                    </button>
                                    <span class="text-gray-300">|</span>
                                    <button @click="deleteRevision('{{ $revision->unique_id }}', {{ $revision->revision_number }})"
                                            :disabled="'{{ $page->published_revision_id }}' === '{{ $revision->id }}'"
                                            class="text-xs text-red-500 hover:underline disabled:opacity-40 disabled:cursor-not-allowed">
                                        Delete
                                    </button>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-3 border-t border-gray-100">
                {{ $revisions->links() }}
            </div>
        @endif
    </div>
</div>

<script>
function revisionIndex() {
    return {
        selected:  [],
        csrfToken: '{{ csrf_token() }}',
        pageUniqueId: '{{ $page->unique_id }}',

        toggleSelect(uid) {
            const idx = this.selected.indexOf(uid);
            if (idx >= 0) {
                this.selected.splice(idx, 1);
            } else if (this.selected.length < 2) {
                this.selected.push(uid);
            }
        },

        compareSelected() {
            if (this.selected.length !== 2) return;
            const url = new URL('{{ route("admin.website-management.pages.revisions.compare", $page->unique_id) }}', location.href);
            url.searchParams.set('a', this.selected[0]);
            url.searchParams.set('b', this.selected[1]);
            location.href = url.toString();
        },

        async restore(revUniqueId, revNumber) {
            if (!confirm(`Restore page to revision #${revNumber}? A new revision will be created from this state.`)) return;
            const res  = await fetch(`{{ url('admin/website-management/pages/' . $page->unique_id . '/revisions') }}/${revUniqueId}/restore`, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken },
            }).then(r => r.json());
            if (res.success) {
                alert(res.message);
                location.reload();
            } else {
                alert(res.message ?? 'Restore failed.');
            }
        },

        async deleteRevision(revUniqueId, revNumber) {
            if (!confirm(`Delete revision #${revNumber}? This cannot be undone.`)) return;
            const res = await fetch(`{{ url('admin/website-management/pages/' . $page->unique_id . '/revisions') }}/${revUniqueId}`, {
                method: 'DELETE',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken },
            }).then(r => r.json());
            if (res.success) {
                alert(res.message);
                location.reload();
            } else {
                alert(res.message ?? 'Delete failed.');
            }
        },
    };
}
</script>

@endsection
