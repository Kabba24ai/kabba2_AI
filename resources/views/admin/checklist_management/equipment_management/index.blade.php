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
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
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
                        class="w-full border px-3 py-2 rounded-md text-sm" onclick="applyFilters()">
                        <option>All Categories</option>
                        <option>Compact Equipment</option>
                        <option>Heavy Equipment</option>
                    </select>
                </div>

                <!-- Status -->
                <div class="mb-4">
                    <label class="text-sm font-medium text-gray-700 mb-1 block">Status</label>
                    <select  class="w-full border px-3 py-2 rounded-md text-sm" id="statusFilter" onclick="applyFilters()">
                        <option>All Statuses</option>
                        <option>Active</option>
                        <option>Damaged</option>
                        <option>Maintenance</option>
                    </select>
                </div>
                <div id="equipmentList" class="space-y-3 max-h-[1000px] overflow-y-auto"></div>
            </div>
        </div>

        <!-- RIGHT: Checklist -->
        <div class="lg:col-span-2">
            <!-- Placeholder -->
            <div id="placeholder" class="bg-white rounded-md shadow-sm border border-gray-200 p-8 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clipboard-list w-16 h-16 text-gray-300 mx-auto mb-4"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"></rect><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><path d="M12 11h4"></path><path d="M12 16h4"></path><path d="M8 11h.01"></path><path d="M8 16h.01"></path>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Select Equipment to Begin</h3>
                <p class="text-gray-600">Choose equipment from the list to start the rental ready inspection process.</p>
            </div>

            <!-- Checklist Container -->
            <div id="checklistContainer" class="hidden bg-white rounded-md shadow-sm border border-gray-200 p-6">
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 id="checklistTitle" class="text-xl font-semibold text-gray-900">Rental Ready Checklist</h2>
                        <div class="text-sm text-gray-600" id="progressTop">0 of 10 items completed</div>
                    </div>

                    <!-- Header row -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1 block">Inspector Name *</label>
                            <select id="inspectorSelect" class="w-full border px-3 py-2 rounded-md text-sm">
                                <option value="">Select Inspector</option>
                                <option>John Smith</option>
                                <option>Sarah Johnson</option>
                                <option>Mike Rodriguez</option>
                                <option>Emily Chen</option>
                                <option>David Wilson</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1 block">Equipment Hours</label>
                            <input id="equipmentHours" type="number" class="w-full border px-3 py-2 rounded-md text-sm" placeholder="Enter hours" min="0" step="0.1">
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1 block">Inspection Date</label>
                            <input id="inspectionDate" type="date" class="w-full border px-3 py-2 rounded-md text-sm">
                        </div>
                    </div>
                </div>

                <!-- Dynamic checklist goes here -->
                <div id="checklistContent" class="space-y-6"></div>

                <!-- Footer summary with progress bar -->
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
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
                        <button id="btnReady" disabled class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-lg font-medium transition-all bg-gray-100 text-gray-400 cursor-not-allowed">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>
                            Mark as Rental Ready
                        </button>
                        <button id="btnDamaged" disabled class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-lg font-medium transition-all bg-gray-100 text-gray-400 cursor-not-allowed">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                            Mark as Damaged
                        </button>
                        <button class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-lg font-medium bg-gray-600 hover:bg-gray-700 text-white transition-all shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            Save Draft
                        </button>
                    </div>
                    <div class="mt-4 space-y-2">
                        <div class="text-sm text-red-600 bg-red-50 p-3 rounded-lg hidden" id="reqMsg">Inspector name is required to complete the checklist.</div>
                    </div>
                </div>
            </div>
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
const equipment = [
  { id:1, name:"Compactor CS56", icon:' <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-alert-triangle w-4 h-4 text-red-500"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><path d="M12 9v4"></path><path d="M12 17h.01"></path></svg>',model:"CAT CS56", serial:"CATCS56005", category:"Compact Equipment", hours:1680, lastInspection:"2024-01-08", badge:"Damaged" },

  { id:2, name:"Forklift 2.5T", icon:' <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-alert-triangle w-4 h-4 text-red-500"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><path d="M12 9v4"></path><path d="M12 17h.01"></path></svg>', model:"Toyota 8FGU25", serial:"TOY25010",  category:"Compact Equipment", hours:2200, lastInspection:"2024-01-05", badge:"Damaged" },

  { id:3, name:"Air Compressor 185CFM", icon:' <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-wrench w-4 h-4 text-orange-500"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>', model:"Atlas Copco 185", serial:"AC185006", category:"Power Equipment", hours:320, lastInspection:"2024-01-18", badge:"Maint. Hold" },

];

