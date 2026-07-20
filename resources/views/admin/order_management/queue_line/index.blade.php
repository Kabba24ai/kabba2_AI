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

    {{-- Sticky Store filter — the app-wide FilterFreezer pattern
         (localStorage per screen key), same mechanism the report screens
         use. Only the STORE selection persists: employees stay on their own
         location between visits, and "All Stores" both applies and persists
         for cross-location planning. Standard board only — the wall board
         is a passive display and never inherits a persisted scope. --}}
    <script>
        document.addEventListener('livewire:initialized', function () {
            const screenKey = 'queue_line_filters';

            const sel = document.getElementById('queue-store-filter');
            if (sel && window.FilterFreezer) {
                const before = sel.value;
                window.FilterFreezer.loadFilters(screenKey, { store: sel });
                if (sel.value !== before) {
                    // Sync the restored DOM value into the Livewire model
                    sel.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }

            // Delegated so Livewire re-renders never detach the persistence
            document.addEventListener('change', function (e) {
                if (e.target && e.target.id === 'queue-store-filter' && window.FilterFreezer) {
                    window.FilterFreezer.saveFilters(screenKey, { store: e.target });
                }
            });
        });
    </script>
@endsection
