<div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 mb-6">

                {{-- Header --}}
        <div class="flex items-center justify-between mb-4 flex-wrap gap-3">

            <div class="flex items-center gap-2">

                <div class="w-9 h-9 rounded-lg bg-red-100 flex items-center justify-center">
                    <x-heroicon-o-bell-alert class="w-5 h-5 text-red-600" />
                </div>

                <div>
                    <h2 class="text-lg font-semibold text-gray-900">
                        Alerts & Activities
                    </h2>

                    <p class="text-sm text-gray-500">
                        Recent system activities and reminders
                    </p>
                </div>

            </div>

            {{-- Assignee Filter Chips --}}
            <div id="assignee-filters" class="flex items-center gap-2 flex-wrap"></div>

            {{-- Right Side --}}
            <div class="flex items-center gap-3">

                <button
                    type="button"
                    onclick="openCallNeededModal()"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-medium shadow-sm hover:bg-red-700 transition-all">

                    <x-heroicon-o-phone class="w-4 h-4" />

                    Call Needed
                </button>

                <span id="call-needed-count"
                    class="px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                    0 New
                </span>

            </div>

        </div>


                {{-- Alerts List --}}
        <div id="call-needed-list"
            class="space-y-3 max-h-[420px] overflow-y-auto pr-1">
        </div>

</div>

@include('admin.dashboard.partials._call_needed_modal')
