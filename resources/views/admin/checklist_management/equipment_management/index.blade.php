@extends('admin.layouts.app')

@section('title', 'Equipment Management')

@push('css')
@endpush

@section('content')

    @include('flash::message')
    @if (!empty($selectedEquipmentId))
        @include('admin.wait_list.partials.banner', ['equipment' => \App\Models\MaintenanceManagement\Equipment::find($selectedEquipmentId)])
    @endif
    @include('admin.partials.formErrors')
    {{-- Phase 3A — Three-Column Rental Ready Workspace.
         xl+ : left navigator (~24%) · center inspection (~48%) · right
               latest-completed comparison (~28%), center primary, right sticky.
         < xl : center is full-width primary; the navigator becomes a left
               DRAWER and the previous inspection a right SLIDE-OVER — never
               three squeezed columns on an iPad-width display. --}}

    {{-- Mobile / tablet toolbar (hidden on xl where both panels are always visible) --}}
    <div class="xl:hidden mb-4 flex items-center gap-2">
        <button type="button" onclick="openEquipmentDrawer()"
            class="inline-flex items-center gap-2 h-9 px-3 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="3" y1="6" x2="21" y2="6" /><line x1="3" y1="12" x2="21" y2="12" /><line x1="3" y1="18" x2="21" y2="18" />
            </svg>
            Browse Equipment
        </button>
        <button type="button" id="openPreviousBtnMobile" onclick="openPreviousPanel()" disabled
            class="inline-flex items-center gap-2 h-9 px-3 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 3v5h5" /><path d="M3.05 13A9 9 0 1 0 6 5.3L3 8" /><path d="M12 7v5l4 2" />
            </svg>
            Previous Inspection
        </button>
    </div>

    {{-- Backdrops for the drawer / slide-over (below xl only) --}}
    <div id="equipmentDrawerBackdrop" aria-hidden="true" class="hidden xl:hidden fixed inset-0 z-30 bg-black/40" onclick="closeEquipmentDrawer()"></div>
    <div id="previousPanelBackdrop" aria-hidden="true" class="hidden xl:hidden fixed inset-0 z-30 bg-black/40" onclick="closePreviousPanel()"></div>

    <div class="">
        <div class="xl:grid xl:grid-cols-[minmax(0,24fr)_minmax(0,48fr)_minmax(0,28fr)] xl:gap-6 xl:items-start">
            <!-- LEFT: Equipment navigator (drawer below xl) -->
            <aside id="equipmentNavigator" tabindex="-1" aria-label="Equipment navigator"
                class="fixed inset-y-0 left-0 z-40 w-[92%] max-w-sm -translate-x-full transition-transform duration-200 ease-out overflow-y-auto bg-gray-50 xl:bg-transparent p-4 xl:p-0
                       xl:static xl:z-auto xl:w-auto xl:max-w-none xl:translate-x-0 xl:overflow-visible">
                <div class="bg-white rounded-md shadow-sm border border-gray-200 p-6">
                    <div class="xl:hidden flex justify-end mb-2">
                        <button type="button" onclick="closeEquipmentDrawer()" data-panel-close aria-label="Close equipment navigator" class="p-1 text-gray-400 hover:text-gray-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-semibold text-gray-900">Select Equipment</h2>
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2">
                                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3" />
                            </svg>
                            <span id="equipmentCount">0 of 0</span>
                        </div>
                    </div>

                    <!-- Search -->
                    <div class="relative mb-4">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z"></path>
                            </svg>
                        </div>
                        <input type="text" id="searchInput" oninput="applyFilters()" placeholder="Search equipment..."
                            class="w-full border pl-10 pr-3 py-2 rounded-md text-sm">
                    </div>

                    <!-- Category + Status row -->
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1 block">Category</label>
                            <select id="categoryFilter" class="w-full border px-3 py-2 rounded-md text-sm"
                                onchange="applyFilters()">
                                <option value="All Categories">All Categories</option>
                                @foreach ($categories as $id => $name)
                                    <option value="{{ $name }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1 block">Status</label>
                            <select class="w-full border px-3 py-2 rounded-md text-sm" id="statusFilter"
                                onchange="applyFilters()">
                                <option>All</option>
                                <option>Damaged</option>
                                <option>Maint. Hold</option>
                                <option>Rented</option>
                                <option>Available</option>
                                <option>Service Due</option>
                                <option>Service OverDue</option>
                            </select>
                        </div>
                    </div>

                    <!-- Store Location filter -->
                    <div class="mb-4">
                        <label class="text-sm font-medium text-gray-700 mb-2 block">Store Location</label>
                        {{-- Hidden span ensures Tailwind bundles active-store classes --}}
                        <span class="hidden bg-green-100 text-green-800 border-green-300 text-gray-900"></span>
                        <div class="flex flex-wrap gap-2" id="storeFilterGroup">
                            <button type="button"
                                class="store-filter-btn px-3 py-1.5 rounded-full text-sm font-medium border transition-colors bg-gray-900 text-white border-gray-900"
                                data-store-id="" data-store-type="all">All Stores</button>
                            @foreach($stores as $store)
                                <button type="button"
                                    class="store-filter-btn px-3 py-1.5 rounded-full text-sm font-medium border transition-colors bg-white text-gray-700 border-gray-300 hover:border-gray-400"
                                    data-store-id="{{ $store->id }}" data-store-type="store">{{ $store->store_name }}</button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Currently Assigned + Clear Filters row -->
                    <div class="mb-4 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="currentlyAssignedFilter" onchange="applyFilters()"
                                class="w-4 h-4 text-blue-600 rounded border-gray-300 cursor-pointer" checked>
                            <label for="currentlyAssignedFilter" class="text-sm text-gray-700 cursor-pointer select-none flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-purple-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                    <line x1="16" y1="2" x2="16" y2="6"/>
                                    <line x1="8" y1="2" x2="8" y2="6"/>
                                    <line x1="3" y1="10" x2="21" y2="10"/>
                                </svg>
                                Currently Assigned
                            </label>
                        </div>
                        <button type="button" onclick="clearFilters()"
                                class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md text-sm font-medium transition-colors flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Clear Filters
                        </button>
                    </div>
                    {{-- <div id="equipmentList" class="space-y-3 max-h-[1000px] overflow-y-auto"></div> --}}
                    {{-- <div id="equipmentList" class="space-y-3 max-h-[1000px] overflow-y-auto"></div> --}}

                    <div id="equipmentListWrapper" class="max-h-[1000px] overflow-y-auto">

                        <div id="equipmentList" class="space-y-3"></div>

                        <div id="equipmentLoader" class="hidden py-4 px-2">
                            <div class="flex items-center justify-center gap-2 text-sm text-gray-500">

                                <svg class="animate-spin h-5 w-5 text-blue-500"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24">
                                    <circle class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4">
                                    </circle>

                                    <path class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8v8H4z">
                                    </path>
                                </svg>

                                <span>Loading more equipment...</span>

                            </div>
                        </div>

                    </div>


                </div>
            </aside>

            <!-- CENTER: Current inspection (primary) -->
            <div class="min-w-0 mt-4 xl:mt-0">
                <!-- Placeholder -->
                <div id="placeholder" class="bg-white rounded-md shadow-sm border border-gray-200 p-8 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="lucide lucide-clipboard-list w-16 h-16 text-gray-300 mx-auto mb-4">
                        <rect width="8" height="4" x="8" y="2" rx="1" ry="1"></rect>
                        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                        <path d="M12 11h4"></path>
                        <path d="M12 16h4"></path>
                        <path d="M8 11h.01"></path>
                        <path d="M8 16h.01"></path>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Select Equipment to Begin</h3>
                    <p class="text-gray-600">Choose equipment from the list to start the rental ready inspection process.
                    </p>
                </div>
                {{ html()->form('POST', route('admin.checklist-management.equipment-management.store'))->attributes([
                        'autocomplete' => 'off',
                        'data-parsley-validate' => true,
                    ])->acceptsFiles()->open() }}

                <input type="hidden" name="rental_ready_all_qa_json" id="rentalReadyQaJson">
                <input type="hidden" name="equipment_id" id="equipmentId">
                <input type="hidden" name="equipment_status" id="equipment_status">

                <!-- Checklist Container -->
                <div id="checklistContainer" class="hidden bg-white rounded-md shadow-sm border border-gray-200 p-6">
                    {{-- Phase 3A — the Phase 2B context bar is absorbed into this
                         center header: a mode chip states clearly whether this is a
                         NEW inspection or a RESUMED draft, and the history / latest-
                         completed entry points sit alongside it. Selecting a unit is
                         never a context-free blank slate. --}}
                    <div class="mb-6">
                        <div class="flex flex-wrap items-center gap-2 mb-2">
                            <h2 id="checklistTitle" class="text-xl font-semibold text-gray-900">Rental Ready Checklist</h2>
                            <span id="inspectionModeChip" class="hidden inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border"></span>
                            <button type="button" id="openPreviousBtnInline" onclick="openPreviousPanel()"
                                class="xl:hidden inline-flex items-center gap-1 h-7 px-2.5 rounded-md border border-gray-300 bg-white text-xs font-medium text-gray-600 hover:bg-gray-50">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v5h5" /><path d="M3.05 13A9 9 0 1 0 6 5.3L3 8" /><path d="M12 7v5l4 2" /></svg>
                                Previous
                            </button>
                            <div class="text-sm text-gray-600 w-full sm:w-auto sm:ml-auto sm:text-right ml-auto"
                                id="progressTop">0 of 10 items completed</div>
                        </div>
                        <div id="inspectionContextLinks" class="hidden mb-4 flex flex-wrap items-center gap-2 text-sm"></div>

                        <!-- Header row -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="text-sm font-medium text-gray-700 mb-1 block required">Inspector Name </label>

                                {!! html()->select(
                                        'inspectorSelect',
                                        $users->sortBy('full_name')->mapWithKeys(fn($user) => [$user->id => $user->full_name])->prepend('Select Inspector', '')->toArray(),
                                        old('inspectorSelect'),
                                    )->id('inspectorSelect')->class(['w-full border px-3 py-2 rounded-md text-sm'])->required() !!}

                            </div>
                            <div id="equipmentHoursNotTracked" class="hidden">
                                <label class="text-sm font-medium text-gray-700 mb-1 block">Equipment Hours</label>
                                <input type="text" value="Not Tracked"
                                    class="w-full border px-3 py-2 rounded-md text-sm bg-gray-100" disabled>
                            </div>
                            <div id="equipmentHoursWrapper">
                                <label class="text-sm font-medium text-gray-700 mb-1 block">Equipment Hours</label>
                                <input id="equipmentHours" name="equipmentHours" type="number"
                                    class="w-full border px-3 py-2 rounded-md text-sm" placeholder="Enter hours"
                                    min="0" step="0.1">
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700 mb-1 block">Inspection Date</label>
                                <input id="inspectionDate" type="date" class="w-full border px-3 py-2 rounded-md text-sm"
                                    readonly>
                            </div>
                        </div>
                    </div>

                    <!-- Dynamic checklist goes here -->
                    <div id="checklistContent" class="space-y-6"></div>

                    <!-- Footer summary with progress bar -->
                    <div class="mt-8 pt-6 border-t border-gray-200" id="footerbutton">
                        <div class="bg-gray-50 rounded-md p-4 mb-6">
                            <h4 class="font-medium text-gray-900 mb-2">Inspection Summary</h4>
                            <div class="mb-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-gray-700">Overall Progress</span>
                                    <span class="text-sm text-gray-600" id="progressBottom">0 of 10 items completed
                                        (0%)</span>
                                </div>
                                <div class="bg-gray-200 rounded-full h-3">
                                    <div id="overallProgressBar"
                                        class="bg-blue-500 h-3 rounded-full transition-all duration-300" style="width:0%">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <button type="submit" id="btnReady" onclick="setStatus('available')" disabled
                                class="text-sm w-full flex items-center justify-center gap-2 px-4 py-2 rounded-md font-medium transition-all bg-gray-100 text-gray-400 cursor-not-allowed">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                                    <path d="m9 11 3 3L22 4" />
                                </svg>
                                Mark as Rental Ready
                            </button>
                            <button type="submit" id="btnDamaged" onclick="setStatus('damaged')" disabled
                                class="text-sm w-full flex items-center justify-center gap-2 px-4 py-2 rounded-md font-medium transition-all bg-gray-100 text-gray-400 cursor-not-allowed">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" />
                                    <path d="M12 9v4" />
                                    <path d="M12 17h.01" />
                                </svg>
                                Mark as Damaged
                            </button>
                            <button type="submit" onclick="setStatus('maintenance')"
                                class="text-sm w-full flex items-center justify-center gap-2 px-4 py-2 rounded-md font-medium bg-gray-600 hover:bg-gray-700 text-white transition-all shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10" />
                                    <polyline points="12 6 12 12 16 14" />
                                </svg>
                                Save Draft
                            </button>
                        </div>
                        <div class="mt-4 space-y-2">
                            <div class="text-sm text-red-600 bg-red-50 p-3 rounded-md hidden" id="reqMsg">Inspector
                                name is required to complete the checklist.</div>
                        </div>

                        <div class="mt-4 space-y-2">
                            <div class="text-sm text-orange-600 bg-orange-50 p-3 rounded-md" id="maint-hold-Msg"> <span
                                    id="total-maint-hold"> 3 </span> item(s) require maintenance before rental ready
                                status.</div>
                        </div>

                    </div>
                </div>

                {{ html()->form()->close() }}

            </div>

            <!-- RIGHT: Latest completed inspection — operational reference only
                 (slide-over below xl, sticky column at xl). NOT the full audit
                 record: the dedicated history + detail pages remain that. -->
            <aside id="previousInspectionPanel" tabindex="-1" aria-label="Previous inspection panel"
                class="fixed inset-y-0 right-0 z-40 w-[92%] max-w-md translate-x-full transition-transform duration-200 ease-out overflow-y-auto bg-gray-50 xl:bg-transparent p-4 xl:p-0
                       xl:static xl:z-auto xl:w-auto xl:max-w-none xl:translate-x-0 xl:overflow-visible xl:sticky xl:top-6">
                <div class="bg-white rounded-md shadow-sm border border-gray-200 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-base font-semibold text-gray-900">Previous Inspection</h2>
                        <button type="button" onclick="closePreviousPanel()" data-panel-close aria-label="Close previous inspection panel" class="xl:hidden p-1 text-gray-400 hover:text-gray-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div id="previousInspectionBody">
                        <p class="text-sm text-gray-400 py-6 text-center">Select equipment to see its most recent completed inspection.</p>
                    </div>
                </div>
            </aside>
        </div>
    </div>