const groups = [
  { title:"Safety", key:"safety", items:[
    { id:"safety-1", title:"Safety Equipment Present", required:true, options:[
      {label:"All Present & Functional", status:"Rental Ready"},
      {label:"Present but Needs Cleaning", status:"Rental Ready"},
      {label:"Missing Non-Critical Items", status:"Maint. Hold"},
      {label:"Missing Critical Items", status:"Damaged"},
      {label:"Equipment Damaged/Non-Functional", status:"Damaged"},
    ]},
    { id:"safety-2", title:"Warning Labels Visible", required:true, options:[
      {label:"All Labels Clear & Visible", status:"Rental Ready"},
      {label:"Labels Present but Faded", status:"Rental Ready"},
      {label:"Some Labels Missing", status:"Maint. Hold"},
      {label:"Critical Labels Missing", status:"Damaged"},
    ]},
  ]},
  { title:"Engine", key:"engine", items:[
    { id:"engine-1", title:"Engine Oil Level", required:true, options:[
      {label:"Full", status:"Rental Ready"},
      {label:"3/4 Full", status:"Rental Ready"},
      {label:"1/2 Full", status:"Maint. Hold"},
      {label:"1/4 Full", status:"Maint. Hold"},
      {label:"Low/Empty", status:"Damaged"},
      {label:"Leaking", status:"Damaged"},
    ]},
    { id:"engine-2", title:"Coolant Level", required:true, options:[
      {label:"Full", status:"Rental Ready"},
      {label:"3/4 Full", status:"Rental Ready"},
      {label:"1/2 Full", status:"Maint. Hold"},
      {label:"1/4 Full", status:"Maint. Hold"},
      {label:"Low/Empty", status:"Damaged"},
      {label:"Leaking", status:"Damaged"},
    ]},
  ]},
  { title:"Hydraulics", key:"hydraulics", items:[
    { id:"hydraulic-1", title:"Hydraulic Fluid Level", required:true, options:[
      {label:"Full", status:"Rental Ready"},
      {label:"3/4 Full", status:"Rental Ready"},
      {label:"1/2 Full", status:"Maint. Hold"},
      {label:"Empty", status:"Maint. Hold"},
      {label:"Leaking", status:"Damaged"},
    ]},
    { id:"hydraulic-2", title:"Hydraulic Hoses Condition", required:true, options:[
      {label:"Excellent Condition", status:"Rental Ready"},
      {label:"Good Condition", status:"Rental Ready"},
      {label:"Minor Wear/Scuffs", status:"Rental Ready"},
      {label:"Significant Wear", status:"Maint. Hold"},
      {label:"Cracked/Damaged", status:"Damaged"},
      {label:"Leaking", status:"Damaged"},
    ]},
  ]},
  { title:"Fuel", key:"fuel", items:[
    { id:"fuel-1", title:"Fuel Level", required:true, options:[
      {label:"Full Tank", status:"Rental Ready"},
      {label:"3/4 Full", status:"Rental Ready"},
      {label:"1/2 Full", status:"Rental Ready"},
      {label:"1/4 Full", status:"Maint. Hold"},
      {label:"Low/Empty", status:"Maint. Hold"},
    ]},
  ]},
  { title:"Electrical", key:"electrical", items:[
    { id:"electrical-1", title:"Battery Condition", required:true, options:[
      {label:"Excellent - Full Charge", status:"Rental Ready"},
      {label:"Good - Holds Charge", status:"Rental Ready"},
      {label:"Fair - Weak Charge", status:"Maint. Hold"},
      {label:"Poor - Won't Hold Charge", status:"Damaged"},
      {label:"Dead/Corroded", status:"Damaged"},
    ]},
    { id:"electrical-2", title:"Lights Functioning", required:true, options:[
      {label:"All Lights Working", status:"Rental Ready"},
      {label:"Most Lights Working", status:"Rental Ready"},
      {label:"Some Lights Out", status:"Maint. Hold"},
      {label:"Many Lights Out", status:"Damaged"},
      {label:"No Lights Working", status:"Damaged"},
    ]},
  ]},
  { title:"General", key:"general", items:[
    { id:"general-1", title:"Overall Cleanliness", required:false, options:[
      {label:"Spotless", status:"Rental Ready"},
      {label:"Clean", status:"Rental Ready"},
      {label:"Needs Light Cleaning", status:"Rental Ready"},
      {label:"Needs Deep Cleaning", status:"Maint. Hold"},
      {label:"Extremely Dirty", status:"Maint. Hold"},
    ]},
  ]},
];

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
inspectionDateInput.value = new Date().toISOString().slice(0,10);
renderEquipment();

