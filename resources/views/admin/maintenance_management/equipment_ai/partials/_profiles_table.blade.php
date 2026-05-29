@php
    $pendingForBulk = $profiles->whereNotIn('ai_status', ['completed'])->values();

    $pendingProfilesForBulk = $pendingForBulk->map(function ($p) {
        return [
            'unique_id' => $p->unique_id,
            'make'      => $p->make,
            'model'     => $p->model ?? '',
        ];
    })->values();
@endphp

{{-- ─────────────────────────────────────────────────────────────────────────
     AI Profiles Table — Alpine x-data wraps the whole card so the bulk-run
     state (progress bar, per-row spinners) can update reactively without a
     page reload mid-run.
───────────────────────────────────────────────────────────────────────────── --}}
<div class="rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
     x-data="{
         /* ── State ────────────────────────────────── */
         running:     false,
         cancelled:   false,
         currentIndex: -1,
         doneCount:   0,
         rowStatus:   {},   /* unique_id → 'running' | 'success' | 'error' */
         rowSaved:    {},   /* unique_id → number of specs saved            */
         csrfToken: @js(csrf_token()),


         /* ── Pending profiles list (server-rendered, JSON-safe) ────── */
         pendingProfiles: @js($pendingProfilesForBulk),

         /* ── Computed ─────────────────────────────────────────────── */
         get totalPending()  { return this.pendingProfiles.length; },
         get progressPct()   { return this.totalPending > 0 ? Math.round((this.doneCount / this.totalPending) * 100) : 0; },
         get currentProfile(){ return this.currentIndex >= 0 ? this.pendingProfiles[this.currentIndex] : null; },

         /* ── Run all pending sequentially ─────────────────────────── */
         async runAll() {
             if (this.pendingProfiles.length === 0) return;

             this.running     = true;
             this.cancelled   = false;
             this.doneCount   = 0;
             this.rowStatus   = {};
             this.rowSaved    = {};

             for (let i = 0; i < this.pendingProfiles.length; i++) {
                 if (this.cancelled) break;

                 const profile = this.pendingProfiles[i];
                 this.currentIndex = i;

                 /* Spread to new object — ensures Alpine detects the change */
                 this.rowStatus = { ...this.rowStatus, [profile.unique_id]: 'running' };

                 try {
                     const res = await fetch(
                         '/maintenance-management/equipment-ai/profiles/' + profile.unique_id + '/specifications/generate',
                         {
                             method: 'POST',
                             headers: {
                                 'X-CSRF-TOKEN':  this.csrfToken,
                                 'Accept':        'application/json',
                                 'Content-Type':  'application/json',
                             },
                         }
                     );
                     const data = await res.json();

                     if (data.success) {
                         this.rowStatus = { ...this.rowStatus, [profile.unique_id]: 'success' };
                         this.rowSaved  = { ...this.rowSaved,  [profile.unique_id]: data.saved  };
                     } else {
                         this.rowStatus = { ...this.rowStatus, [profile.unique_id]: 'error' };
                     }
                 } catch (e) {
                     this.rowStatus = { ...this.rowStatus, [profile.unique_id]: 'error' };
                 }

                 this.doneCount++;
             }

             this.running      = false;
             this.currentIndex = -1;

             /* Reload only on natural completion (not cancel) to reflect new statuses */
             if (!this.cancelled) {
                 window.location.reload();
             }
         },

         cancel() { this.cancelled = true; },
     }">

    {{-- ─── Card Header ────────────────────────────────────────────── --}}
    <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-700">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                    AI Profiles — {{ $selectedCategory->title }}
                </h2>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                    {{ $profiles->count() }} unique make/model profile(s) found
                    @if ($pendingForBulk->count() > 0)
                        · <span class="text-amber-600 dark:text-amber-400">{{ $pendingForBulk->count() }} pending AI research</span>
                    @endif
                </p>
            </div>

            {{-- "Research Specs for All" — disabled once running or if nothing pending --}}
            <button @click="runAll()"
                :disabled="running || totalPending === 0"
                class="inline-flex shrink-0 items-center gap-2 rounded-lg border border-purple-400 px-4 py-2 text-sm font-medium text-purple-700 hover:bg-purple-50 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-50 dark:border-purple-500 dark:text-purple-400 dark:hover:bg-purple-900/20">
                <svg x-show="running" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <x-heroicon-o-sparkles x-show="!running" class="h-4 w-4" />
                <span x-text="running
                    ? 'Processing ' + (currentIndex + 1) + ' of ' + totalPending + '…'
                    : 'Research and Create Specs for All'">
                </span>
            </button>
        </div>
    </div>

    {{-- ─── Progress Panel (visible while running or just after) ──────── --}}
    <div x-show="running || doneCount > 0" x-cloak
         class="border-b border-gray-100 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-800/50">

        <div class="flex items-center justify-between mb-2">
            {{-- Current item label --}}
            <p class="text-sm text-gray-700 dark:text-gray-300">
                <span x-show="running">
                    Researching:
                    <strong x-text="currentProfile ? currentProfile.make + ' ' + currentProfile.model : '…'"></strong>
                </span>
                <span x-show="!running && doneCount > 0" class="text-green-700 dark:text-green-400 font-medium">
                    ✓ Complete — <span x-text="doneCount"></span> profile(s) processed. Reloading…
                </span>
            </p>
            {{-- Cancel button --}}
            <button x-show="running" @click="cancel()"
                class="rounded border border-red-300 px-2.5 py-1 text-xs font-medium text-red-600 hover:bg-red-50 dark:border-red-600 dark:text-red-400 dark:hover:bg-red-900/20">
                Cancel
            </button>
        </div>

        {{-- Progress bar --}}
        <div class="w-full rounded-full bg-gray-200 h-2 dark:bg-gray-700">
            <div class="h-2 rounded-full bg-indigo-600 transition-all duration-500 dark:bg-indigo-500"
                 :style="'width: ' + progressPct + '%'"></div>
        </div>
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
            <span x-text="doneCount"></span> of <span x-text="totalPending"></span> completed
            (<span x-text="progressPct"></span>%)
        </p>
    </div>

    {{-- ─── Table ────────────────────────────────────────────────────── --}}
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100 text-sm dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Make</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Model</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">AI Status</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Last AI Update</th>
                    <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Specs</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-700 dark:bg-gray-900">
                @forelse ($profiles as $profile)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors"
                        :class="rowStatus['{{ $profile->unique_id }}'] === 'running' ? 'bg-yellow-50 dark:bg-yellow-900/10' :
                                rowStatus['{{ $profile->unique_id }}'] === 'success' ? 'bg-green-50 dark:bg-green-900/10' :
                                rowStatus['{{ $profile->unique_id }}'] === 'error'   ? 'bg-red-50 dark:bg-red-900/10'     : ''">

                        {{-- Make --}}
                        <td class="px-5 py-4 font-medium text-gray-900 dark:text-gray-100">
                            {{ $profile->make }}
                        </td>

                        {{-- Model --}}
                        <td class="px-5 py-4 text-gray-700 dark:text-gray-300">
                            {{ $profile->model ?: '—' }}
                        </td>

                        {{-- AI Status — reactive during bulk run ──────────── --}}
                        <td class="px-5 py-4">
                            {{-- Spinner while this row is being processed --}}
                            <span x-show="rowStatus['{{ $profile->unique_id }}'] === 'running'" x-cloak
                                  class="inline-flex items-center gap-1.5 rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-semibold text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">
                                <svg class="h-3 w-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Processing…
                            </span>
                            {{-- Green on success --}}
                            <span x-show="rowStatus['{{ $profile->unique_id }}'] === 'success'" x-cloak
                                  class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                ✓ Completed
                            </span>
                            {{-- Red on error --}}
                            <span x-show="rowStatus['{{ $profile->unique_id }}'] === 'error'" x-cloak
                                  class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                ✗ Failed
                            </span>
                            {{-- Default server-rendered badge --}}
                            <span x-show="!rowStatus['{{ $profile->unique_id }}']"
                                  class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $profile->statusBadgeClass() }}">
                                {{ $profile->statusLabel() }}
                            </span>
                        </td>

                        {{-- Last AI Update --}}
                        <td class="px-5 py-4 text-gray-500 dark:text-gray-400">
                            {{ $profile->last_ai_update_at ? $profile->last_ai_update_at->diffForHumans() : '—' }}
                        </td>

                        {{-- Specs count — updates on success ────────────── --}}
                        <td class="px-5 py-4 text-center">
                            {{-- Newly saved count shown right after success --}}
                            <span x-show="rowStatus['{{ $profile->unique_id }}'] === 'success'" x-cloak
                                  class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-700 dark:bg-green-500/10 dark:text-green-400"
                                  x-text="(rowSaved['{{ $profile->unique_id }}'] || 0) + ' specs'">
                            </span>
                            {{-- Default server-rendered count --}}
                            <span x-show="rowStatus['{{ $profile->unique_id }}'] !== 'success'">
                                @php $specCount = $profile->specifications->count(); @endphp
                                @if ($specCount > 0)
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-700 dark:bg-green-500/10 dark:text-green-400">
                                        {{ $specCount }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">0</span>
                                @endif
                            </span>
                        </td>

                        {{-- Actions --}}
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.maintenance-management.equipment-ai.profiles.specifications.index', $profile->unique_id) }}"
                                    class="inline-flex items-center gap-1.5 rounded-md border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                                    title="View / Edit Specifications">
                                    <x-heroicon-o-clipboard-document-list class="h-4 w-4" />
                                    Specs
                                </a>
                                <form method="POST"
                                    action="{{ route('admin.maintenance-management.equipment-ai.profiles.delete', $profile->unique_id) }}"
                                    class="inline"
                                    onsubmit="return confirm('Delete AI profile for {{ addslashes($profile->make) }} {{ addslashes($profile->model) }}?\n\nAll specifications will also be deleted.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="inline-flex items-center justify-center rounded-md p-1.5 text-red-500 hover:text-red-600 dark:text-red-400 dark:hover:text-red-300"
                                        title="Delete Profile">
                                        <x-heroicon-o-trash class="h-4 w-4" />
                                    </button>
                                </form>
                            </div>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-gray-400 dark:text-gray-500">
                            <x-heroicon-o-cpu-chip class="mx-auto mb-2 h-8 w-8 text-gray-300 dark:text-gray-600" />
                            No AI profiles found for this category.
                            Use <strong>Scan Category</strong> above to generate them from existing equipment records.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
