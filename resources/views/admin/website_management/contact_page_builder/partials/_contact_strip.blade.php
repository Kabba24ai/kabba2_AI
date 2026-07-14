{{-- ── Contact Strip (GLOBAL shared component) ─────────────────────────────
     Content lives in ONE place: Home Page Builder → Contact Strip.
     The contact page owns nothing but the show/hide state below. --}}
@php
    $routePrefix = $routePrefix ?? 'admin.website-management.contact-builder';
    $isActive    = ($section?->status ?? 'Active') === 'Active';
@endphp

@if($section)
<form method="POST"
      action="{{ route($routePrefix . '.section.update', $section->unique_id) }}"
      data-track-changes
      x-data="{ active: @js($isActive) }">
    @csrf

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h3 class="text-base font-semibold text-gray-900">Show Shared Contact Strip</h3>
            <p class="text-xs text-gray-500 mt-0.5">Controls whether the shared strip appears on the Contact Us page.</p>
        </div>
        <div class="flex items-center gap-3">
            <input type="hidden" name="status"
                   value="{{ $isActive ? 'Active' : 'Inactive' }}"
                   :value="active ? 'Active' : 'Inactive'">
            <div class="flex items-center gap-2 cursor-pointer select-none" @click="active = !active">
                <button type="button"
                        :class="active ? 'bg-green-500' : 'bg-gray-300'"
                        class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-green-400">
                    <span :class="active ? 'translate-x-4' : 'translate-x-0.5'"
                          class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform shadow-sm"></span>
                </button>
                <span class="text-xs font-medium w-12"
                      :class="active ? 'text-green-600' : 'text-gray-400'"
                      x-text="active ? 'Active' : 'Inactive'"></span>
            </div>
            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-md text-sm font-medium shadow-sm">
                Save
            </button>
        </div>
    </div>

    <div class="flex items-start gap-2 rounded-md border border-blue-200 bg-blue-50 px-3 py-2.5">
        <x-heroicon-o-information-circle class="h-5 w-5 text-blue-500 shrink-0 mt-0.5" />
        <p class="text-sm text-blue-800">
            Contact Strip content is managed globally from
            <a href="{{ route('admin.website-management.home-builder.index', ['tab' => 'contact_strip']) }}"
               class="font-semibold underline text-blue-900 hover:text-blue-700">Home Page Builder → Contact Strip</a>.
            This setting only controls whether the shared strip appears on the Contact Us page.
        </p>
    </div>

</form>
@else
    <div class="flex items-center gap-2 bg-yellow-50 border border-yellow-200 rounded-md px-4 py-3 text-sm text-yellow-800">
        <x-heroicon-o-exclamation-triangle class="w-4 h-4 shrink-0"/>
        No contact strip section found. Run the contact page seeder first.
    </div>
@endif