window.applyFilters = function () {
  renderEquipment();
};

/* =================== LEFT LIST =================== */
function renderEquipment() {

     const searchValue = document.getElementById("searchInput").value.toLowerCase();
  const selectedCategory = document.getElementById("categoryFilter").value;
  const selectedStatus = document.getElementById("statusFilter").value;

  equipmentList.innerHTML = "";

    const filtered = equipment.filter(eq => {
    const matchesSearch =
      eq.name.toLowerCase().includes(searchValue) ||
      eq.model.toLowerCase().includes(searchValue) ||
      eq.serial.toLowerCase().includes(searchValue);

    const matchesCategory =
      selectedCategory === "All Categories" || eq.category === selectedCategory;

    const matchesStatus =
      selectedStatus === "All Statuses" || 
      (selectedStatus === "Maintenance" && eq.badge === "Maint. Hold") ||
      eq.badge === selectedStatus;

    return matchesSearch && matchesCategory && matchesStatus;
  });

  filtered.forEach(eq => {
    const card = document.createElement("div");
    card.className = "equipment-card p-4 rounded-md border-1 transition-all cursor-pointer hover:shadow-md border-gray-200 hover:border-gray-300";
    card.innerHTML = `
      <div class="flex items-start justify-between">
        <div class="flex-1">
          <div class="flex items-center gap-2 mb-2">
            <h3 class="font-medium text-gray-900">${eq.name}</h3>
            ${eq.icon}
          </div>
          <div class="text-sm text-gray-600 space-y-1">
            <div>Model: ${eq.model}</div>
            <div>Serial: ${eq.serial}</div>
            <div>Category: ${eq.category}</div>
            <div class="flex items-center gap-1">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              Hours: ${eq.hours.toLocaleString()}
            </div>
            <div>Last Inspection: ${eq.lastInspection}</div>
          </div>
        </div>
        <div class="px-3 py-1 rounded-full text-xs font-medium border ${badgeColors(eq.badge)}">${eq.badge}</div>
      </div>`;
    card.addEventListener("click", () => {
      document.querySelectorAll(".equipment-card").forEach(c=>{
        c.classList.remove("border-blue-500","bg-blue-50"); c.classList.add("border-gray-200");
      });
      card.classList.add("border-blue-500","bg-blue-50");
      openChecklist(eq);
    });
    equipmentList.appendChild(card);
  });
}
function badgeColors(b){
  if(b==="Damaged") return "bg-red-100 text-red-800 border-red-200";
  if(b==="Maint. Hold") return "bg-orange-100 text-orange-800 border-orange-200";
  if(b==="Available") return "bg-green-100 text-green-800 border-green-200";
  if(b==="Rented") return "bg-blue-100 text-blue-800 border-blue-200";
  return "bg-gray-100 text-gray-700 border-gray-200";
}

