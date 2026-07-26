@extends('admin.layouts.app')

@section('title', 'New Field Service Request')

@push('css')
<style>
    main { background-color: #f8fafc; flex: 1 1 auto; }
    /* Two-option segmented decision control — self-contained CSS so it renders
       correctly in production without an asset rebuild (deploys don't rebuild
       assets). Neither segment is active until a radio inside is :checked. */
    .fs-sr { position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; border:0; }
    .fs-seg-group { display:inline-flex; border:1px solid #d1d5db; border-radius:0.5rem; overflow:hidden; background:#fff; }
    .fs-seg { display:inline-flex; align-items:center; justify-content:center; min-width:9.5rem; padding:0.5rem 1rem; font-size:0.875rem; font-weight:500; color:#4b5563; background:#fff; cursor:pointer; user-select:none; transition:background-color .15s ease,color .15s ease; }
    .fs-seg:hover { background:#f9fafb; }
    .fs-seg + .fs-seg { border-left:1px solid #d1d5db; }
    .fs-seg:has(input:checked) { background:#2563eb; color:#fff; }
    .fs-seg:has(input:checked):hover { background:#1d4ed8; }
    .fs-seg:focus-within { outline:2px solid #60a5fa; outline-offset:-2px; }
</style>
@endpush

@section('content')

    @include('flash::message')

    @php
        use App\Enums\FieldService\FieldMachineStatus;
        use App\Enums\FieldService\FieldOperationalExpectation;
        use App\Enums\FieldService\FieldRecoveryRisk;
        use App\Enums\FieldService\FieldSafetyConcern;
        use App\Enums\FieldService\FieldSiteAccess;
        use App\Enums\FieldService\FieldYesNoUnknown;
        use App\Enums\Service\ServicePriority;
        $inputClass = 'w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm bg-white text-gray-900 focus:ring focus:border-blue-400 outline-none disabled:bg-gray-50 disabled:text-gray-400';
        $labelClass = 'block text-sm font-medium text-gray-700 mb-1';
    @endphp

    {{-- Centered working container — same discipline as the Standard Service
         intake (max-w-4xl), heading aligned with the form cards. --}}
    <div class="max-w-4xl mx-auto">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold flex items-center gap-2">
                <x-heroicon-o-truck class="w-6 h-6 text-blue-600" />
                New Field Service Request
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                A field mission: send a technician to assess and stabilize a customer-site incident.
                One question drives this form — <span class="font-medium text-gray-600">what does the technician need before leaving the shop?</span>
            </p>
        </div>
        <a href="{{ route('admin.service-management.board') }}"
            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-gray-300 bg-white text-sm text-gray-600 hover:bg-gray-50 transition">
            <x-heroicon-o-arrow-left class="w-4 h-4" />
            Back to Board
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3">
            <p class="text-sm font-semibold text-red-700 mb-1">Please correct the following:</p>
            <ul class="text-sm text-red-600 list-disc pl-5 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.field-service.tickets.store') }}" enctype="multipart/form-data">
        @csrf

        {{-- ===== 1. Dispatch Information ===== --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
            <h2 class="text-sm font-semibold text-gray-800 mb-1">1 · Dispatch Information</h2>
            <p class="text-xs text-gray-400 mb-4">
                The order answers what the system already knows. You decide only where we go and who we meet.
            </p>

            {{-- Customer Order — the canonical selector. --}}
            <div class="mb-4">
                <label class="{{ $labelClass }} required">Customer Order</label>
                <select name="order_id" id="fs-order" class="{{ $inputClass }}">
                    <option value="">Search order # or customer…</option>
                    @foreach ($orderOptions as $order)
                        <option value="{{ $order['id'] }}"
                            @if(!empty($order['reference'])) data-custom-properties='{"reference":"{{ $order['reference'] }}"}' @endif
                            @selected((int) old('order_id') === $order['id'])>{{ $order['label'] }}</option>
                    @endforeach
                </select>
                @error('order_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Customer — display only. --}}
                <div>
                    <label class="{{ $labelClass }}">Customer</label>
                    <div id="fs-customer" class="px-3 py-2.5 rounded-md border border-gray-200 bg-gray-50 text-sm text-gray-600">Select an order…</div>
                </div>

                {{-- Equipment — single order: read-only; multiple: selector. --}}
                <div>
                    <label class="{{ $labelClass }} required">Equipment</label>
                    {{-- The one posted equipment id, driven by the JS below. --}}
                    <input type="hidden" name="equipment_id" id="fs-equipment" value="{{ old('equipment_id') }}">
                    <div id="fs-equipment-single" class="hidden px-3 py-2.5 rounded-md border border-gray-200 bg-gray-50 text-sm text-gray-800"></div>
                    <div id="fs-equipment-multi" class="hidden space-y-1.5"></div>
                    <div id="fs-equipment-empty" class="px-3 py-2.5 rounded-md border border-gray-200 bg-gray-50 text-sm text-gray-400">Select an order…</div>
                    <p id="fs-equipment-detail" class="text-xs text-gray-400 mt-1"></p>
                    @error('equipment_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    <input type="hidden" name="serial_number" id="fs-serial" value="{{ old('serial_number') }}">
                </div>
            </div>

            {{-- Contact Person — a REQUIRED, explicit decision. No default: the
                 ticket must never instruct a technician to ask for someone the
                 dispatcher did not consciously confirm. --}}
            <div class="mt-4">
                <label class="{{ $labelClass }} required">Who should the technician ask for?</label>
                <div class="fs-seg-group" role="radiogroup" aria-label="Who should the technician ask for?">
                    <label class="fs-seg">
                        <input type="radio" name="contact_source" value="order" class="fs-sr fs-contact-src" @checked(old('contact_source') === 'order')>
                        <span>Order Contact</span>
                    </label>
                    <label class="fs-seg">
                        <input type="radio" name="contact_source" value="other" class="fs-sr fs-contact-src" @checked(old('contact_source') === 'other')>
                        <span>Someone Else</span>
                    </label>
                </div>
                <p id="fs-contact-hint" class="text-xs text-gray-400 mt-1.5">Confirm who to ask for before dispatching — this becomes the technician's instruction.</p>
                @error('contact_source')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                {{-- Order contact (read-only display) — shown only after "Order Contact". --}}
                <div id="fs-contact-order" class="hidden mt-2 rounded-md border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm">
                    <div id="fs-contact-order-name" class="font-medium text-gray-800">—</div>
                    <div id="fs-contact-order-phone" class="text-gray-500">—</div>
                </div>
                {{-- Someone else (intentional entry) — shown only after "Someone Else". --}}
                <div id="fs-contact-other" class="hidden mt-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="{{ $labelClass }} required">Contact Name</label>
                        <input type="text" name="contact_name" id="fs-contact-name" value="{{ old('contact_name') }}" class="{{ $inputClass }}">
                        @error('contact_name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $labelClass }} required">Phone Number</label>
                        {{-- Shared Kaaba phone input: .masked-phone → IMask (555) 555-5555 (admin app.js). --}}
                        <input type="text" inputmode="tel" name="contact_phone" id="fs-contact-phone" value="{{ old('contact_phone') }}" placeholder="(555) 555-5555" class="{{ $inputClass }} masked-phone">
                        @error('contact_phone')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            {{-- Service Location — a REQUIRED, explicit decision. No default: a
                 technician is never sent to an address the dispatcher did not
                 consciously confirm; the billing address is never a fallback. --}}
            <div class="mt-4">
                <label class="{{ $labelClass }} required">Service Location</label>
                <div class="fs-seg-group" role="radiogroup" aria-label="Service Location">
                    <label class="fs-seg">
                        <input type="radio" name="location_source" value="delivery" class="fs-sr fs-loc-src" @checked(old('location_source') === 'delivery')>
                        <span>Delivery Address</span>
                    </label>
                    <label class="fs-seg">
                        <input type="radio" name="location_source" value="other" class="fs-sr fs-loc-src" @checked(old('location_source') === 'other')>
                        <span>Different Address</span>
                    </label>
                </div>
                <p id="fs-loc-hint" class="text-xs text-gray-400 mt-1.5">Confirm where the technician is going before dispatching — never assume the delivery address.</p>
                @error('location_source')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                {{-- Delivery address (read-only) — shown only after "Delivery Address". --}}
                <div id="fs-loc-delivery" class="hidden mt-2 rounded-md border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-800">—</div>
                {{-- Different address (intentional entry) — shown only after "Different Address". --}}
                <div id="fs-loc-other" class="hidden mt-2 grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div class="sm:col-span-4">
                        <label class="{{ $labelClass }} required">Street</label>
                        <input type="text" name="loc_street" id="fs-loc-street" value="{{ old('loc_street') }}" class="{{ $inputClass }}">
                        @error('loc_street')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $labelClass }} required">City</label>
                        <input type="text" name="loc_city" id="fs-loc-city" value="{{ old('loc_city') }}" class="{{ $inputClass }}">
                        @error('loc_city')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $labelClass }} required">State</label>
                        {{-- Shared U.S. state list (App\Models\Locations\State); value = two-letter abbreviation. --}}
                        <select name="loc_state" id="fs-loc-state" class="{{ $inputClass }}">
                            <option value="">Select state</option>
                            @foreach ($states as $state)
                                <option value="{{ $state->abbreviation }}" @selected(old('loc_state') === $state->abbreviation)>{{ $state->name }}</option>
                            @endforeach
                        </select>
                        @error('loc_state')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $labelClass }} required">ZIP</label>
                        <input type="text" name="loc_zip" id="fs-loc-zip" value="{{ old('loc_zip') }}" class="{{ $inputClass }}">
                        @error('loc_zip')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

        </div>

        {{-- ===== 2. Reported Problem — canonical shared complaint intake
             (grouped checklist + Complaint Details + Complaint Evidence), the
             SAME component Standard Service uses. ===== --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
            @include('admin.service_management.problem_templates.partials._complaint_intake', ['prefix' => 'fs'])
        </div>

        {{-- ===== 3. Assigned Personnel — a routing decision made AFTER the
             complaint is understood (which specialty the problem needs). The SAME
             shared assignment component New Service Ticket uses: multiple crew, one
             optional Team Leader. Optional at creation — assignment can also happen
             on the Field Operations Workbench. The mission's single lead technician
             is derived server-side from the marked Team Leader (or the first person
             selected) so the dispatch workflow keeps its one-lead reference. ===== --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
            @include('admin.service_management.partials._personnel_assignment', ['prefix' => 'fs'])
        </div>

        {{-- ===== 4. Dispatch Assessment ===== --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
            <h2 class="text-sm font-semibold text-gray-800 mb-1">4 · Dispatch Assessment</h2>
            <p class="text-xs text-gray-400 mb-4">What the office knows about risk and site conditions right now. "Unknown" is a valid answer.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="{{ $labelClass }} required">Priority</label>
                    <select name="priority" required class="{{ $inputClass }}">
                        @foreach (ServicePriority::cases() as $case)
                            <option value="{{ $case->value }}" @selected(old('priority', 'normal') === $case->value)>{{ $case->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $labelClass }} required">Safety Concern</label>
                    <select name="safety_concern" required class="{{ $inputClass }}">
                        @foreach (FieldSafetyConcern::cases() as $case)
                            <option value="{{ $case->value }}" @selected(old('safety_concern', 'unknown') === $case->value)>{{ $case->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $labelClass }} required">Machine Status</label>
                    <select name="machine_status" required class="{{ $inputClass }}">
                        @foreach (FieldMachineStatus::cases() as $case)
                            <option value="{{ $case->value }}" @selected(old('machine_status', 'unknown') === $case->value)>{{ $case->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $labelClass }} required">Machine Stuck</label>
                    <select name="machine_stuck" required class="{{ $inputClass }}">
                        @foreach (FieldYesNoUnknown::cases() as $case)
                            <option value="{{ $case->value }}" @selected(old('machine_stuck', 'unknown') === $case->value)>{{ $case->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $labelClass }} required">Recovery Risk</label>
                    <select name="recovery_risk" required class="{{ $inputClass }}">
                        @foreach (FieldRecoveryRisk::cases() as $case)
                            <option value="{{ $case->value }}" @selected(old('recovery_risk', 'unknown') === $case->value)>{{ $case->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $labelClass }} required">Site Access</label>
                    <select name="site_access" required class="{{ $inputClass }}">
                        @foreach (FieldSiteAccess::cases() as $case)
                            <option value="{{ $case->value }}" @selected(old('site_access', 'unknown') === $case->value)>{{ $case->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2 lg:col-span-3">
                    <label class="{{ $labelClass }}">Site / Access Notes</label>
                    <textarea name="site_notes" rows="2" class="{{ $inputClass }}"
                        placeholder="Gate codes, soft ground, overhead lines, low clearance, parking…">{{ old('site_notes') }}</textarea>
                </div>
            </div>
        </div>

        {{-- ===== 5. Dispatch Logistics ===== --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
            <h2 class="text-sm font-semibold text-gray-800 mb-1">5 · Dispatch Logistics</h2>
            <p class="text-xs text-gray-400 mb-4">
                Truck, timing, tools, and instructions — optional at creation; can also be set on the Field Operations Workbench.
            </p>
            @php
                // Restore the compound Departure Location selection after a
                // validation round-trip (store:<id> | other | '').
                $oldDep = old('departure_location_type') === 'store'
                    ? 'store:' . old('departure_store_id')
                    : (old('departure_location_type') === 'other' ? 'other' : '');
            @endphp

            {{-- Truck + explicit Departure Location (no default) + Departure Time --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="{{ $labelClass }}">Service Truck</label>
                    <select name="truck_id" class="{{ $inputClass }}">
                        <option value="">— Not assigned yet —</option>
                        @foreach ($trucks as $truck)
                            <option value="{{ $truck->id }}" @selected((int) old('truck_id') === $truck->id)>
                                {{ $truck->truck_name }}{{ $truck->truck_number ? ' #' . $truck->truck_number : '' }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">The truck and departure point are independent — a truck may leave from another branch or a jobsite.</p>
                </div>
                <div>
                    {{-- Departure Location — a REQUIRED explicit decision. No
                         default: the route origin is never assumed from the
                         truck's home store, the service store, or the user. --}}
                    <label class="{{ $labelClass }}">Departure Location</label>
                    <select id="fs-dep-select" class="{{ $inputClass }}">
                        <option value="" @selected($oldDep === '')>— Select departure location —</option>
                        @foreach ($departureStores as $store)
                            <option value="store:{{ $store['id'] }}" @selected($oldDep === 'store:' . $store['id'])>
                                {{ $store['name'] }}{{ $store['address'] ? ' — ' . $store['address'] : ' — (no address on file)' }}
                            </option>
                        @endforeach
                        <option value="other" @selected($oldDep === 'other')>Other</option>
                    </select>
                    {{-- The two fields the form actually posts, driven by the select. --}}
                    <input type="hidden" name="departure_location_type" id="fs-dep-type" value="{{ old('departure_location_type') }}">
                    <input type="hidden" name="departure_store_id" id="fs-dep-store" value="{{ old('departure_store_id') }}">
                    @error('departure_location_type')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    @error('departure_store_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="{{ $labelClass }}">Departure Time</label>
                    <input type="datetime-local" name="estimated_departure_at" id="fs-dep-time"
                        value="{{ old('estimated_departure_at') }}" class="{{ $inputClass }}">
                </div>
            </div>

            {{-- Other Departure Address — revealed only when "Other" is chosen;
                 reuses the shared structured-address pattern + state list. --}}
            <div id="fs-dep-other" class="mt-4 {{ old('departure_location_type') === 'other' ? '' : 'hidden' }}">
                <label class="{{ $labelClass }}">Departure Address</label>
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div class="sm:col-span-4">
                        <label class="{{ $labelClass }} required">Street Address</label>
                        <input type="text" name="departure_street" id="fs-dep-street" value="{{ old('departure_street') }}" class="{{ $inputClass }}">
                        @error('departure_street')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-4">
                        <label class="{{ $labelClass }}">Address Line 2</label>
                        <input type="text" name="departure_line2" id="fs-dep-line2" value="{{ old('departure_line2') }}" class="{{ $inputClass }}" placeholder="Suite, unit, gate…">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $labelClass }} required">City</label>
                        <input type="text" name="departure_city" id="fs-dep-city" value="{{ old('departure_city') }}" class="{{ $inputClass }}">
                        @error('departure_city')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $labelClass }} required">State</label>
                        <select name="departure_state" id="fs-dep-state" class="{{ $inputClass }}">
                            <option value="">Select state</option>
                            @foreach ($states as $state)
                                <option value="{{ $state->abbreviation }}" @selected(old('departure_state') === $state->abbreviation)>{{ $state->name }}</option>
                            @endforeach
                        </select>
                        @error('departure_state')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $labelClass }} required">ZIP Code</label>
                        <input type="text" name="departure_zip" id="fs-dep-zip" value="{{ old('departure_zip') }}" class="{{ $inputClass }}">
                        @error('departure_zip')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            {{-- System-calculated route result — read-only. No enabled arrival
                 input; arrival is departure time + route duration, computed and
                 persisted server-side. --}}
            <div id="fs-route-box" class="mt-4 rounded-md border border-gray-200 bg-gray-50 px-4 py-3 hidden">
                <div class="flex flex-wrap gap-x-8 gap-y-1 text-sm">
                    <div>Travel Time: <span id="fs-travel-time" class="font-medium text-gray-800">—</span></div>
                    <div>Expected Arrival: <span id="fs-expected-arrival" class="font-medium text-gray-800">—</span></div>
                </div>
                <p id="fs-route-msg" class="text-xs text-amber-600 mt-1.5 hidden"></p>
            </div>

            {{-- Tools / parts / instructions --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                <div>
                    <label class="{{ $labelClass }}">Suggested Tools</label>
                    <textarea name="suggested_tools" rows="2" class="{{ $inputClass }}"
                        placeholder="Multimeter, hydraulic gauge set, jump pack…">{{ old('suggested_tools') }}</textarea>
                </div>
                <div>
                    <label class="{{ $labelClass }}">Suggested Parts</label>
                    <textarea name="suggested_parts" rows="2" class="{{ $inputClass }}"
                        placeholder="Battery, fuses, common wear parts…">{{ old('suggested_parts') }}</textarea>
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $labelClass }}">Special Instructions</label>
                    <textarea name="special_instructions" rows="2" class="{{ $inputClass }}"
                        placeholder="Anything the technician must know before rolling…">{{ old('special_instructions') }}</textarea>
                </div>
            </div>
        </div>

        {{-- ===== 6. Initial Operational Expectation ===== --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
            <h2 class="text-sm font-semibold text-gray-800 mb-1">6 · Initial Operational Expectation</h2>
            <p class="text-xs text-gray-400 mb-4">
                An estimate only — the real outcome is decided by the technician's field assessment.
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="{{ $labelClass }} required">Expected Outcome</label>
                    <select name="operational_expectation" required class="{{ $inputClass }}">
                        @foreach (FieldOperationalExpectation::cases() as $case)
                            <option value="{{ $case->value }}" @selected(old('operational_expectation', 'unknown') === $case->value)>{{ $case->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="flex justify-end items-center gap-3">
            <span class="text-xs text-gray-400 mr-auto">The mission continues on the Field Operations Workbench after creation.</span>
            <a href="{{ route('admin.service-management.board') }}"
                class="px-6 py-3 rounded-lg font-medium text-sm border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
                Cancel
            </a>
            <button type="submit"
                class="px-6 py-3 rounded-lg font-medium text-sm bg-blue-600 text-white hover:bg-blue-700 shadow-sm transition">
                Create Field Ticket
            </button>
        </div>
    </form>
    </div>{{-- /centered container --}}

@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ORDERS = @json($orderOptions);

    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c])); }
    function $(id) { return document.getElementById(id); }

    // Currently-selected field equipment = the EFFECTIVE equipment that drives
    // the shared complaint intake (Reported Problem + Complaint Details +
    // Complaint Evidence — the same component Standard Service uses).
    let currentUnit = null;
    const complaintIntake = window.serviceComplaintIntake.init('fs', () => currentUnit);

    // Assigned Personnel — shared component (same engine New Service Ticket uses).
    window.servicePersonnelAssignment.init('fs');

    const orderSelect = $('fs-order');
    const customerBox = $('fs-customer');
    const equipHidden = $('fs-equipment');   // the posted equipment_id
    const serialHidden = $('fs-serial');
    const eqSingle = $('fs-equipment-single');
    const eqMulti  = $('fs-equipment-multi');
    const eqEmpty  = $('fs-equipment-empty');
    const eqDetail = $('fs-equipment-detail');

    function currentOrder() { return ORDERS.find(o => String(o.id) === String(orderSelect.value)); }

    function setEquipment(u) {
        equipHidden.value  = u.id;
        serialHidden.value = u.serial || '';
        eqDetail.textContent = [u.model, u.serial ? 'SN ' + u.serial : ''].filter(Boolean).join(' · ');
        currentUnit = u;
        complaintIntake.refresh();
    }

    // Single equipment → read-only; multiple → a selector; none → prompt.
    function renderEquipment(order, keepId) {
        [eqSingle, eqMulti, eqEmpty].forEach(el => el.classList.add('hidden'));
        eqMulti.innerHTML = ''; eqDetail.textContent = '';
        currentUnit = null; complaintIntake.refresh(); // reset problem scope until a unit is chosen
        const units = order ? (order.equipment || []) : [];

        if (!order) { eqEmpty.textContent = 'Select an order…'; eqEmpty.classList.remove('hidden'); equipHidden.value = ''; serialHidden.value = ''; return; }
        if (units.length === 0) { eqEmpty.textContent = 'No equipment on this order'; eqEmpty.classList.remove('hidden'); equipHidden.value = ''; serialHidden.value = ''; return; }

        if (units.length === 1) {
            eqSingle.textContent = units[0].label;
            eqSingle.classList.remove('hidden');
            setEquipment(units[0]);
            return;
        }

        eqMulti.classList.remove('hidden');
        let matched = false;
        units.forEach(function (u) {
            const label = document.createElement('label');
            label.className = 'flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 cursor-pointer hover:bg-gray-100 transition text-sm';
            label.innerHTML = '<input type="radio" name="fs_equipment_choice" value="' + u.id + '"> <span>' + esc(u.label) + '</span>';
            const radio = label.querySelector('input');
            if (String(u.id) === String(keepId)) { radio.checked = true; setEquipment(u); matched = true; }
            radio.addEventListener('change', function () { setEquipment(u); });
            eqMulti.appendChild(label);
        });
        if (!matched) { equipHidden.value = ''; serialHidden.value = ''; eqDetail.textContent = 'Select the machine on site.'; }
    }

    // ── Contact + location: REQUIRED explicit decisions (no default) ───
    // Neither box shows until the dispatcher makes a choice; the prompt hides
    // once a decision is made. Order-derived values are populated but stay
    // hidden until "Order Contact" / "Delivery Address" is chosen.
    const contactOrder = $('fs-contact-order'), contactOther = $('fs-contact-other'), contactHint = $('fs-contact-hint');
    function syncContactDisplay(order) {
        const c = (order && order.contact) || {};
        $('fs-contact-order-name').textContent  = c.name  || '—';
        $('fs-contact-order-phone').textContent = c.phone || '—';
    }
    function applyContactSource() {
        const src = (document.querySelector('.fs-contact-src:checked') || {}).value || null;
        contactOrder.classList.toggle('hidden', src !== 'order');
        contactOther.classList.toggle('hidden', src !== 'other');
        if (contactHint) contactHint.classList.toggle('hidden', src !== null); // hide the prompt once decided
    }
    function clearContactOther() {
        const n = $('fs-contact-name'), p = $('fs-contact-phone');
        if (n) n.value = ''; if (p) p.value = '';
    }

    const locDelivery = $('fs-loc-delivery'), locOther = $('fs-loc-other'), locHint = $('fs-loc-hint');
    function syncLocationDisplay(order) {
        locDelivery.textContent = (order && order.address && order.address.full) || 'No delivery address on this order';
    }
    function applyLocationSource() {
        const src = (document.querySelector('.fs-loc-src:checked') || {}).value || null;
        locDelivery.classList.toggle('hidden', src !== 'delivery');
        locOther.classList.toggle('hidden', src !== 'other');
        if (locHint) locHint.classList.toggle('hidden', src !== null);
    }
    function clearLocationOther() {
        ['fs-loc-street', 'fs-loc-city', 'fs-loc-state', 'fs-loc-zip'].forEach(function (id) { const el = $(id); if (el) el.value = ''; });
    }

    // On a user-driven change, update visibility and — when switching BACK to the
    // order-derived option — clear the now-unused alternate inputs so stray values
    // can never accidentally persist.
    document.querySelectorAll('.fs-contact-src').forEach(function (r) {
        r.addEventListener('change', function () { applyContactSource(); if (r.value === 'order') clearContactOther(); });
    });
    document.querySelectorAll('.fs-loc-src').forEach(function (r) {
        r.addEventListener('change', function () { applyLocationSource(); if (r.value === 'delivery') clearLocationOther(); });
    });

    // ── Order orchestration ────────────────────────────────────────────
    function onOrder(preserveEquip) {
        const order = currentOrder();
        customerBox.textContent = order ? (order.customer || 'Unknown customer') : 'Select an order…';
        renderEquipment(order, preserveEquip ? equipHidden.value : '');
        syncContactDisplay(order);
        syncLocationDisplay(order);
    }
    orderSelect.addEventListener('change', function () { onOrder(false); });
    onOrder(true); // restore after a validation round-trip
    applyContactSource();
    applyLocationSource();

    // ── Dispatch Logistics: explicit departure location + live arrival ──
    // The departure origin is NEVER defaulted. The visible selector drives two
    // hidden posted fields (type + store id); "Other" reveals the structured
    // address. Expected Arrival is computed server-side (browser → Kabba →
    // Google) and shown read-only; it recalculates whenever the origin, the
    // Other address, the destination, or the departure time changes.
    const ROUTE_PREVIEW_URL = @json(route('admin.field-service.tickets.route-preview'));
    const depSelect   = $('fs-dep-select');
    const depType     = $('fs-dep-type');
    const depStore    = $('fs-dep-store');
    const depOther    = $('fs-dep-other');
    const depTime     = $('fs-dep-time');
    const routeBox    = $('fs-route-box');
    const travelTime  = $('fs-travel-time');
    const arrivalOut  = $('fs-expected-arrival');
    const routeMsg    = $('fs-route-msg');
    const depOtherIds = ['fs-dep-street', 'fs-dep-line2', 'fs-dep-city', 'fs-dep-state', 'fs-dep-zip'];

    function clearDepartureOther() {
        depOtherIds.forEach(function (id) { const el = $(id); if (el) el.value = ''; });
    }

    // The resolved destination mirrors the Service Location decision above:
    // the order's delivery address, or the different address entered there.
    function resolvedDestination() {
        const src = (document.querySelector('.fs-loc-src:checked') || {}).value || null;
        if (src === 'delivery') {
            const o = currentOrder();
            return (o && o.address && o.address.full) || '';
        }
        if (src === 'other') {
            return [
                $('fs-loc-street').value,
                $('fs-loc-city').value,
                ($('fs-loc-state').value + ' ' + $('fs-loc-zip').value).trim(),
            ].filter(Boolean).join(', ');
        }
        return '';
    }

    function csrfToken() {
        const t = document.querySelector('meta[name="csrf-token"]');
        return t ? t.getAttribute('content') : '';
    }

    let routeTimer = null;
    function requestRoutePreview() {
        // Nothing to calculate until a departure location is explicitly chosen.
        if (!depType.value) { routeBox.classList.add('hidden'); return; }

        routeBox.classList.remove('hidden');
        travelTime.textContent = '…';
        arrivalOut.textContent = '…';
        routeMsg.classList.add('hidden');

        clearTimeout(routeTimer);
        routeTimer = setTimeout(function () {
            const body = {
                departure_location_type: depType.value,
                departure_store_id: depStore.value,
                departure_street: ($('fs-dep-street') || {}).value || '',
                departure_line2:  ($('fs-dep-line2') || {}).value || '',
                departure_city:   ($('fs-dep-city') || {}).value || '',
                departure_state:  ($('fs-dep-state') || {}).value || '',
                departure_zip:    ($('fs-dep-zip') || {}).value || '',
                destination_address: resolvedDestination(),
                departure_at: depTime.value || '',
            };
            fetch(ROUTE_PREVIEW_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                body: JSON.stringify(body),
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success) {
                        travelTime.textContent = data.travel_time || '—';
                        arrivalOut.textContent = data.expected_arrival || '—';
                        routeMsg.classList.add('hidden');
                    } else {
                        travelTime.textContent = '—';
                        arrivalOut.textContent = '—';
                        routeMsg.textContent = data.message || 'Expected arrival is unavailable.';
                        routeMsg.classList.remove('hidden');
                    }
                })
                .catch(function () {
                    travelTime.textContent = '—';
                    arrivalOut.textContent = '—';
                    routeMsg.textContent = 'Could not calculate the route — try again.';
                    routeMsg.classList.remove('hidden');
                });
        }, 450);
    }

    depSelect.addEventListener('change', function () {
        const v = depSelect.value;
        if (v.indexOf('store:') === 0) {
            depType.value = 'store';
            depStore.value = v.slice(6);
            depOther.classList.add('hidden');
            clearDepartureOther();
        } else if (v === 'other') {
            depType.value = 'other';
            depStore.value = '';
            depOther.classList.remove('hidden');
        } else {
            depType.value = '';
            depStore.value = '';
            depOther.classList.add('hidden');
            clearDepartureOther();
        }
        requestRoutePreview();
    });

    // Recalculate on any input that changes origin, destination, or timing.
    depTime.addEventListener('change', requestRoutePreview);
    depOtherIds.forEach(function (id) { const el = $(id); if (el) el.addEventListener('input', requestRoutePreview); });
    document.querySelectorAll('.fs-loc-src').forEach(function (r) { r.addEventListener('change', requestRoutePreview); });
    ['fs-loc-street', 'fs-loc-city', 'fs-loc-state', 'fs-loc-zip'].forEach(function (id) { const el = $(id); if (el) el.addEventListener('input', requestRoutePreview); });
    orderSelect.addEventListener('change', requestRoutePreview);

    requestRoutePreview(); // restore the panel after a validation round-trip

    new Choices(orderSelect, {
        searchEnabled: true,
        shouldSort: false,
        itemSelectText: '',
        searchResultLimit: 1000,
        renderChoiceLimit: -1,
        // Order #, customer, and equipment are already in the visible label;
        // reference # rides in customProperties so it's searchable too — matching
        // the Standard Service server search's fields.
        searchFields: ['label', 'value', 'customProperties.reference'],
        searchPlaceholderValue: 'Type an order #, customer, or equipment…',
    });
});
</script>
@endpush
