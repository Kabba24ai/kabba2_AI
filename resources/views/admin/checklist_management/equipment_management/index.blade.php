@extends('admin.layouts.app')

@section('title', 'Equipment Management')

@push('css')

@endpush

@section('content')

@include('flash::message')
@include('admin.partials.formErrors')
<div class="">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- LEFT: Equipment -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-md shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">Select Equipment</h2>
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3" />
                        </svg>
                        <span id="equipmentCount">0 of 0</span>
                    </div>
                </div>

                <!-- Search -->
                <div class="relative mb-4">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z"></path>
                        </svg>
                    </div>
                    <input type="text" id="searchInput" oninput="applyFilters()" placeholder="Search equipment..." class="w-full border pl-10 pr-3 py-2 rounded-md text-sm">
                </div>

                <!-- Category -->
                <div class="mb-3">
                    <label class="text-sm font-medium text-gray-700 mb-1 block">Category</label>

                    <select id="categoryFilter"
                        class="w-full border px-3 py-2 rounded-md text-sm"
                        onchange="applyFilters()">
                        <option value="All Categories">All Categories</option>
                        @foreach ($categories as $id => $name)
                        <option value="{{ $name }}">{{ $name }}</option>
                        @endforeach
                    </select>


                </div>

                <!-- Status -->
                <div class="mb-4">
                    <label class="text-sm font-medium text-gray-700 mb-1 block">Status</label>
                    <select class="w-full border px-3 py-2 rounded-md text-sm" id="statusFilter" onclick="applyFilters()">
                        <option>All Statuses</option>
                        <option>Damaged</option>
                        <option>Maint. Hold</option>
                        <option>Rented</option>
                        <option>Available</option>
                    </select>
                </div>
                <div id="equipmentList" class="space-y-3 max-h-[1000px] overflow-y-auto"></div>
            </div>
        </div>

        <!-- RIGHT: Checklist -->
        <div class="lg:col-span-2">
            <!-- Placeholder -->
            <div id="placeholder" class="bg-white rounded-md shadow-sm border border-gray-200 p-8 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clipboard-list w-16 h-16 text-gray-300 mx-auto mb-4">
                    <rect width="8" height="4" x="8" y="2" rx="1" ry="1"></rect>
                    <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                    <path d="M12 11h4"></path>
                    <path d="M12 16h4"></path>
                    <path d="M8 11h.01"></path>
                    <path d="M8 16h.01"></path>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Select Equipment to Begin</h3>
                <p class="text-gray-600">Choose equipment from the list to start the rental ready inspection process.</p>
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
                <div class="mb-6">
                    <div class="flex flex-wrap items-start gap-2 mb-4">
                        <h2 id="checklistTitle" class="text-xl font-semibold text-gray-900">Rental Ready Checklist</h2>
                        <div class="text-sm text-gray-600 w-full sm:w-auto sm:ml-auto sm:text-right ml-auto" id="progressTop">0 of 10 items completed</div>
                    </div>

                    <!-- Header row -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1 block required">Inspector Name </label>

                            {!! html()
                            ->select('inspectorSelect',
                            $users->mapWithKeys(fn ($user) => [$user->id => $user->full_name])->prepend('Select Inspector', '')->toArray(),
                            old('inspectorSelect')
                            )
                            ->id('inspectorSelect')
                            ->class([
                            'w-full border px-3 py-2 rounded-md text-sm',
                            ])
                            ->required()
                            !!}

                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1 block">Equipment Hours</label>
                            <input id="equipmentHours" name="equipmentHours" type="number" class="w-full border px-3 py-2 rounded-md text-sm" placeholder="Enter hours" min="0" step="0.1">
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1 block">Inspection Date</label>
                            <input id="inspectionDate" type="date" class="w-full border px-3 py-2 rounded-md text-sm" readonly>
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
                                <span class="text-sm text-gray-600" id="progressBottom">0 of 10 items completed (0%)</span>
                            </div>
                            <div class="bg-gray-200 rounded-full h-3">
                                <div id="overallProgressBar" class="bg-blue-500 h-3 rounded-full transition-all duration-300" style="width:0%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <button type="submit" id="btnReady" onclick="setStatus('available')" disabled class="text-sm w-full flex items-center justify-center gap-2 px-4 py-2 rounded-md font-medium transition-all bg-gray-100 text-gray-400 cursor-not-allowed">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                                <path d="m9 11 3 3L22 4" />
                            </svg>
                            Mark as Rental Ready
                        </button>
                        <button type="submit" id="btnDamaged" onclick="setStatus('damaged')" disabled class="text-sm w-full flex items-center justify-center gap-2 px-4 py-2 rounded-md font-medium transition-all bg-gray-100 text-gray-400 cursor-not-allowed">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" />
                                <path d="M12 9v4" />
                                <path d="M12 17h.01" />
                            </svg>
                            Mark as Damaged
                        </button>
                        <button type="submit" onclick="setStatus('maintenance')"
                            class="text-sm w-full flex items-center justify-center gap-2 px-4 py-2 rounded-md font-medium bg-gray-600 hover:bg-gray-700 text-white transition-all shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10" />
                                <polyline points="12 6 12 12 16 14" />
                            </svg>
                            Save Draft
                        </button>
                    </div>
                    <div class="mt-4 space-y-2">
                        <div class="text-sm text-red-600 bg-red-50 p-3 rounded-md hidden" id="reqMsg">Inspector name is required to complete the checklist.</div>
                    </div>

                    <div class="mt-4 space-y-2">
                        <div class="text-sm text-orange-600 bg-orange-50 p-3 rounded-md" id="maint-hold-Msg"> <span id="total-maint-hold"> 3 </span> item(s) require maintenance before rental ready status.</div>
                    </div>

                </div>
            </div>

            {{ html()->form()->close() }}

        </div>
    </div>