/* =================== RIGHT: OPEN CHECKLIST =================== */
function openChecklist(eq){
  placeholder.classList.add("hidden");
  checklistContainer.classList.remove("hidden");
  checklistTitle.textContent = `Rental Ready Checklist - ${eq.name}`;
  equipmentHoursInput.value = eq.hours;
  checklistContent.innerHTML = "";

  groups.forEach((g) => {
    const section = document.createElement("div");
    section.className = "border border-gray-200 rounded-lg p-4";
    section.innerHTML = `
      <h3 class="font-medium text-gray-900 mb-4 flex items-center gap-2">
        <span>${g.title}</span>
        <span id="groupCount-${g.key}" class="text-sm text-gray-500">(0/${g.items.length})</span>
      </h3>
      <div id="groupBody-${g.key}" class="space-y-4"></div>`;
    checklistContent.appendChild(section);

    const body = section.querySelector(`#groupBody-${g.key}`);
    g.items.forEach((item) => {
      const card = document.createElement("div");
      card.className = "item-card p-4 rounded-lg border-2 transition-all border-gray-200 bg-gray-50";
      card.id = `item-${item.id}`;
      const options = item.options.map((opt) => `
        <label class="flex items-center justify-between gap-3 p-2 bg-white rounded-lg border border-gray-200 cursor-pointer hover:bg-gray-50 transition-colors">
          <div class="flex items-center gap-3">
            <input type="radio" name="answer-${item.id}" class="w-4 h-4 text-blue-600 focus:ring-blue-500"
                   value="${opt.status}" data-item="${item.id}" data-group="${g.key}">
            <span class="text-sm text-gray-900">${opt.label}</span>
          </div>
          ${statusBadge(opt.status)}
        </label>
      `).join("");

      card.innerHTML = `
        <div class="flex items-center gap-2 mb-3">
          <span id="icon-${item.id}" class="inline-flex">${iconSvg("default")}</span>
          <span class="font-medium text-gray-900">${item.title}${item.required ? '<span class="text-red-500 ml-1">*</span>':''}</span>
          <span id="chip-${item.id}"></span>
        </div>
        <div class="text-sm font-medium text-gray-700 mb-2">Select Condition:</div>
        <div class="space-y-2">${options}</div>
        <div id="notes-${item.id}" class="hidden mt-3">
          <textarea class="w-full bg-white border px-3 py-2 rounded-md text-sm text-sm border-gray-300" rows="3" placeholder="Add notes about the issue..."></textarea>
        </div>
      `;
      body.appendChild(card);
    });
  });

   /* ---------- General Notes card (NEW) ---------- */
  const generalNotes = document.createElement("div");
  generalNotes.className = "";
  generalNotes.innerHTML = `
    <h3 class="font-medium text-gray-700 mb-3 flex items-center gap-2">
      <span>General Notes</span>
    </h3>
    <textarea id="generalNotes" class="w-full bg-white border px-3 py-2 rounded-md text-sm border-gray-300" rows="4" placeholder="Add any additional notes or observations..."></textarea>
  `;
    checklistContent.appendChild(generalNotes);

  checklistContent.querySelectorAll('input[type="radio"]').forEach(r => {
    r.addEventListener("change", onChoice);
  });

  updateAllCounts();
  updateProgress();
}

/* =================== HANDLERS =================== */
function onChoice(e){
  const status = e.target.value;
  const itemId = e.target.dataset.item;
  const groupKey = e.target.dataset.group;

  colorItemCard(itemId, status);
  setHeaderIcon(itemId, status);

  updateGroupCount(groupKey);
  updateProgress();
}