@endsection

@push('js')



    <script>
        document.addEventListener("DOMContentLoaded", () => {
         console.time("FULL_PAGE_LOAD");
            /* ====== CONFIG ====== */
            const REQUIRE_INSPECTOR = true;
            let currentPage = {{ $equipments->currentPage() }};
            let lastPage = {{ $equipments->lastPage() }};
            let totalEquipment = {{ $equipments->total() }};
            let isLoadingMore = false;

            const equipmentLoader = document.getElementById("equipmentLoader");

            /* =================== DATA =================== */
            @php
                // Calculate service status for each equipment
                $equipmentsWithStatus = $equipments->map(function($item) use ($serviceRecords, $pendingBeforeHours, $pendingAfterHours) {
                    $serviceStatus = 'empty';
                    
                    if ($item->serviceTemplate && $item->serviceTemplate->preset && $item->serviceTemplate->templateTasks->isNotEmpty()) {
                        $intervalType = $item->serviceTemplate->preset->interval_type ?? 'hour';
                        $isDateBased = ($intervalType !== 'hour');
                        
                        // Calculate current value
                        if ($isDateBased && $item->date_acquired) {
                            $currentValue = ceil((time() - strtotime($item->date_acquired)) / (60 * 60 * 24));
                        } else {
                            $currentValue = $item->equipment_hours ?? 0;
                        }
                        
                        $intervals = $item->serviceTemplate->preset->intervals ?? [];
                        $tasks = $item->serviceTemplate->templateTasks;
                        
                        $hasOverdue = false;
                        $hasPending = false;
                        $hasNotDue = false;
                        $totalTasks = 0;
                        $completedTasks = 0;
                        
                        foreach ($tasks as $templateTask) {
                            $taskId = $templateTask->task?->id;
                            if (!$taskId) continue;
                            
                            $ints = $templateTask->intervals ?? $templateTask->intervals_json ?? $templateTask->interval ?? [];
                            $arr = [];
                            if (is_array($ints)) {
                                $arr = $ints;
                            } elseif (is_string($ints)) {
                                try { $arr = json_decode($ints, true) ?? []; } catch(\Exception $e) { $arr = []; }
                            } elseif (is_numeric($ints)) {
                                $arr = [$ints];
                            }
                            
                            foreach ($arr as $interval) {
                                $totalTasks++;
                                
                                // Check if this interval is completed
                                $recordKey = $item->id . '_' . $taskId;
                                $records = $serviceRecords[$recordKey] ?? collect();
                                $isCompleted = $records->contains(function($record) use ($interval) {
                                    return $record->interval_value == $interval;
                                });
                                
                                if ($isCompleted) {
                                    $completedTasks++;
                                    continue;
                                }
                                
                                // Calculate status for this interval
                                $before = intval($pendingBeforeHours);
                                $after = intval($pendingAfterHours);
                                $greyThreshold = $interval - $before;
                                $yellowMax = $interval + $after;
                                
                                if ($currentValue < $greyThreshold) {
                                    $hasNotDue = true;
                                } elseif ($currentValue <= $yellowMax) {
                                    $hasPending = true;
                                } else {
                                    $hasOverdue = true;
                                }
                            }
                        }
                        
                        // Determine overall status based on priority
                        if ($hasOverdue) {
                            $serviceStatus = 'overdue';
                        } elseif ($hasPending) {
                            $serviceStatus = 'pending';
                        } elseif ($totalTasks > 0 && $completedTasks === $totalTasks) {
                            $serviceStatus = 'completed';
                        } elseif ($hasNotDue) {
                            $serviceStatus = 'not_due';
                        }
                    }
                    
                    $item->service_status = $serviceStatus;
                    return $item;
                    
                });
            @endphp
            
           console.time("rawEquipmentParse");

            const rawEquipment = @json($equipments->items());
            // Phase 2B — Rental Ready history entry point (built per equipment from unique_id).
            const rrHistoryTemplate = '{{ route('admin.checklist-management.equipment-management.rental-ready-history', ':id') }}';

            console.timeEnd("rawEquipmentParse");

            const icons = {
                damaged: ` <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-red-600" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" />
                                    <path d="M12 9v4" />
                                    <path d="M12 17h.01" />
                    </svg>`,
                maintenance: `<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-yellow-600" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path
                                        d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 1 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94Z" />
                                </svg>`,
                available: `  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-green-500" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                                    <path d="m9 11 3 3L22 4" />
                                </svg>`,
                rented: ` <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-600" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path d="M16 21v-2a4 4 0 0 0-8 0v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>`,
            };

            // Service status icons
            const serviceStatusIcons = {
                overdue: `<a href="/maintenance-management/equipment/service/EQUIPMENT_ID" class="inline-flex items-center gap-1.5 px-2 py-1 bg-red-100 border-2 border-red-300 rounded-lg hover:opacity-80 transition-opacity" title="Service Overdue - Click to manage">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-red-600">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                    <span class="text-xs font-medium text-red-700">Service OverDue</span>
                </a>`,
                pending: `<a href="/maintenance-management/equipment/service/EQUIPMENT_ID" class="inline-flex items-center gap-1.5 px-2 py-1 bg-yellow-100 border-2 border-yellow-300 rounded-lg hover:opacity-80 transition-opacity" title="Service Due - Click to manage">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-yellow-600">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <span class="text-xs font-medium text-yellow-700">Service Due</span>
                </a>`,
                completed: ``,
                not_due: ``,
                empty: ``
            };

            

            // Store location filter state
            let activeStoreId = '';

            function setStoreFilterStyles() {
                document.querySelectorAll('.store-filter-btn').forEach(btn => {
                    const isActive = btn.dataset.storeId === activeStoreId;
                    const base = 'store-filter-btn px-3 py-1.5 rounded-full text-sm font-medium border transition-colors';
                    if (isActive) {
                        if (btn.dataset.storeType === 'all') {
                            btn.className = base + ' bg-gray-900 text-white border-gray-900';
                        } else {
                            btn.className = base + ' bg-green-100 text-green-800 border-green-300';
                        }
                    } else {
                        btn.className = base + ' bg-white text-gray-900 border-gray-300 hover:border-gray-400';
                    }
                });
            }

            document.getElementById('storeFilterGroup').addEventListener('click', function (e) {
                const btn = e.target.closest('.store-filter-btn');
                if (!btn) return;
                activeStoreId = btn.dataset.storeId;
                setStoreFilterStyles();
                applyFilters();
            });

            // Single canonical equipment mapper (used by the initial render,
            // filtering, and infinite scroll — one shape, no drift). Phase 3A
            // adds the latest-completed summary + active-draft flag the left
            // card needs; a draft NEVER overrides the completed result because
            // both come from separate, completion-only readers.
            function mapEquipment(eq) {
                let serviceIcon = serviceStatusIcons[eq.service_status] || serviceStatusIcons.empty;
                serviceIcon = serviceIcon.replace(/EQUIPMENT_ID/g, eq.unique_id);

                return {
                    id: eq.id,
                    unique_id: eq.unique_id,
                    name: eq.equipment_name,
                    model: eq.model,
                    serial: eq.serial_number,
                    equipment_id: eq.equipment_id,
                    category_id: eq.category_id,
                    category: eq.category_name ?? 'N/A',
                    checklist_master_id: eq.checklist_master_id,
                    hours: eq.equipment_hours,
                    lastInspection: eq.last_inspection ?? '',
                    latestCompletedUuid: eq.latest_rental_ready_template?.unique_id ?? null,
                    latestCompletedResult: eq.latest_rental_ready_template?.result ?? null,
                    latestCompletedDate: eq.latest_rental_ready_template?.inspection_date ?? null,
                    latestCompletedInspector: eq.latest_rental_ready_template?.employee_name ?? null,
                    hasDraft: !!eq.latest_draft_rental_ready_template,
                    orderproduct: eq.order_product?.product_name ?? eq.soft_assignments?.[0]?.order_product?.product_name ?? null,
                    orderproductid: eq.order_product?.id ?? null,
                    order_route: eq.order?.view_link ?? null,
                    orderid: eq.order?.id ?? null,
                    badge: eq.status_label,
                    icon: icons[eq.current_status] ?? icons.available,
                    is_tracked: eq.is_tracked ?? 'No',
                    customername: eq.order?.customer_name ?? ' ',
                    serviceStatus: eq.service_status,
                    serviceStatusIcon: serviceIcon,
                    is_assigned: eq.is_assigned ?? 0,
                    store_name: eq.store?.store_name ?? null,
                };
            }
            window.mapEquipment = mapEquipment;

            const equipment = rawEquipment.map(mapEquipment);


            let groups = []; // use 'let' so you can reassign

            function renderChecklist(groups) {}



            /* =================== ELEMENTS =================== */
            const equipmentWrapper = document.getElementById("equipmentListWrapper");
            const equipmentList = document.getElementById("equipmentList");
            const equipmentCount = document.getElementById("equipmentCount");
            const checklistContainer = document.getElementById("checklistContainer");
            const checklistTitle = document.getElementById("checklistTitle");
            const checklistContent = document.getElementById("checklistContent");
            const placeholder = document.getElementById("placeholder");
            const equipmentHoursInput = document.getElementById("equipmentHours");

            const hoursWrapper = document.getElementById("equipmentHoursWrapper");
            const hoursNotTracked = document.getElementById("equipmentHoursNotTracked");
            const inspectionDateInput = document.getElementById("inspectionDate");
            const progressTop = document.getElementById("progressTop");
            const progressBottom = document.getElementById("progressBottom");
            const bar = document.getElementById("overallProgressBar");
            const btnReady = document.getElementById("btnReady");
            const btnDamaged = document.getElementById("btnDamaged");
            const reqMsg = document.getElementById("reqMsg");
            const inspectorSelect = document.getElementById("inspectorSelect");

            /* =================== INIT =================== */
            // equipmentCount.textContent = `${equipment.length} of ${equipment.length}`;
            equipmentCount.textContent = `${equipment.length} of ${totalEquipment}`;

            inspectionDateInput.value = new Date().toISOString().slice(0, 10);

            equipmentWrapper.addEventListener("scroll", () => {
                    const nearBottom =
                    equipmentWrapper.scrollTop + equipmentWrapper.clientHeight >=
                    equipmentWrapper.scrollHeight - 300;

                    if (nearBottom) {
                        loadMoreEquipment();
                    }
                });


            window.applyFilters = async function () {

                currentPage = 1;

                equipmentList.innerHTML = "";

                equipmentLoader.classList.remove("hidden");

                try {

                    const search = document.getElementById("searchInput").value;
                    const category = document.getElementById("categoryFilter").value;
                    const status = document.getElementById("statusFilter").value;
                    const currentlyAssigned = document.getElementById("currentlyAssignedFilter").checked ? '1' : '0';

                    const response = await fetch(
                        `?page=1&search=${search}&category=${category}&status=${status}&currently_assigned=${currentlyAssigned}&store_id=${activeStoreId}`,
                        {
                            headers: {
                                "X-Requested-With": "XMLHttpRequest"
                            }
                        }
                    );

                    const result = await response.json();

                    equipment.length = 0;

                    const newItems = result.data.map(mapEquipment);

                    equipment.push(...newItems);

                    totalEquipment = result.total;
                    lastPage = result.last_page;

                    renderEquipment();

                } catch (error) {

                    console.log("Filter error:", error);

                }

                equipmentLoader.classList.add("hidden");
            };

          

            window.clearFilters = async function () {

                    document.getElementById("searchInput").value = "";
                    document.getElementById("categoryFilter").selectedIndex = 0;
                    document.getElementById("statusFilter").selectedIndex = 0;
                    document.getElementById("currentlyAssignedFilter").checked = true;

                    // Reset store location filter
                    activeStoreId = '';
                    setStoreFilterStyles();

                    await applyFilters();
                };
           // ===== APPLY URL FILTER FIRST =====
                    const params = new URLSearchParams(window.location.search);
                    const typeFromUrl = params.get('type');

                    if (typeFromUrl) {

                        const decodedType = decodeURIComponent(typeFromUrl);

                        const statusSelect = document.getElementById('statusFilter');

                        for (let i = 0; i < statusSelect.options.length; i++) {

                            if (statusSelect.options[i].text.trim() === decodedType.trim()) {

                                statusSelect.selectedIndex = i;
                                break;
                            }
                        }

                        // CALL BACKEND FILTER
                        applyFilters();
                    }


            const selectedId = '{{ $selectedEquipmentId }}';
          
            @if($selectedEquipmentId)
                const selectedEquipmentId = @json($selectedEquipmentId);
                renderEquipment(selectedEquipmentId);


                const selectedEquipmentName = @json($selectedEquipmentName);
              
                 if (selectedEquipmentName) {
                    const input = document.getElementById("searchInput");
                    input.value = selectedEquipmentName;

                    input.dispatchEvent(new Event('input')); 
                }

            @else
                if (!typeFromUrl) { applyFilters(); }
            @endif






            /* =================== LEFT LIST =================== */
            function createEquipmentCard(eq, selectedId = null) {
                const card = document.createElement("div");
                card.className =
                    "equipment-card p-4 rounded-md border-1 transition-all cursor-pointer hover:shadow-md border-gray-200 hover:border-gray-300";

                card.innerHTML = `
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex flex-wrap items-start gap-2 mb-2">
                                    <div class="flex items-center gap-2 min-w-0 flex-1">
                                        <h3 class="font-medium text-sm sm:text-base text-gray-900 truncate">${eq.name}</h3>

                                    </div>

                                    <div class="basis-full sm:basis-auto sm:ml-auto inline-flex items-center gap-1.5 whitespace-nowrap">
                                        ${eq.serviceStatusIcon}
                                        ${eq.icon}
                                        <span class="inline-flex px-2 py-1 rounded text-xs font-medium border ${badgeColors(eq.badge)}">
                                            ${eq.badge}
                                        </span>
                                        ${eq.hasDraft ? `<span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium border bg-blue-50 text-blue-700 border-blue-200" title="An in-progress draft inspection exists">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                            Draft
                                        </span>` : ''}
                                    </div>
                                </div>

                                <!-- All fields in a 2-column grid -->
                                <div class="grid grid-cols-2 gap-4 text-sm text-gray-600">

                                    <div>
                                        <div class="font-medium text-gray-700">Model</div>
                                        <div>${eq.model || ''}</div>
                                    </div>

                                    <div>
                                        <div class="font-medium text-gray-700">Equipment ID</div>
                                        <div>${eq.equipment_id}</div>
                                        ${eq.store_name ? `<div class="flex items-center gap-1 mt-0.5 text-xs font-medium text-indigo-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                                            ${eq.store_name}
                                        </div>` : ''}
                                    </div>

                                    <div>
                                        <div class="font-medium text-gray-700">Category</div>
                                        <div>${eq.category}</div>
                                    </div>

                                    <div>
                                        <div class="font-medium text-gray-700">Assigned Order</div>
                                        <div class="flex flex-col gap-0.5">
                                            ${eq.order_route ? eq.order_route.replace('<a ', '<a target="_blank" rel="noopener noreferrer" ') : '<span class="text-xs italic text-gray-400">None</span>'}
                                            ${eq.order_route && eq.orderproduct ? `<span class="text-xs text-gray-500">${eq.orderproduct}</span>` : ''}
                                        </div>
                                    </div>

                                    <div>
                                        <div class="font-medium text-gray-700">Hours</div>
                                        <div class="flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"/>
                                                <polyline points="12 6 12 12 16 14"/>
                                            </svg>
                                            ${(eq.hours ?? 0).toLocaleString()}
                                        </div>
                                    </div>

                                    <div>
                                        <div class="font-medium text-gray-700">Last Completed</div>
                                        ${eq.latestCompletedResult
                                            ? `<div class="flex flex-col gap-0.5">
                                                    <span class="inline-flex items-center self-start px-2 py-0.5 rounded text-xs font-medium border ${resultChipClass(eq.latestCompletedResult)}">${resultLabel(eq.latestCompletedResult)}</span>
                                                    ${eq.latestCompletedDate ? `<span class="text-xs text-gray-500">${escapeHtml(eq.latestCompletedDate)}${eq.latestCompletedInspector ? ' · ' + escapeHtml(eq.latestCompletedInspector) : ''}</span>` : ''}
                                               </div>`
                                            : `<div class="text-xs italic text-gray-400">No completed inspection</div>`}
                                        <a href="${rrHistoryTemplate.replace(':id', eq.unique_id)}" onclick="event.stopPropagation()" class="text-xs text-sky-700 hover:underline">View History</a>
                                    </div>

                                </div>
                            </div>
                        </div>

                    `;

                card.addEventListener("click", () => {
                    document.querySelectorAll(".equipment-card").forEach(c => {
                        c.classList.remove("border-blue-500", "bg-blue-50");
                        c.classList.add("border-gray-200");
                    });
                    card.classList.add("border-blue-500", "bg-blue-50");
                    openChecklist(eq);
                });

                if (selectedId && eq.unique_id == selectedId) {
                    card.classList.add("border-blue-500", "bg-blue-50");
                    openChecklist(eq);
                }

                return card;
            }

            function renderEquipment(selectedId = null) {
                console.time("renderEquipment");
                equipmentList.innerHTML = "";
                equipment.forEach(eq => {
                    equipmentList.appendChild(createEquipmentCard(eq, selectedId));
                });
                equipmentCount.textContent = `${equipment.length} of ${totalEquipment}`;
                console.timeEnd("renderEquipment");
            }


            async function loadMoreEquipment() {
                if (isLoadingMore || currentPage >= lastPage) return;

                // isLoadingMore = true;
                isLoadingMore = true;
                equipmentLoader.classList.remove("hidden");
                currentPage++;

                try {

                    const search = document.getElementById("searchInput").value;
                    const category = document.getElementById("categoryFilter").value;
                    const status = document.getElementById("statusFilter").value;
                    const currentlyAssigned = document.getElementById("currentlyAssignedFilter").checked ? '1' : '0';

                    const response = await fetch(`?page=${currentPage}&search=${search}&category=${category}&status=${status}&currently_assigned=${currentlyAssigned}&store_id=${activeStoreId}`, {
                        headers: {
                            "X-Requested-With": "XMLHttpRequest"
                        }
                    });

                    const result = await response.json();

                    const newItems = result.data.map(mapEquipment);

                    equipment.push(...newItems);
                    newItems.forEach(eq => {
                        equipmentList.appendChild(createEquipmentCard(eq, null));
                    });
                    equipmentCount.textContent = `${equipment.length} of ${totalEquipment}`;

                } catch (error) {
                    console.log("Load more equipment error:", error);
                }

                // isLoadingMore = false;
                isLoadingMore = false;
                equipmentLoader.classList.add("hidden");
            }

            function badgeColors(b) {
                if (b === "Damaged") return "bg-red-100 text-red-700 border-red-200";
                if (b === "Maint. Hold") return "bg-yellow-100 text-yellow-700 border-yellow-200";
                if (b === "Available") return "bg-green-100 text-green-700 border-green-200";
                if (b === "Rented") return "bg-blue-100 text-blue-700 border-blue-200";
                return "bg-gray-100 text-gray-700 border-gray-200";
            }

            // Rental Ready RESULT (business outcome) → label + tone. Mirrors the
            // RentalReadyResult enum labels; kept in JS for the left card + right
            // panel. Distinct from equipment status badges above. Declared as
            // hoisted functions so the synchronous selected-equipment render (which
            // runs before this point) can still call them.
            function resultLabel(v) {
                if (v === "rental_ready") return "Rental Ready";
                if (v === "maintenance_hold") return "Maintenance Hold";
                if (v === "damaged") return "Damaged";
                return "—";
            }
            function resultChipClass(v) {
                if (v === "rental_ready") return "bg-green-50 text-green-700 border-green-200";
                if (v === "maintenance_hold") return "bg-amber-50 text-amber-800 border-amber-200";
                if (v === "damaged") return "bg-red-50 text-red-700 border-red-200";
                return "bg-gray-100 text-gray-600 border-gray-200";
            }
            window.resultLabel = resultLabel;
            window.resultChipClass = resultChipClass;

            // HTML-escape every snapshot / historical value rendered via innerHTML.
            // Immutable ≠ trusted: an older, browser-built snapshot could carry
            // markup in a question, answer, inspector name, order number, etc.
            // Declared (hoisted) so the synchronous selected-equipment render can
            // use it too.
            function escapeHtml(v) {
                if (v === null || v === undefined) return '';
                return String(v)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }
            window.escapeHtml = escapeHtml;

            window.setStatus = function(status) {
                // console.log("Setting equipment status:", status);

                // update hidden input
                const hidden = document.getElementById("equipment_status");
                if (hidden) {
                    hidden.value = status;
                } else {
                    console.warn("Hidden input #equipment_status not found in DOM");
                }
            };





            /* ============ RIGHT PANEL + COMPARISON (Phase 3A) ============ */
            // The three regions are driven from a SINGLE equipment selection.
            // A monotonic token guards against stale async results when the user
            // switches units quickly: the checklist-questions fetch and the
            // latest-completed fetch each stamp the token they started under and
            // bail if it has moved on.
            const rrLatestCompletedTemplate = '{{ route('admin.checklist-management.equipment-management.rental-ready-latest-completed', ':id') }}';
            window.rrSelectionToken = 0;
            window.currentGroupsReady = false;
            window.currentInspectionBlocked = false; // rented → no current inspection
            window.previousReady = false;
            window.previousInspection = null;

            // Center header: history / latest-completed entry points (absorbed
            // from the Phase 2B context bar) + the New-vs-Draft mode chip.
            function renderInspectionContextLinks(eq) {
                const box = document.getElementById("inspectionContextLinks");
                if (!box) return;
                const historyUrl = rrHistoryTemplate.replace(':id', eq.unique_id);
                const latestLink = eq.latestCompletedUuid
                    ? `<a href="${historyUrl}/${eq.latestCompletedUuid}" class="inline-flex items-center gap-1 text-sky-700 hover:underline font-medium">View Latest Completed</a><span class="text-gray-300">·</span>`
                    : '';
                box.innerHTML = `
                    ${latestLink}
                    <a href="${historyUrl}" class="inline-flex items-center gap-1 text-sky-700 hover:underline font-medium">View History</a>
                `;
                box.classList.remove("hidden");
            }

            // Explicit New Inspection vs Draft Resumed label. `resumed` is decided
            // AFTER the questions endpoint answers (existing_data ⇒ a draft was
            // reopened; otherwise a brand-new inspection).
            function setInspectionMode(resumed) {
                const chip = document.getElementById("inspectionModeChip");
                if (!chip) return;
                chip.classList.remove("hidden", "bg-blue-50", "text-blue-700", "border-blue-200", "bg-emerald-50", "text-emerald-700", "border-emerald-200");
                if (resumed) {
                    chip.textContent = "Draft Resumed";
                    chip.classList.add("bg-blue-50", "text-blue-700", "border-blue-200");
                } else {
                    chip.textContent = "New Inspection";
                    chip.classList.add("bg-emerald-50", "text-emerald-700", "border-emerald-200");
                }
            }

            // Fetch the latest COMPLETED, non-voided inspection for the right
            // panel. Read-only; never mutates anything. Null ⇒ never inspected.
            function fetchLatestCompleted(eq, token) {
                const body = document.getElementById("previousInspectionBody");
                if (body) {
                    body.innerHTML = `<div class="p-4 flex flex-col gap-3 animate-pulse">${Array(3).fill(0).map(() => '<div class="h-10 bg-gray-200 rounded-md"></div>').join("")}</div>`;
                }
                window.previousReady = false;
                window.previousInspection = null;

                fetch(rrLatestCompletedTemplate.replace(':id', eq.unique_id), {
                        headers: { "X-Requested-With": "XMLHttpRequest" }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (token !== window.rrSelectionToken) return; // stale
                        window.previousInspection = (data && data.success) ? data.inspection : null;
                        window.previousReady = true;
                        renderPreviousPanelHeader();
                        renderComparison();
                    })
                    .catch(() => {
                        if (token !== window.rrSelectionToken) return;
                        window.previousInspection = null;
                        window.previousReady = true;
                        renderPreviousPanelHeader();
                        renderComparison();
                    });
            }

            // Right-panel header: result / completion date / inspector / hours /
            // order + View Full Inspection + View All History. Operational
            // reference only — NOT the full audit record (that stays on the
            // dedicated detail + history pages).
            function renderPreviousPanelHeader() {
                const body = document.getElementById("previousInspectionBody");
                if (!body) return;
                const ins = window.previousInspection;

                if (!ins) {
                    body.innerHTML = `<p class="text-sm text-gray-400 py-6 text-center">No completed inspection on record for this unit yet.</p>`;
                    return;
                }

                const hours = (ins.equipment_hours !== null && ins.equipment_hours !== undefined && ins.equipment_hours !== '')
                    ? Number(ins.equipment_hours).toLocaleString() : '—';

                body.innerHTML = `
                    <div class="space-y-3">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold border ${resultChipClass(ins.result)}">${escapeHtml(ins.result_label || resultLabel(ins.result))}</span>
                            ${ins.completed_at ? `<span class="text-xs text-gray-500">${escapeHtml(ins.completed_at)}</span>` : ''}
                        </div>
                        <dl class="text-sm text-gray-600 space-y-1.5">
                            <div class="flex justify-between gap-3"><dt class="text-gray-500">Inspector</dt><dd class="text-gray-800 font-medium text-right">${ins.inspector ? escapeHtml(ins.inspector) : '—'}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-gray-500">Hours</dt><dd class="text-gray-800 font-medium text-right">${hours}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-gray-500">Order</dt><dd class="text-gray-800 font-medium text-right">${ins.order_number ? '#' + escapeHtml(ins.order_number) : '—'}</dd></div>
                        </dl>
                        <div class="flex flex-wrap gap-x-3 gap-y-1 text-sm pt-1">
                            <a href="${ins.detail_url}" class="text-sky-700 hover:underline font-medium">View Full Inspection</a>
                            <a href="${ins.history_url}" class="text-sky-700 hover:underline font-medium">View All History</a>
                        </div>
                        <div class="pt-2 border-t border-gray-100">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500">Answer Comparison</h3>
                            </div>
                            <div id="comparisonList" class="space-y-2"></div>
                        </div>
                    </div>
                `;
            }

            // Compact current-vs-previous comparison, aligned PRIMARILY by stable
            // master question id (current `main_id` ↔ snapshot `question_id`).
            //  - a NEW current question (no prior match) shows "No previous answer";
            //  - a historical question no longer in the current checklist is kept
            //    in a collapsed "Questions no longer in the current checklist"
            //    section (never discarded);
            //  - match / difference labels appear ONLY after the current question
            //    is answered, with explicit text (not color alone);
            //  - read-only: rendering never alters any submitted classification.
            function renderComparison() {
                if (!window.previousReady) return;               // wait for fetch
                const list = document.getElementById("comparisonList");
                if (!list) return; // header not rendered (no previous inspection)
                // Wait for the current checklist unless it's blocked (rented).
                if (!window.currentInspectionBlocked && !window.currentGroupsReady) return;

                const prevQuestions = (window.previousInspection && window.previousInspection.questions) || [];
                // Primary key: numeric master question id. Secondary key: question
                // unique_id (covers a resumed draft, whose current items key off it).
                const prevByNumeric = {};
                const prevByUuid = {};
                prevQuestions.forEach(p => {
                    if (p.question_id !== null && p.question_id !== undefined) prevByNumeric[String(p.question_id)] = p;
                    if (p.question_unique_id) prevByUuid[String(p.question_unique_id)] = p;
                });

                const currentItems = window.currentInspectionBlocked ? [] : (((window.groups || [])[0] || {}).items || []);
                // Enforce ONE-TO-ONE alignment: a historical question, once matched
                // to a current question, is consumed and cannot match a second one.
                // `matched` (by numeric question_id) also drives orphan detection.
                const matched = new Set();
                let rows = "";

                currentItems.forEach(item => {
                    // Primary key: numeric master id. Secondary: question unique_id,
                    // used ONLY when both sides carry it. NEVER fall back to option
                    // ids or question text — a false comparison is worse than
                    // "No previous answer". A candidate already consumed by an
                    // earlier current question is skipped (one-to-one).
                    let prev = null;
                    let candidate = null;
                    if (item.main_id !== null && item.main_id !== undefined && prevByNumeric[String(item.main_id)]) {
                        candidate = prevByNumeric[String(item.main_id)];
                    } else if (item.id !== null && item.id !== undefined && prevByUuid[String(item.id)]) {
                        candidate = prevByUuid[String(item.id)];
                    }
                    if (candidate && !matched.has(String(candidate.question_id))) {
                        prev = candidate;
                        matched.add(String(prev.question_id));
                    }

                    // Current selection (read from the DOM radios; never written).
                    const itemId = item.question_id || item.id;
                    const picked = document.querySelector(`input[name="answer-${itemId}"]:checked`);
                    const currentType = picked ? picked.value : null;

                    const prevAnswer = prev
                        ? `${escapeHtml(prev.selected_answer_name || '—')}`
                        : `<span class="italic text-gray-400">No previous answer</span>`;

                    // Difference label only once the current question is answered.
                    let diff = "";
                    if (currentType) {
                        if (!prev || !prev.selected_answer_type) {
                            diff = `<span class="inline-flex items-center gap-1 text-[11px] font-semibold text-indigo-700">New</span>`;
                        } else if (String(prev.selected_answer_type) === String(currentType)) {
                            diff = `<span class="inline-flex items-center gap-1 text-[11px] font-semibold text-gray-500">✓ Unchanged</span>`;
                        } else {
                            diff = `<span class="inline-flex items-center gap-1 text-[11px] font-semibold text-amber-700">▲ Changed</span>`;
                        }
                    }

                    rows += `
                        <div class="rounded-md border border-gray-100 bg-gray-50 px-2.5 py-2">
                            <div class="flex items-start justify-between gap-2">
                                <span class="text-xs font-medium text-gray-700">${escapeHtml(item.title || '—')}</span>
                                ${diff}
                            </div>
                            <div class="text-xs text-gray-500 mt-0.5">Prev: ${prevAnswer}</div>
                        </div>
                    `;
                });

                if (!currentItems.length && !window.currentInspectionBlocked) {
                    rows = `<p class="text-xs text-gray-400">Loading current checklist…</p>`;
                }

                // Historical questions no longer present in the current checklist —
                // retained, never discarded, in a collapsed section.
                const orphans = prevQuestions.filter(p => !matched.has(String(p.question_id)));
                let orphanBlock = "";
                if (orphans.length) {
                    orphanBlock = `
                        <details class="mt-2 rounded-md border border-gray-200">
                            <summary class="cursor-pointer select-none px-2.5 py-2 text-xs font-semibold text-gray-600">
                                Questions no longer in the current checklist (${orphans.length})
                            </summary>
                            <div class="px-2.5 pb-2 space-y-2">
                                ${orphans.map(p => `
                                    <div class="rounded-md border border-gray-100 bg-white px-2.5 py-2">
                                        <div class="text-xs font-medium text-gray-700">${escapeHtml(p.question_name || '—')}</div>
                                        <div class="text-xs text-gray-500 mt-0.5">Prev: ${escapeHtml(p.selected_answer_name || '—')}</div>
                                    </div>
                                `).join("")}
                            </div>
                        </details>
                    `;
                }

                list.innerHTML = (window.currentInspectionBlocked
                        ? `<p class="text-xs text-gray-400 mb-2">This unit is rented — no current inspection to compare. Showing the previous inspection for reference.</p>`
                        : "") + rows + orphanBlock;
            }
            window.renderComparison = renderComparison;

            /* ============ DRAWER / SLIDE-OVER (below xl) ============ */
            // Accessible off-canvas panels: opening one closes the other; focus
            // moves into the panel and returns to the opener on close; body scroll
            // is locked while open and restored on EVERY close path; Escape and
            // backdrop click both close. Declared (hoisted) so openChecklist's
            // synchronous selected-equipment path can call closeEquipmentDrawer();
            // also exported to window for the inline onclick handlers.
            let rrLastFocused = null;
            function rrIsMobile() { return window.matchMedia("(max-width: 1279px)").matches; }
            function rrAnyPanelOpen() {
                const nav = document.getElementById("equipmentNavigator");
                const prev = document.getElementById("previousInspectionPanel");
                return (!nav.classList.contains("-translate-x-full")) || (!prev.classList.contains("translate-x-full"));
            }
            function rrFocusInto(panel) {
                const target = panel.querySelector("[data-panel-close]") || panel;
                setTimeout(() => { try { target.focus(); } catch (e) {} }, 60);
            }
            function rrRestoreFocus() {
                const el = rrLastFocused;
                rrLastFocused = null;
                if (el && typeof el.focus === "function" && el !== document.body) {
                    try { el.focus(); } catch (e) {}
                }
            }
            function rrSyncBodyScroll() {
                // Only the mobile off-canvas state locks scrolling; at xl the panels
                // are permanent columns, so never lock there.
                document.body.classList.toggle("overflow-hidden", rrIsMobile() && rrAnyPanelOpen());
            }

            function openEquipmentDrawer() {
                const opener = document.activeElement;
                closePreviousPanel();
                rrLastFocused = opener;
                const panel = document.getElementById("equipmentNavigator");
                panel.classList.remove("-translate-x-full");
                document.getElementById("equipmentDrawerBackdrop").classList.remove("hidden");
                rrSyncBodyScroll();
                rrFocusInto(panel);
            }
            function closeEquipmentDrawer() {
                const panel = document.getElementById("equipmentNavigator");
                const wasOpen = rrIsMobile() && !panel.classList.contains("-translate-x-full");
                panel.classList.add("-translate-x-full");
                document.getElementById("equipmentDrawerBackdrop").classList.add("hidden");
                rrSyncBodyScroll();
                if (wasOpen) rrRestoreFocus();
            }
            function openPreviousPanel() {
                const opener = document.activeElement;
                closeEquipmentDrawer();
                rrLastFocused = opener;
                const panel = document.getElementById("previousInspectionPanel");
                panel.classList.remove("translate-x-full");
                document.getElementById("previousPanelBackdrop").classList.remove("hidden");
                rrSyncBodyScroll();
                rrFocusInto(panel);
            }
            function closePreviousPanel() {
                const panel = document.getElementById("previousInspectionPanel");
                const wasOpen = rrIsMobile() && !panel.classList.contains("translate-x-full");
                panel.classList.add("translate-x-full");
                document.getElementById("previousPanelBackdrop").classList.add("hidden");
                rrSyncBodyScroll();
                if (wasOpen) rrRestoreFocus();
            }
            window.openEquipmentDrawer = openEquipmentDrawer;
            window.closeEquipmentDrawer = closeEquipmentDrawer;
            window.openPreviousPanel = openPreviousPanel;
            window.closePreviousPanel = closePreviousPanel;

            // Escape closes whichever off-canvas panel is open (mobile/tablet only).
            document.addEventListener("keydown", (e) => {
                if (e.key !== "Escape" || !rrIsMobile()) return;
                const nav = document.getElementById("equipmentNavigator");
                const prev = document.getElementById("previousInspectionPanel");
                if (nav && !nav.classList.contains("-translate-x-full")) { closeEquipmentDrawer(); return; }
                if (prev && !prev.classList.contains("translate-x-full")) { closePreviousPanel(); }
            });
            // If the viewport grows to xl while a panel was open, drop the scroll lock.
            window.addEventListener("resize", rrSyncBodyScroll);

            function openChecklist(eq) {

                console.time("openChecklist");

                window.currentEquipment = eq;

                // Phase 3A — a fresh selection drives all three regions. Bump the
                // token so any in-flight async result for a previously-selected
                // unit is discarded, reset the per-selection readiness flags, and
                // kick off the (read-only) latest-completed fetch for the right
                // panel in parallel with the checklist-questions fetch below.
                const token = ++window.rrSelectionToken;
                window.currentGroupsReady = false;
                window.currentInspectionBlocked = (eq.badge === "Rented");
                window.previousReady = false;
                window.previousInspection = null;
                // Hide stale context from a prior selection; the questions handler
                // sets the correct New/Draft mode. Rented units return early and
                // never load a checklist, so their chip stays hidden.
                document.getElementById("inspectionModeChip")?.classList.add("hidden");
                document.getElementById("inspectionContextLinks")?.classList.add("hidden");
                fetchLatestCompleted(eq, token);
                closeEquipmentDrawer(); // if opened as a drawer on tablet, dismiss it
                const openPrevBtn = document.getElementById("openPreviousBtnMobile");
                if (openPrevBtn) openPrevBtn.disabled = false;


                const footerButton = document.getElementById("footerbutton"); // get the footer button

                if (eq.orderproductid == null) {
                    // Fresh template (no order product linked)
                    footerButton.classList.remove("hidden");
                    checklistContent.classList.remove("hidden");
                } else {
                    // Has order product → hide/show based on status
                    if (eq.badge === "Available") {
                        footerButton.classList.add("hidden");
                        checklistContent.classList.remove("hidden");
                    } else {
                        footerButton.classList.remove("hidden");
                        checklistContent.classList.remove("hidden");
                    }
                }

                //  ensure rented equipment always stays hidden
                if (eq.badge === "Rented") {
                    footerButton.classList.add("hidden");

                    checklistContent.classList.remove("hidden");
                    checklistContent.innerHTML = `
                            <div class="p-4  border  rounded-lg ">
                                <p class="font-medium  ">
                                    This ${eq.name} - ID: ${eq.equipment_id} is currently <span class="text-red-600 font-bold">Rented</span>
                                    and cannot be updated in the Rental Ready system.
                                </p>
                                <p class="mt-2">
                                    If the rental is completed, please update the order to close out the rental →
                                    ${eq.order_route}

                                </p>
                            </div>
                        `;
                    placeholder.classList.add("hidden");
                    checklistContainer.classList.remove("hidden");

                    return; // Stop execution — DO NOT load checklist questions
                }

                placeholder.classList.add("hidden");
                checklistContainer.classList.remove("hidden");
                checklistTitle.textContent = `Rental Ready Checklist - ${eq.name}`;
                renderInspectionContextLinks(eq);
                equipmentHoursInput.value = eq.hours;

                if (eq.is_tracked === "Yes") {
                    // Show normal input
                    hoursWrapper.classList.remove("hidden");
                    hoursNotTracked.classList.add("hidden");

                    equipmentHoursInput.disabled = false;
                    equipmentHoursInput.placeholder = "Enter hours";
                    equipmentHoursInput.value = eq.hours ?? 0;

                } else {
                    // Show "Not tracked" box
                    hoursWrapper.classList.add("hidden");
                    hoursNotTracked.classList.remove("hidden");
                }
                checklistContent.innerHTML = "";

                document.getElementById("equipmentId").value = eq.id;

                // ----- Show loader while fetching -----
                checklistContent.innerHTML = `
                    <div id="loaderWrapper" class="p-6 flex flex-col gap-4 animate-pulse">
                        ${Array(3).fill(0).map(() => `
                                <div class="h-16 bg-gray-200 rounded-md"></div>
                            `).join("")}
                    </div>
                `;

                checklistContent.classList.add("opacity-50", "pointer-events-none");

                //  Fetch checklist questions for this equipment

                console.time("fetchChecklist");
                    
                fetch(`{{ route('admin.checklist-management.equipment-management.get-checklist-questions') }}`, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute(
                                'content'),
                        },
                        body: JSON.stringify({
                            checklist_id: eq.checklist_master_id,
                            equipment_id: eq.id,
                            order_product_id: eq.orderproductid
                        })
                        })
                        .then(res => res.json())
                        .then(data => {

                                console.timeEnd("fetchChecklist");
                            // remove loader
                            checklistContent.classList.remove("opacity-50", "pointer-events-none");
                            checklistContent.innerHTML = "";

                            if (data.success === true) {
                                console.log("Fetched checklist data:", data);

                                // Normalize: use questions OR existing_data.questions
                                let items = [];
                                if (data.questions) {
                                    items = data.questions; // fresh questions
                                } else if (data.existing_data && data.existing_data.questions) {
                                    items = data.existing_data.questions; // saved data
                                }

                                groups = [{
                                    key: "default",
                                    title: "Checklist Questions",
                                    items: items
                                }];

                                window.groups = groups;

                                renderChecklist(groups);

                                // now render UI
                                groups.forEach((g) => {
                                    const section = document.createElement("div");
                                    section.className = "border border-gray-200 rounded-md p-4";
                                    section.innerHTML = `
                                        <h3 class="font-medium text-gray-900 mb-4 flex items-center gap-2">
                                            <span>${g.title}</span>
                                            <span id="groupCount-${g.key}" class="text-sm text-gray-500">(0/${g.items.length})</span>
                                            <input type="hidden" name="total_questions" value="${g.items.length}">
                                        </h3>
                                        <div id="groupBody-${g.key}" class="space-y-4"></div>`;
                                                    checklistContent.appendChild(section);

                                                    const body = section.querySelector(`#groupBody-${g.key}`);
                                                    g.items.forEach((item) => {
                                                        const itemId = item.question_id || item.id;
                                                        const answers = (item.answers || []).map((opt) => `
                                            <label class="flex flex-wrap items-center gap-3 p-2 bg-white rounded-md border border-gray-200 cursor-pointer hover:bg-gray-50 transition-colors">
                                                <div class="flex items-center gap-3 flex-1 min-w-0">
                                                    <input type="radio" name="answer-${itemId}" class="w-4 h-4 text-blue-600 focus:ring-blue-500 shrink-0"
                                                        value="${opt.status}" data-item="${itemId}" data-opt-id="${opt.id}" data-group="${g.key}">
                                                    <span class="text-sm text-gray-900">${opt.label}</span>
                                                </div>
                                                <div class="w-full sm:w-auto sm:ml-auto sm:justify-end flex items-center">
                                                    ${statusBadge(opt.status)}
                                                </div>
                                            </label>
                                        `).join("");

                                        const card = document.createElement("div");
                                        card.className =
                                            "item-card p-4 rounded-md border-2 transition-all border-gray-200 bg-white-50";
                                        card.id = `item-${itemId}`;
                                        card.innerHTML = `
                                            <div class="flex flex-wrap items-center gap-2 mb-3">
                                                <span id="icon-${itemId}" class="inline-flex shrink-0 ">${iconSvg("default")}</span>
                                                <span class="font-medium text-gray-900">${item.title}${item.required ? '<span class="text-red-500 ml-1">*</span>' : ''}</span>

                                                

                                                <span id="chip-${itemId}"></span>
                                            </div>
                                            <div class="text-sm font-medium text-gray-700 mb-2">Select Condition:</div>
                                            <div class="space-y-2">${answers}</div>
                                            <div id="notes-${itemId}" class="hidden mt-3">
                                                <textarea class="w-full bg-white border px-3 py-2 rounded-md text-sm border-gray-300" rows="3" placeholder="Add notes about the issue..."></textarea>
                                            </div>
                                        `;

                                        body.appendChild(card);
                                    });
                                });

                                /* ---------- General Notes card (NEW) ---------- */
                                const generalNotes = document.createElement("div");
                                generalNotes.innerHTML = `
                                        <h3 class="font-medium text-gray-700 mb-3 flex items-center gap-2">
                                            <span>General Notes</span>
                                        </h3>
                                        <textarea id="generalNotes" name="general_notes" class="w-full bg-white border px-3 py-2 rounded-md text-sm border-gray-300" rows="4" placeholder="Add any additional notes or observations..."></textarea>
                                    `;
                                checklistContent.appendChild(generalNotes);
                                

                                // === Apply saved data if available ===
                                if (data.existing_data && data.existing_data.questions) {
                                    data.existing_data.questions.forEach(saved => {
                                        const itemId = saved.question_id || saved.id;
                                        const radio = document.querySelector(
                                            `input[name="answer-${itemId}"][data-opt-id="${saved.answer_id}"]`
                                        );
                                        if (radio) {
                                            radio.checked = true;
                                            colorItemCard(itemId, saved.status);
                                            setHeaderIcon(itemId, saved.status);
                                            updateGroupCount("default");

                                            //  sync groups
                                            const group = groups.find(g => g.key === "default");
                                            if (group) {
                                                const itemObj = group.items.find(it => (it.question_id || it
                                                    .id) === itemId);
                                                if (itemObj) {
                                                    itemObj.answer_id = saved.answer_id;
                                                    itemObj.status = saved.status;
                                                    itemObj.notes = saved.notes || "";
                                                }
                                            }
                                        }


                                        if (saved.notes) {
                                            const notesBox = document.querySelector(
                                                `#notes-${itemId} textarea`);
                                            if (notesBox) {
                                                notesBox.value = saved.notes;
                                                notesBox.parentElement.classList.remove("hidden");
                                            }
                                        }
                                    });
                                }

                                if (data.existing_data && data.existing_data.general_notes) {
                                    document.getElementById("generalNotes").value = data.existing_data
                                    .general_notes;
                                }

                                if (data.existing_data && data.existing_data.existingTemplate && data.existing_data
                                    .existingTemplate.employee_name) {
                                    // document.getElementById("inspectorSelect").innerText = data.existing_data
                                    //     .existingTemplate.employee_name;

                                    //     console.log('document.getElementById("inspectorSelect").innerText:- ',document.getElementById("inspectorSelect").innerText);
                                    //     console.log('data.existing_data.existingTemplate.employee_name ',data.existing_data.existingTemplate.employee_name);


                                    const select = document.getElementById("inspectorSelect");
                                    const targetName = data.existing_data.existingTemplate.employee_name;

                                    [...select.options].forEach(option => {
                                        if (option.text.trim() === targetName.trim()) {
                                            select.value = option.value;
                                        }
                                    });

                                }

                                if (data.existing_data && data.existing_data.existingTemplate && data.existing_data
                                    .existingTemplate.equipment_hours) {
                                    document.getElementById("equipmentHours").value = data.existing_data
                                        .existingTemplate.equipment_hours;
                                }




                                //  Handle Misc default selection
                                    const miscItem = groups
                                        .flatMap(g => g.items)
                                        .find(it => it.title === "Miscellaneous");

                                    if (miscItem) {
                                        const miscId = miscItem.question_id || miscItem.id;

                                        // check if already selected (existing data)
                                        const alreadySelected = document.querySelector(
                                            `input[name="answer-${miscId}"]:checked`
                                        );

                                        //  ONLY if no existing selection
                                        if (!alreadySelected) {

                                            // select "Rental Ready" (Operable)
                                            const defaultRadio = document.querySelector(
                                                `input[name="answer-${miscId}"][value="Rental Ready"]`
                                            );

                                            if (defaultRadio) {
                                                defaultRadio.checked = true;

                                                // apply UI
                                                colorItemCard(miscId, "Rental Ready");
                                                setHeaderIcon(miscId, "Rental Ready");

                                                // sync group data
                                                miscItem.answer_id = parseInt(defaultRadio.dataset.optId, 10);
                                                miscItem.status = "Rental Ready";
                                            }
                                        }
                                    }



                                checklistContent.querySelectorAll('input[type="radio"]').forEach(r => {
                                    r.addEventListener("change", onChoice);
                                });

                                updateAllCounts();
                                updateProgress();

                                // Phase 3A — the questions endpoint has spoken:
                                // existing_data ⇒ a draft was resumed, otherwise a
                                // brand-new inspection. Label it, then let the
                                // right-panel comparison render now that the
                                // current checklist is in the DOM.
                                setInspectionMode(!!(data.existing_data && data.existing_data.questions));
                                window.currentGroupsReady = true;
                                renderComparison();
                            } else {
                                //notyf.error(data.message);


                                // put this once near the top of your script
                                const editRouteTemplate =
                                    '{{ route('admin.maintenance-management.equipment.edit', ':id') }}';
                                footerButton.classList.add("hidden");
                                // when building the HTML (inside openChecklist)
                                checklistContent.innerHTML = `<p class="text-red-500">
                            There is no Checklist assigned to this Equipment ID - Assign a Checklist to this Equipment ID now:
                            <a href="${editRouteTemplate.replace(':id', eq.unique_id)}" class="text-blue-600">Click Here</a>
                            </p>`;
                            }

                            console.timeEnd("openChecklist");
                        })
                        .catch(err => {
                            checklistContent.classList.remove("opacity-50", "pointer-events-none");
                            checklistContent.innerHTML = `<p class="text-red-500"> Error loading questions.</p>`;
                            console.log(err);
                        });
                }

                /* =================== HANDLERS =================== */
                function onChoice(e) {
                    const status = e.target.value;
                    const itemId = e.target.dataset.item;
                    const groupKey = e.target.dataset.group;

                    colorItemCard(itemId, status);
                    setHeaderIcon(itemId, status);

                    //  sync groups when user changes
                    const group = groups.find(g => g.key === groupKey);
                    if (group) {
                        const itemObj = group.items.find(it => (it.question_id || it.id) === itemId);
                        if (itemObj) {
                            itemObj.answer_id = parseInt(e.target.dataset.optId, 10);
                            itemObj.status = status;
                        }
                    }

                    // 
                    // check if THIS is Miscellaneous question
                    const miscItem = groups
                        .flatMap(g => g.items)
                        .find(it => it.title === "Miscellaneous");

                        console.log("Misc Item Found:", miscItem);

                    if (miscItem && itemId === (miscItem.question_id || miscItem.id)) {

                        if (status === "Damaged") {

                            // loop all questions except misc
                            groups.forEach(g => {
                                g.items.forEach(item => {

                                    const id = item.question_id || item.id;

                                    // skip misc itself
                                    if (id === itemId) return;

                                    const labels = document.querySelectorAll(`input[name="answer-${id}"]`);

                                        let radio = null;

                                        labels.forEach(r => {
                                            const labelText = r.closest('label').innerText.toLowerCase();

                                            if (labelText.includes("inspection required")) {
                                                radio = r;
                                            }
                                        });

                                    if (radio) {
                                        radio.checked = true;

                                        colorItemCard(id, "Maint. Hold");
                                        setHeaderIcon(id, "Maint. Hold");

                                        item.answer_id = parseInt(radio.dataset.optId, 10);
                                        item.status = "Maint. Hold";
                                    }
                                });
                            });

                        }
                    } //  END BLOCK

                    updateGroupCount(groupKey);
                    updateProgress();
                    // Phase 3A — refresh the read-only comparison so match /
                    // difference labels appear as questions get answered. This
                    // only reads the current selections; it never writes them.
                    if (window.renderComparison) window.renderComparison();
                }


                function colorItemCard(itemId, status) {
                    const box = document.getElementById(`item-${itemId}`);
                    const chip = document.getElementById(`chip-${itemId}`);
                    const notes = document.getElementById(`notes-${itemId}`);

                    box.classList.remove("bg-green-50", "bg-yellow-50", "bg-red-50", "bg-gray-50",
                        "border-green-300", "border-yellow-300", "border-red-300", "border-gray-200");
                    chip.className = "ml-auto text-xs px-2 py-1 rounded font-medium";

                    if (status === "Rental Ready") {
                        box.classList.add("bg-green-50", "border-green-300");
                        chip.classList.add("bg-green-100", "text-green-800");
                        chip.textContent = "Rental Ready";
                        notes.classList.add("hidden");
                    } else if (status === "Maint. Hold") {
                        box.classList.add("bg-yellow-50", "border-yellow-300");
                        chip.classList.add("bg-orange-100", "text-orange-800");
                        chip.textContent = "Maint. Hold";
                        notes.classList.remove("hidden");
                    } else if (status === "Damaged") {
                        box.classList.add("bg-red-50", "border-red-300");
                        chip.classList.add("bg-red-100", "text-red-800");
                        chip.textContent = "Damaged";
                        notes.classList.remove("hidden");
                    } else {
                        box.classList.add("bg-gray-50", "border-gray-200");
                        chip.classList.add("bg-gray-100", "text-gray-600");
                        chip.textContent = "—";
                        notes.classList.add("hidden");
                    }
                }

                function setHeaderIcon(itemId, status) {
                    const holder = document.getElementById(`icon-${itemId}`);
                    if (!holder) return;
                    if (status === "Rental Ready") holder.innerHTML = iconSvg("ready");
                    else if (status === "Maint. Hold") holder.innerHTML = iconSvg("hold");
                    else if (status === "Damaged") holder.innerHTML = iconSvg("damaged");
                    else holder.innerHTML = iconSvg("default");
                }

                /* =================== PROGRESS & BUTTONS =================== */
                function computeStatusSummary() {
                    const total = groups.reduce((n, g) => n + g.items.length, 0);
                    const requiredTotal = groups.reduce((n, g) => n + g.items.filter(it => it.required).length, 0);

                    let completed = 0,
                        ready = 0,
                        hold = 0,
                        damaged = 0,
                        requiredCompleted = 0;

                    groups.forEach(g => {
                        g.items.forEach(item => {
                            const itemId = item.question_id || item.id;
                            const picked = document.querySelector(
                                `input[name="answer-${itemId}"]:checked`);
                            if (picked) {
                                completed++;
                                if (item.required) requiredCompleted++;
                                const s = picked.value;
                                if (s === "Rental Ready") ready++;
                                else if (s === "Maint. Hold") hold++;
                                else if (s === "Damaged") damaged++;
                            }
                        });
                    });

                    return {
                        total,
                        requiredTotal,
                        completed,
                        requiredCompleted,
                        ready,
                        hold,
                        damaged
                    };
                }

                function updateProgress() {
                    const {
                        total,
                        requiredTotal,
                        completed,
                        requiredCompleted,
                        ready,
                        hold,
                        damaged
                    } = computeStatusSummary();
                    const pct = Math.round((completed / total) * 100);

                    progressTop.textContent = `${completed} of ${total} items completed`;
                    progressBottom.textContent = `${completed} of ${total} items completed (${pct}%)`;
                    bar.style.width = pct + "%";



                    // ensure counters exist, then update them
                    const {
                        req,
                        maint,
                        totalmaint,
                        dmg
                    } = ensureCounters();
                    if (req) req.textContent = `${requiredCompleted}/${requiredTotal}`;
                    if (maint) maint.textContent = hold;
                    if (dmg) dmg.textContent = damaged;

                    // Show total-maint-hold only if hold > 0
                    if (totalmaint) {
                        totalmaint.textContent = hold;
                        document.getElementById("maint-hold-Msg").classList.toggle("hidden", hold === 0);
                    }


                    const inspectorOk = (typeof REQUIRE_INSPECTOR === "boolean" ? !REQUIRE_INSPECTOR : true) || (
                        inspectorSelect && inspectorSelect.value !== "");
                    const allAnswered = (completed === total);
                    const allReady = allAnswered && (ready === total);
                    const anyDamaged = allAnswered && (damaged > 0);

                    // default → both visible but disabled gray
                    setBtn(btnReady, {
                        enabled: false,
                        color: "green",
                        show: true
                    });
                    setBtn(btnDamaged, {
                        enabled: false,
                        color: "red",
                        show: true
                    });

                    if (allReady && inspectorOk) {
                        setBtn(btnReady, {
                            enabled: true,
                            color: "green",
                            show: true
                        });
                        setBtn(btnDamaged, {
                            enabled: false,
                            color: "red",
                            show: false
                        });
                    } else if (anyDamaged && inspectorOk) {
                        setBtn(btnReady, {
                            enabled: false,
                            color: "green",
                            show: false
                        });
                        setBtn(btnDamaged, {
                            enabled: true,
                            color: "red",
                            show: true
                        });
                    }

                    if (reqMsg) reqMsg.classList.toggle("hidden", inspectorOk);
                }

                function setBtn(btn, {
                    enabled,
                    color,
                    show
                }) {
                    if (!btn) return;
                    // btn.classList.toggle("hidden", show === false);
                    btn.classList.remove(
                        "bg-gray-100", "text-gray-400", "cursor-not-allowed",
                        "bg-blue-600", "hover:bg-blue-700",
                        "bg-green-600", "hover:bg-green-700",
                        "bg-red-600", "hover:bg-red-700", "text-white"
                    );
                    if (!enabled) {
                        btn.disabled = true;
                        btn.classList.add("bg-gray-100", "text-gray-400", "cursor-not-allowed");
                    } else {
                        btn.disabled = false;
                        btn.classList.add("text-white");
                        if (color === "green") btn.classList.add("bg-green-600", "hover:bg-green-700");
                        else if (color === "red") btn.classList.add("bg-red-600", "hover:bg-red-700");
                        else btn.classList.add("bg-blue-600", "hover:bg-blue-700");
                    }
                }

                    /* =================== COUNTS =================== */
                    function updateGroupCount(groupKey) {
                        const group = groups.find(g => g.key === groupKey);
                        if (!group) return;
                        let done = 0;
                        group.items.forEach(item => {
                            const itemId = item.question_id || item.id;
                            if (document.querySelector(`input[name="answer-${itemId}"]:checked`)) done++;
                        });
                        const el = document.getElementById(`groupCount-${groupKey}`);
                        if (el) el.textContent = `(${done}/${group.items.length})`;
                    }

                    function updateAllCounts() {
                        groups.forEach(g => updateGroupCount(g.key));
                    }

                /* =================== ICONS & BADGES =================== */
                function statusBadge(status) {
                    if (status === "Rental Ready")
                    return `<div class="flex items-center gap-2 flex-shrink-0"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-circle w-4 h-4 text-green-500"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><path d="m9 11 3 3L22 4"></path></svg> <span class="text-xs px-2 py-1 rounded font-medium bg-green-100 text-green-800">Rental Ready</span></div>`;

                    if (status === "Maint. Hold")
                    return `<div class="flex items-center gap-2 flex-shrink-0"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-yellow-600" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path
                                            d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 1 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94Z" />
                                    </svg><span class="text-xs px-2 py-1 rounded font-medium bg-yellow-100 text-yellow-800">Maint. Hold</span></div>`;

                    if (status === "Damaged")
                    return `<div class="flex items-center gap-2 flex-shrink-0"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
                                        <path d="M12 9v4"></path>
                                        <path d="M12 17h.01"></path>
                        </svg><span class="text-xs px-2 py-1 rounded font-medium bg-red-100 text-red-700">Damaged</span></div>`;
                    return `<span class="text-xs px-2 py-1 rounded font-medium bg-gray-100 text-gray-600">—</span>`;
                }

                function sectionIcon() {
                    return `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-circle w-4 h-4 text-green-500"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><path d="m9 11 3 3L22 4"></path></svg>`;
                }

                function iconSvg(type) {
                    if (type === "ready")
                        return `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-circle w-4 h-4 text-green-500"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><path d="m9 11 3 3L22 4"></path></svg>`;
                    if (type === "hold")
                        return `<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-yellow-600" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path
                                            d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 1 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94Z" />
                                    </svg>`;
                    if (type === "damaged")
                        return `<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
                                        <path d="M12 9v4"></path>
                                        <path d="M12 17h.01"></path>
                        </svg>`;
                    return `<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>`;
                }

                /* =================== LISTENERS =================== */
                if (inspectorSelect) inspectorSelect.addEventListener("change", updateProgress);

                window.computeStatusSummary = computeStatusSummary;

                   console.timeEnd("FULL_PAGE_LOAD");
            }); 


            // --- creates the 3 counters below the progress bar if they don't exist ---
            function ensureCounters() {
                const pb = document.getElementById("progressBottom");
                if (!pb) return {
                    req: null,
                    maint: null,
                    totalmaint: null,
                    dmg: null
                };

                // place counters right under the progress bar block
                const progressBlock = pb.closest(".mb-4") || pb.parentElement;

                if (!document.getElementById("summaryCounters")) {
                    const ul = document.createElement("ul");
                    ul.id = "summaryCounters";
                    ul.className = "mt-4 divide-y divide-gray-200 text-sm";
                    ul.innerHTML = `
                    <li class="flex items-center justify-between py-2">
                        <span class="text-gray-700">Required Items Completed</span>
                        <span id="reqItems" class="font-medium text-gray-900">0/0</span>
                    </li>
                    <li class="flex items-center justify-between py-2">
                        <span class="text-gray-700">Items Requiring Maintenance</span>
                        <span id="maintItems" class="font-medium text-orange-500">0</span>
                    </li>
                    <li class="flex items-center justify-between py-2">
                        <span class="text-gray-700">Damaged Items</span>
                        <span id="damagedItems" class="font-medium text-red-700">0</span>
                    </li>`;
                                progressBlock.insertAdjacentElement("afterend", ul);
                            }

                return {
                    req: document.getElementById("reqItems"),
                    maint: document.getElementById("maintItems"),
                    totalmaint: document.getElementById("total-maint-hold"),
                    dmg: document.getElementById("damagedItems"),
                };
            }


  
    </script>



    <script>
        document.querySelector("form").addEventListener("submit", function(e) {
            // e.preventDefault();

            const summary = window.computeStatusSummary();
            const qaData = [];


            window.groups.forEach(g => {
                g.items.forEach(item => {
                    const itemId = item.question_id || item.id;
                    const picked = document.querySelector(`input[name="answer-${itemId}"]:checked`);
                    const noteEl = document.querySelector(`#notes-${itemId} textarea`);

                    // Build answers array
                    const answers = (item.answers || []).map(opt => {
                        const isSelected = picked && parseInt(picked.dataset.optId, 10) ===
                            opt.id;
                        return {
                            id: opt.id, // numeric db id
                            unique_id: opt.unique_id || `ANS-${opt.id}`, // string fallback
                            answer_name: opt.label,
                            type: opt.status,
                            is_selected: isSelected
                        };
                    });


                    // Build selected_answer if chosen
                    let selectedAnswer = null;
                    if (picked) {
                        const chosenOpt = item.answers.find(opt => opt.id === parseInt(picked
                            .dataset.optId, 10));
                        if (chosenOpt) {
                            selectedAnswer = {
                                id: chosenOpt.id,
                                unique_id: chosenOpt.unique_id || `ANS-${chosenOpt.id}`,
                                answer_name: chosenOpt.label,
                                type: chosenOpt.status,
                                is_selected: true
                            };
                        }
                    }

                    qaData.push({
                        id: item.main_id || item.id, // numeric db id if possible
                        unique_id: item.unique_id || `${item.id}`, // string fallback
                        question_name: item.title,
                        answers: answers,
                        note: noteEl ? noteEl.value : "",
                        category_id: item.category_id || null,
                        required_question: !!item.required,
                        selected_answer: selectedAnswer
                    });

                });
            });

            const finalPayload = {
                questions: qaData,
                order_product_id: window.currentEquipment?.orderproductid ?? null,
                order_id: window.currentEquipment?.orderid ?? null,
                counts: {
                    total_questions: summary.total,
                    required_questions: summary.requiredTotal,
                    optional_questions: summary.total - summary.requiredTotal,
                    required_items_completed: summary.requiredCompleted,
                    items_requiring_maintenance: summary.hold,
                    damaged_items: summary.damaged
                }
            };

            // console.log('this is final submited values');
            // console.log(finalPayload);

            document.getElementById("rentalReadyQaJson").value = JSON.stringify(finalPayload);



        });
    </script>
@endpush