</div>
@endsection

@push('js')

<script>
    document.addEventListener("DOMContentLoaded", () => {


        /* ====== CONFIG ====== */
        const REQUIRE_INSPECTOR = true;

        /* =================== DATA =================== */
        const rawEquipment = @json($equipments);

        const icons = {
            damaged: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     class="lucide lucide-alert-triangle w-4 h-4 text-red-500">
                     <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8
                              14A2 2 0 0 0 4 21h16a2 2
                              0 0 0 1.73-3Z"></path>
                     <path d="M12 9v4"></path>
                     <path d="M12 17h.01"></path>
                 </svg>`,
            maintenance: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="lucide lucide-wrench w-4 h-4 text-orange-500">
                        <path d="M14.7 6.3a1 1 0 0 0 0
                                 1.4l1.6 1.6a1 1 0 0 0
                                 1.4 0l3.77-3.77a6 6 0 0
                                 1-7.94 7.94l-6.91 6.91a2.12
                                 2.12 0 0 1-3-3l6.91-6.91a6
                                 6 0 0 1 7.94-7.94l-3.76
                                 3.76z"></path>
                    </svg>`,
            available: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                       viewBox="0 0 24 24" fill="none" stroke="currentColor"
                       stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                       class="lucide lucide-check-circle w-4 h-4 text-green-500">
                       <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                       <path d="m9 11 3 3L22 4"></path>
                   </svg>`,
            rented: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                   viewBox="0 0 24 24" fill="none" stroke="currentColor"
                   stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                   class="lucide lucide-truck w-4 h-4 text-blue-500">
                   <path d="M14 18V6a2 2 0 0 0-2-2H4a2 2
                            0 0 0-2 2v11a1 1 0 0 0
                            1 1h2"></path>
                   <path d="M15 18H9"></path>
                   <path d="M19 18h2a1 1 0 0 0
                            1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1
                            0 0 0 17.52 8H14"></path>
                   <circle cx="17" cy="18" r="2"></circle>
                   <circle cx="7" cy="18" r="2"></circle>
                </svg>`,
        };

        const equipment = rawEquipment.map(eq => ({
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
            lastInspection: eq.last_inspection ?? '-',
            orderproduct: eq.order_product?.product_name ?? '-',
            orderproductid: eq.order_product?.id ?? null,
            orderid: eq.order?.id ?? null,
            badge: eq.status_label,
            icon: icons[eq.current_status] ?? icons.available
        }));

        // console.log('equipment :- ', equipment);

        // console.log(equipment);
        let groups = []; // use 'let' so you can reassign

        function renderChecklist(groups) {}



        /* =================== ELEMENTS =================== */
        const equipmentList = document.getElementById("equipmentList");
        const equipmentCount = document.getElementById("equipmentCount");
        const checklistContainer = document.getElementById("checklistContainer");
        const checklistTitle = document.getElementById("checklistTitle");
        const checklistContent = document.getElementById("checklistContent");
        const placeholder = document.getElementById("placeholder");
        const equipmentHoursInput = document.getElementById("equipmentHours");
        const inspectionDateInput = document.getElementById("inspectionDate");
        const progressTop = document.getElementById("progressTop");
        const progressBottom = document.getElementById("progressBottom");
        const bar = document.getElementById("overallProgressBar");
        const btnReady = document.getElementById("btnReady");
        const btnDamaged = document.getElementById("btnDamaged");
        const reqMsg = document.getElementById("reqMsg");
        const inspectorSelect = document.getElementById("inspectorSelect");

        /* =================== INIT =================== */
        equipmentCount.textContent = `${equipment.length} of ${equipment.length}`;
        inspectionDateInput.value = new Date().toISOString().slice(0, 10);
        renderEquipment();

        window.applyFilters = function() {
            renderEquipment();
        };

        /* =================== LEFT LIST =================== */
        function renderEquipment(selectedId = null) {

            const searchValue = document.getElementById("searchInput").value.toLowerCase();
            const selectedCategory = document.getElementById("categoryFilter").value;
            const selectedStatus = document.getElementById("statusFilter").value;

            equipmentList.innerHTML = "";

            const filtered = equipment.filter(eq => {
                const name = (eq.name || "").toLowerCase();
                const model = (eq.model || "").toLowerCase();
                const serial = (eq.serial || "").toLowerCase();

                const matchesSearch =
                    name.includes(searchValue) ||
                    model.includes(searchValue) ||
                    serial.includes(searchValue);

                const matchesCategory =
                    selectedCategory === "All Categories" || eq.category === selectedCategory;

                const matchesStatus =
                    selectedStatus === "All Statuses" || eq.badge === selectedStatus;

                return matchesSearch && matchesCategory && matchesStatus;
            });


            //  Add sorting by badge priority
            const badgeOrder = {
                "Damaged": 1,
                "Maint. Hold": 2,
                "Rented": 3,
                "Available": 4
            };

            filtered.sort((a, b) => {
                const badgeDiff = (badgeOrder[a.badge] || 99) - (badgeOrder[b.badge] || 99);
                if (badgeDiff !== 0) return badgeDiff; // status sort first
                return a.name.localeCompare(b.name, undefined, {
                    sensitivity: 'base'
                }); // then alphabetical
            });
            filtered.forEach(eq => {
                const card = document.createElement("div");
                card.className = "equipment-card p-4 rounded-md border-1 transition-all cursor-pointer hover:shadow-md border-gray-200 hover:border-gray-300";
                card.innerHTML = `
      <div class="flex items-start justify-between">
        <div class="flex-1">
            <div class="flex flex-wrap items-start gap-2 mb-2">
                <div class="flex items-center gap-2 min-w-0 flex-1">
                    <h3 class="font-medium text-sm sm:text-base text-gray-900 truncate">${eq.name}</h3>
                    ${eq.icon}
                </div>

                <!-- Status pill: full-width on mobile (drops to 2nd line), inline-right on ≥sm -->
                <div class="basis-full sm:basis-auto sm:ml-auto">
                    <span class="inline-flex px-3 py-1 rounded-full text-xs font-medium border ${badgeColors(eq.badge)}">
                    ${eq.badge}
                    </span>
                </div>
            </div>
          <div class="text-sm text-gray-600 space-y-1">
            <div>Model: ${eq.model}</div>
            <div>Equipment Id : ${eq.equipment_id}</div>
            <div>Category: ${eq.category}</div>
            <div>Order Product: ${eq.orderproduct ?? '-'}</div>
            <div class="flex items-center gap-1">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Hours: ${ (eq.hours ?? 0).toLocaleString() }
            </div>
            <div>Last Inspection: ${eq.lastInspection}</div>
          </div>
        </div>

      </div>`;

                // Card click handler
                card.addEventListener("click", () => {
                    document.querySelectorAll(".equipment-card").forEach(c => {
                        c.classList.remove("border-blue-500", "bg-blue-50");
                        c.classList.add("border-gray-200");
                    });
                    card.classList.add("border-blue-500", "bg-blue-50");
                    openChecklist(eq);
                });
                equipmentList.appendChild(card);


                //  Auto-select if it matches selectedEquipmentId
                if (selectedId && eq.unique_id == selectedId) {
                    card.classList.add("border-blue-500", "bg-blue-50");
                    openChecklist(eq);
                }

            });
        }

        function badgeColors(b) {
            if (b === "Damaged") return "bg-red-100 text-red-800 border-red-200";
            if (b === "Maint. Hold") return "bg-orange-100 text-orange-800 border-orange-200";
            if (b === "Available") return "bg-green-100 text-green-800 border-green-200";
            if (b === "Rented") return "bg-blue-100 text-blue-800 border-blue-200";
            return "bg-gray-100 text-gray-700 border-gray-200";
        }

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

        @if($selectedEquipmentId)
        const selectedEquipmentId = @json($selectedEquipmentId);
        renderEquipment(selectedEquipmentId);
        @else
        renderEquipment();
        @endif



        /* =================== RIGHT: OPEN CHECKLIST =================== */
        function openChecklist(eq) {
            window.currentEquipment = eq;

            // console.log('openChecklist :-');
            // console.log(currentEquipment);


            const footerButton = document.getElementById("footerbutton"); // get the footer button

            if (eq.orderproductid == null) {
                // Fresh template (no order product linked)
                footerButton.classList.remove("hidden");
            } else {
                // Has order product → hide/show based on status
                if (eq.badge === "Available") {
                    footerButton.classList.add("hidden");
                } else {
                    footerButton.classList.remove("hidden");
                }
            }

            //  ensure rented equipment always stays hidden
            if (eq.badge === "Rented") {
                footerButton.classList.add("hidden");
            }

            placeholder.classList.add("hidden");
            checklistContainer.classList.remove("hidden");
            checklistTitle.textContent = `Rental Ready Checklist - ${eq.name}`;
            equipmentHoursInput.value = eq.hours;
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
            fetch(`{{ route('admin.checklist-management.equipment-management.get-checklist-questions') }}`, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: JSON.stringify({
                        checklist_id: eq.checklist_master_id,
                        equipment_id: eq.id,
                        order_product_id: eq.orderproductid
                    })
                })
                .then(res => res.json())
                .then(data => {


                    // remove loader
                    checklistContent.classList.remove("opacity-50", "pointer-events-none");
                    checklistContent.innerHTML = "";

                    if (data.success === true) {
                        // console.log("Fetched checklist data:", data);

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
                                card.className = "item-card p-4 rounded-md border-2 transition-all border-gray-200 bg-white-50";
                                card.id = `item-${itemId}`;
                                card.innerHTML = `
                        <div class="flex flex-wrap items-center gap-2 mb-3">
                            <span id="icon-${itemId}" class="inline-flex shrink-0">${iconSvg("default")}</span>
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
                                        const itemObj = group.items.find(it => (it.question_id || it.id) === itemId);
                                        if (itemObj) {
                                            itemObj.answer_id = saved.answer_id;
                                            itemObj.status = saved.status;
                                            itemObj.notes = saved.notes || "";
                                        }
                                    }
                                }


                                if (saved.notes) {
                                    const notesBox = document.querySelector(`#notes-${itemId} textarea`);
                                    if (notesBox) {
                                        notesBox.value = saved.notes;
                                        notesBox.parentElement.classList.remove("hidden");
                                    }
                                }
                            });
                        }

                        if (data.existing_data && data.existing_data.general_notes) {
                            document.getElementById("generalNotes").value = data.existing_data.general_notes;
                        }

                        if (data.existing_data && data.existing_data.existingTemplate && data.existing_data.existingTemplate.employee_id) {
                            document.getElementById("inspectorSelect").value = data.existing_data.existingTemplate.employee_id;
                        }

                        if (data.existing_data && data.existing_data.existingTemplate && data.existing_data.existingTemplate.equipment_hours) {
                            document.getElementById("equipmentHours").value = data.existing_data.existingTemplate.equipment_hours;
                        }

                        checklistContent.querySelectorAll('input[type="radio"]').forEach(r => {
                            r.addEventListener("change", onChoice);
                        });

                        updateAllCounts();
                        updateProgress();
                    } else {
                        notyf.error(data.message);


                        checklistContent.innerHTML = `<p class="text-red-500">${data.message}</p>`;
                    }
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

            updateGroupCount(groupKey);
            updateProgress();
        }


        function colorItemCard(itemId, status) {
            const box = document.getElementById(`item-${itemId}`);
            const chip = document.getElementById(`chip-${itemId}`);
            const notes = document.getElementById(`notes-${itemId}`);

            box.classList.remove("bg-green-50", "bg-yellow-50", "bg-red-50", "bg-gray-50",
                "border-green-300", "border-yellow-300", "border-red-300", "border-gray-200");
            chip.className = "ml-auto text-xs px-2 py-1 rounded-full font-medium";

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
                    const picked = document.querySelector(`input[name="answer-${itemId}"]:checked`);
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


            const inspectorOk = (typeof REQUIRE_INSPECTOR === "boolean" ? !REQUIRE_INSPECTOR : true) || (inspectorSelect && inspectorSelect.value !== "");
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
            if (status === "Rental Ready") return `<div class="flex items-center gap-2 flex-shrink-0"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-circle w-4 h-4 text-green-500"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><path d="m9 11 3 3L22 4"></path></svg> <span class="text-xs px-2 py-1 rounded-full font-medium bg-green-100 text-green-800">Rental Ready</span></div>`;

            if (status === "Maint. Hold") return `<div class="flex items-center gap-2 flex-shrink-0"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-alert-circle w-4 h-4 text-orange-500"><circle cx="12" cy="12" r="10"></circle><line x1="12" x2="12" y1="8" y2="12"></line><line x1="12" x2="12.01" y1="16" y2="16"></line></svg><span class="text-xs px-2 py-1 rounded-full font-medium bg-orange-100 text-orange-800">Maint. Hold</span></div>`;

            if (status === "Damaged") return `<div class="flex items-center gap-2 flex-shrink-0"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-alert-triangle w-4 h-4 text-red-500"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><path d="M12 9v4"></path><path d="M12 17h.01"></path></svg><span class="text-xs px-2 py-1 rounded-full font-medium bg-red-100 text-red-800">Damaged</span></div>`;
            return `<span class="text-xs px-2 py-1 rounded-full font-medium bg-gray-100 text-gray-600">—</span>`;
        }

        function sectionIcon() {
            return `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-circle w-4 h-4 text-green-500"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><path d="m9 11 3 3L22 4"></path></svg>`;
        }

        function iconSvg(type) {
            if (type === "ready")
                return `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-circle w-4 h-4 text-green-500"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><path d="m9 11 3 3L22 4"></path></svg>`;
            if (type === "hold")
                return `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-alert-circle w-4 h-4 text-orange-500"><circle cx="12" cy="12" r="10"></circle><line x1="12" x2="12" y1="8" y2="12"></line><line x1="12" x2="12.01" y1="16" y2="16"></line></svg>`;
            if (type === "damaged")
                return `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-alert-triangle w-4 h-4 text-red-500"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><path d="M12 9v4"></path><path d="M12 17h.01"></path></svg>`;
            return `<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>`;
        }

        /* =================== LISTENERS =================== */
        if (inspectorSelect) inspectorSelect.addEventListener("change", updateProgress);

        window.computeStatusSummary = computeStatusSummary;

    }); // DOMContentLoaded


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
                    const isSelected = picked && parseInt(picked.dataset.optId, 10) === opt.id;
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
                    const chosenOpt = item.answers.find(opt => opt.id === parseInt(picked.dataset.optId, 10));
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