function colorItemCard(itemId, status){
  const box  = document.getElementById(`item-${itemId}`);
  const chip = document.getElementById(`chip-${itemId}`);
  const notes = document.getElementById(`notes-${itemId}`);

  box.classList.remove("bg-green-50","bg-yellow-50","bg-red-50","bg-gray-50",
                       "border-green-300","border-yellow-300","border-red-300","border-gray-200");
  chip.className = "ml-auto text-xs px-2 py-1 rounded-full font-medium";

  if(status === "Rental Ready"){
    box.classList.add("bg-green-50","border-green-300");
    chip.classList.add("bg-green-100","text-green-800");
    chip.textContent = "Rental Ready";
    notes.classList.add("hidden");
  }else if(status === "Maint. Hold"){
    box.classList.add("bg-yellow-50","border-yellow-300");
    chip.classList.add("bg-orange-100","text-orange-800");
    chip.textContent = "Maint. Hold";
    notes.classList.remove("hidden");
  }else if(status === "Damaged"){
    box.classList.add("bg-red-50","border-red-300");
    chip.classList.add("bg-red-100","text-red-800");
    chip.textContent = "Damaged";
    notes.classList.remove("hidden");
  }else{
    box.classList.add("bg-gray-50","border-gray-200");
    chip.classList.add("bg-gray-100","text-gray-600");
    chip.textContent = "—";
    notes.classList.add("hidden");
  }
}

function setHeaderIcon(itemId, status){
  const holder = document.getElementById(`icon-${itemId}`);
  if(!holder) return;
  if(status === "Rental Ready") holder.innerHTML = iconSvg("ready");
  else if(status === "Maint. Hold") holder.innerHTML = iconSvg("hold");
  else if(status === "Damaged") holder.innerHTML = iconSvg("damaged");
  else holder.innerHTML = iconSvg("default");
}

/* =================== PROGRESS & BUTTONS =================== */
function computeStatusSummary(){
  const total = groups.reduce((n,g)=>n+g.items.length,0);
  const requiredTotal = groups.reduce((n,g)=> n + g.items.filter(it => it.required).length, 0);

  let completed = 0, ready = 0, hold = 0, damaged = 0, requiredCompleted = 0;

  groups.forEach(g=>{
    g.items.forEach(item=>{
      const picked = document.querySelector(`input[name="answer-${item.id}"]:checked`);
      if (picked){
        completed++;
        if (item.required) requiredCompleted++;
        const s = picked.value;
        if (s === "Rental Ready") ready++;
        else if (s === "Maint. Hold") hold++;
        else if (s === "Damaged") damaged++;
      }
    });
  });

  return { total, requiredTotal, completed, requiredCompleted, ready, hold, damaged };
}

function updateProgress(){
  const { total, requiredTotal, completed, requiredCompleted, ready, hold, damaged } = computeStatusSummary();
  const pct = Math.round((completed/total)*100);

  progressTop.textContent = `${completed} of ${total} items completed`;
  progressBottom.textContent = `${completed} of ${total} items completed (${pct}%)`;
  bar.style.width = pct + "%";

  // ensure counters exist, then update them
  const { req, maint, dmg } = ensureCounters();
  if (req)   req.textContent   = `${requiredCompleted}/${requiredTotal}`;
  if (maint) maint.textContent = hold;
  if (dmg)   dmg.textContent   = damaged;

  const inspectorOk = (typeof REQUIRE_INSPECTOR === "boolean" ? !REQUIRE_INSPECTOR : true) || (inspectorSelect && inspectorSelect.value !== "");
  const allAnswered = (completed === total);
  const allReady    = allAnswered && (ready === total);
  const anyDamaged  = allAnswered && (damaged > 0);

  // default → both visible but disabled gray
  setBtn(btnReady,   { enabled:false, color:"green", show:true });
  setBtn(btnDamaged, { enabled:false, color:"red",   show:true });

  if (allReady && inspectorOk){
    setBtn(btnReady,   { enabled:true,  color:"green", show:true });
    setBtn(btnDamaged, { enabled:false, color:"red",   show:false });
  } else if (anyDamaged && inspectorOk){
    setBtn(btnReady,   { enabled:false, color:"green", show:false });
    setBtn(btnDamaged, { enabled:true,  color:"red",   show:true });
  }

  if (reqMsg) reqMsg.classList.toggle("hidden", inspectorOk);
}

