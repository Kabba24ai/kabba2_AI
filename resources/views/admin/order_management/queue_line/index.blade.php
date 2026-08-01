@extends('admin.layouts.app')

@section('title', 'Queue Line')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Queue Line</h1>
        <p class="text-sm text-gray-500 mt-1">
            What machine do I need to pull for this order? A live view of the equipment still to be prepared.
        </p>
    </div>

    <livewire:queue-line.board />

    {{-- Sticky filters — Store via the app-wide FilterFreezer pattern, and
         the Show: All | 3 Days | Today date window via its own raw
         localStorage key (same convention as the Dispatch and Schedule
         boards — a segmented button group has no form element for
         FilterFreezer to read). Standard board only — the wall board is a
         passive display and never inherits a persisted scope. --}}
    <script>
        document.addEventListener('livewire:initialized', function () {
            const screenKey = 'queue_line_filters';
            const RANGE_KEY = 'queue_line_range_filter';

            const sel = document.getElementById('queue-store-filter');
            if (sel && window.FilterFreezer) {
                const before = sel.value;
                window.FilterFreezer.loadFilters(screenKey, { store: sel });
                if (sel.value !== before) {
                    // Sync the restored DOM value into the Livewire model
                    sel.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }

            // Restore the saved date window (component default is Today, so
            // only a differing saved value needs a round-trip).
            const savedRange = localStorage.getItem(RANGE_KEY);
            if (['all', '3_days', 'today'].includes(savedRange) && savedRange !== 'today') {
                Livewire.dispatch('queue-line-set-range', { range: savedRange });
            }

            // Delegated so Livewire re-renders never detach the persistence
            document.addEventListener('change', function (e) {
                if (e.target && e.target.id === 'queue-store-filter' && window.FilterFreezer) {
                    window.FilterFreezer.saveFilters(screenKey, { store: e.target });
                }
            });
            document.addEventListener('click', function (e) {
                const btn = e.target.closest('[data-queue-range]');
                if (btn) localStorage.setItem(RANGE_KEY, btn.getAttribute('data-queue-range'));
            });
        });
    </script>
@endsection