function setBtn(btn, { enabled, color, show }){
  if (!btn) return;
  btn.classList.toggle("hidden", show === false);
  btn.classList.remove(
    "bg-gray-100","text-gray-400","cursor-not-allowed",
    "bg-blue-600","hover:bg-blue-700",
    "bg-green-600","hover:bg-green-700",
    "bg-red-600","hover:bg-red-700","text-white"
  );
  if (!enabled){
    btn.disabled = true;
    btn.classList.add("bg-gray-100","text-gray-400","cursor-not-allowed");
  } else {
    btn.disabled = false;
    btn.classList.add("text-white");
    if (color === "green") btn.classList.add("bg-green-600","hover:bg-green-700");
    else if (color === "red") btn.classList.add("bg-red-600","hover:bg-red-700");
    else btn.classList.add("bg-blue-600","hover:bg-blue-700");
  }
}

/* =================== COUNTS =================== */
function updateGroupCount(groupKey){
  const group = groups.find(g=>g.key===groupKey);
  if(!group) return;
  let done = 0;
  group.items.forEach(item=>{
    if(document.querySelector(`input[name="answer-${item.id}"]:checked`)) done++;
  });
  const el = document.getElementById(`groupCount-${groupKey}`);
  if(el) el.textContent = `(${done}/${group.items.length})`;
}
function updateAllCounts(){ groups.forEach(g=>updateGroupCount(g.key)); }

/* =================== ICONS & BADGES =================== */
function statusBadge(status){
  if(status==="Rental Ready") return `<div class="flex items-center gap-2 flex-shrink-0"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-circle w-4 h-4 text-green-500"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><path d="m9 11 3 3L22 4"></path></svg> <span class="text-xs px-2 py-1 rounded-full font-medium bg-green-100 text-green-800">Rental Ready</span></div>`;

  if(status==="Maint. Hold")  return `<div class="flex items-center gap-2 flex-shrink-0"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-alert-circle w-4 h-4 text-orange-500"><circle cx="12" cy="12" r="10"></circle><line x1="12" x2="12" y1="8" y2="12"></line><line x1="12" x2="12.01" y1="16" y2="16"></line></svg><span class="text-xs px-2 py-1 rounded-full font-medium bg-orange-100 text-orange-800">Maint. Hold</span></div>`;

  if(status==="Damaged")  return `<div class="flex items-center gap-2 flex-shrink-0"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-alert-triangle w-4 h-4 text-red-500"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><path d="M12 9v4"></path><path d="M12 17h.01"></path></svg><span class="text-xs px-2 py-1 rounded-full font-medium bg-red-100 text-red-800">Damaged</span></div>`;
  return `<span class="text-xs px-2 py-1 rounded-full font-medium bg-gray-100 text-gray-600">—</span>`;
}
function sectionIcon(){
  return `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-circle w-4 h-4 text-green-500"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><path d="m9 11 3 3L22 4"></path></svg>`;
}
function iconSvg(type){
  if(type==="ready")
    return `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-circle w-4 h-4 text-green-500"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><path d="m9 11 3 3L22 4"></path></svg>`;
  if(type==="hold")
    return `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-alert-circle w-4 h-4 text-orange-500"><circle cx="12" cy="12" r="10"></circle><line x1="12" x2="12" y1="8" y2="12"></line><line x1="12" x2="12.01" y1="16" y2="16"></line></svg>`;
  if(type==="damaged")
    return `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-alert-triangle w-4 h-4 text-red-500"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><path d="M12 9v4"></path><path d="M12 17h.01"></path></svg>`;
  return `<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>`;
}

/* =================== LISTENERS =================== */
if (inspectorSelect) inspectorSelect.addEventListener("change", updateProgress);

}); // DOMContentLoaded



// --- creates the 3 counters below the progress bar if they don't exist ---
function ensureCounters() {
  const pb = document.getElementById("progressBottom");
  if (!pb) return { req: null, maint: null, dmg: null };

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
    dmg: document.getElementById("damagedItems"),
  };
}



</script>



@endpush

