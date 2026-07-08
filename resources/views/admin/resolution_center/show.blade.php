@extends('admin.layouts.app')

@section('title', 'Resolution Center — Case ' . $case->unique_id)

@section('content')

    @include('flash::message')

    @php
        $recommendedKeys = $case->recommended_resolution ? explode(',', $case->recommended_resolution) : [];
        $isReschedule = in_array(\App\Services\ResolutionCenter\ResolutionPolicy::RECOMMEND_FREE_RESCHEDULE, $recommendedKeys);
        // Phase 3.5: Manual Resolution offers Store Credit under its own
        // 'store_credit' key (distinct from Cancellation/Refund's
        // 'issue_store_credit') — both are recognized here so this section
        // stays scenario-agnostic without changing Cancellation/Refund's
        // existing behavior.
        $isStoreCredit = in_array(\App\Services\ResolutionCenter\ResolutionPolicy::RECOMMEND_ISSUE_STORE_CREDIT, $recommendedKeys)
            || in_array(\App\Services\ResolutionCenter\ManualResolutionScenario::RESOLUTION_STORE_CREDIT, $recommendedKeys);
        $isRefund = in_array(\App\Services\ResolutionCenter\ResolutionPolicy::RECOMMEND_STANDARD_REFUND, $recommendedKeys) || in_array(\App\Services\ResolutionCenter\ResolutionPolicy::RECOMMEND_WAIVE_REFUND_FEE, $recommendedKeys);
        $resolutionLabels = \App\Services\ResolutionCenter\ResolutionPolicy::LABELS + \App\Services\ResolutionCenter\ManualResolutionScenario::RESOLUTION_LABELS;
    @endphp

    <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-semibold text-gray-900">Resolution Center — Case {{ $case->unique_id }}</h2>
        <a href="{{ route('admin.resolution-center.index') }}" class="text-sm text-blue-600 hover:underline">Back to History</a>
    </div>

    {{-- Case Summary --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <div class="bg-gray-50 rounded-lg border border-gray-200 p-3">
            <div class="text-[11px] uppercase tracking-wide text-gray-400">Customer</div>
            <div class="font-semibold text-gray-900">{{ optional($case->customer)->first_name }} {{ optional($case->customer)->last_name }}</div>
        </div>
        <div class="bg-gray-50 rounded-lg border border-gray-200 p-3">
            <div class="text-[11px] uppercase tracking-wide text-gray-400">Order</div>
            <div class="font-semibold text-gray-900">
                <a class="text-blue-600 hover:underline" href="{{ route('admin.order-management.orders.edit', optional($case->order)->unique_id) }}">
                    #{{ optional($case->order)->order_number }}
                </a>
            </div>
        </div>
        <div class="bg-gray-50 rounded-lg border border-gray-200 p-3">
            <div class="text-[11px] uppercase tracking-wide text-gray-400">Current Balance</div>
            <div class="font-semibold text-gray-900">{{ \App\Helpers\CustomHelper::formatCurrency($currentBalance) }}</div>
        </div>
        <div class="bg-gray-50 rounded-lg border border-gray-200 p-3">
            <div class="text-[11px] uppercase tracking-wide text-gray-400">Store Credit</div>
            <div class="font-semibold text-gray-900">{{ \App\Helpers\CustomHelper::formatCurrency($currentStoreCredit) }}</div>
        </div>
    </div>

    {{-- Operations Center fields — Phase 3.6 --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <div class="bg-gray-50 rounded-lg border border-gray-200 p-3">
            <div class="text-[11px] uppercase tracking-wide text-gray-400">Status</div>
            <div class="font-semibold text-gray-900 capitalize">{{ str_replace('_', ' ', $case->status) }}{{ $case->status === 'waiting' && $case->waiting_on ? ' — on '.$case->waiting_on : '' }}</div>
        </div>
        <div class="bg-gray-50 rounded-lg border border-gray-200 p-3">
            <div class="text-[11px] uppercase tracking-wide text-gray-400">Priority</div>
            <div class="font-semibold text-gray-900 capitalize">{{ $case->priority }}</div>
        </div>
        <div class="bg-gray-50 rounded-lg border border-gray-200 p-3">
            <div class="text-[11px] uppercase tracking-wide text-gray-400">Assigned To</div>
            <div class="font-semibold text-gray-900">{{ optional($case->assignedTo)->full_name ?? 'Unassigned' }}</div>
        </div>
        <div class="bg-gray-50 rounded-lg border border-gray-200 p-3">
            <div class="text-[11px] uppercase tracking-wide text-gray-400">Store</div>
            <div class="font-semibold text-gray-900">{{ optional($case->store)->store_name ?? '—' }}</div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm mb-6">
        <div class="text-[11px] uppercase tracking-wide text-gray-400 mb-1">Issue</div>
        <p class="text-gray-800">{{ $case->issue }}</p>
        <div class="mt-3 flex items-center gap-2">
            <span class="text-[11px] uppercase tracking-wide text-gray-400">Customer Status:</span>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs {{ $customerStatus['badge']['bg'] ?? 'bg-gray-100' }} {{ $customerStatus['badge']['text'] ?? 'text-gray-700' }}">
                {{ $customerStatus['badge']['label'] ?? $customerStatus['status'] ?? 'Unknown' }}
            </span>
        </div>
    </div>

    @can('resolution_center.use')

    @if($case->scenario_key === \App\Services\ResolutionCenter\ManualResolutionScenario::KEY)

        {{-- Manual Resolution — Phase 3.5. Single-step: both answers are
             known the moment the employee submits, unlike Cancellation/
             Refund's sequential wizard below. --}}
        @if(is_null($case->recommended_resolution))
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm mb-6">
            <h3 class="font-semibold text-lg mb-3">Categorize &amp; Resolve</h3>
            <p class="text-sm text-gray-500 mb-3">Choose the resolution directly — this scenario does not compute a recommendation for you.</p>
            {{ html()->form('PUT', route('admin.resolution-center.manual-answer', $case->unique_id))->open() }}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 required">Issue Category</label>
                    {!! html()->select('issue_category', \App\Services\ResolutionCenter\ManualResolutionScenario::CATEGORY_LABELS)
                            ->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700')
                            ->value($case->issue_category) !!}
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 required">Resolution</label>
                    {!! html()->select('selected_resolution', \App\Services\ResolutionCenter\ManualResolutionScenario::RESOLUTION_LABELS)
                            ->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700') !!}
                </div>
            </div>
            <button type="submit" class="px-5 py-2 rounded-md bg-teal-600 text-white font-medium hover:bg-teal-700">Record Resolution</button>
            {{ html()->form()->close() }}
        </div>
        @endif

    @else

    {{-- Cancellation / Refund wizard — unchanged since Phase 3.3. --}}
    {{-- Step 1 --}}
    @if(is_null($case->can_reschedule))
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm mb-6">
            <h3 class="font-semibold text-lg mb-3">Step 1 — Can the rental be rescheduled?</h3>
            {{ html()->form('PUT', route('admin.resolution-center.answer', $case->unique_id))->open() }}
            <div class="flex gap-3">
                <button type="submit" name="can_reschedule" value="1" class="px-5 py-2 rounded-md bg-teal-600 text-white font-medium hover:bg-teal-700">Yes</button>
                <button type="submit" name="can_reschedule" value="0" class="px-5 py-2 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-100">No</button>
            </div>
            {{ html()->form()->close() }}
        </div>

    {{-- Step 2 --}}
    @elseif($case->can_reschedule === false && is_null($case->credit_would_satisfy))
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm mb-6">
            <h3 class="font-semibold text-lg mb-3">Step 2 — Would Financial Store Credit satisfy the customer?</h3>
            {{ html()->form('PUT', route('admin.resolution-center.answer', $case->unique_id))->open() }}
            <input type="hidden" name="can_reschedule" value="0" />
            <div class="flex gap-3">
                <button type="submit" name="credit_would_satisfy" value="1" class="px-5 py-2 rounded-md bg-teal-600 text-white font-medium hover:bg-teal-700">Yes</button>
                <button type="submit" name="credit_would_satisfy" value="0" class="px-5 py-2 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-100">No</button>
            </div>
            {{ html()->form()->close() }}
        </div>

    {{-- Step 3 --}}
    @elseif($case->can_reschedule === false && $case->credit_would_satisfy === false && is_null($case->recommended_resolution))
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm mb-6">
            <h3 class="font-semibold text-lg mb-3">Step 3 — Refund Required. Payment Method?</h3>
            {{ html()->form('PUT', route('admin.resolution-center.answer', $case->unique_id))->open() }}
            <input type="hidden" name="can_reschedule" value="0" />
            <input type="hidden" name="credit_would_satisfy" value="0" />
            <div class="mb-4">
                {!! html()->select('payment_method', collect($paymentMethodOptions)->mapWithKeys(fn($m) => [$m->value => $m->label()]))
                        ->id('paymentMethodSelect')
                        ->class('w-full max-w-xs border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700')
                        ->value($case->payment_method_snapshot) !!}
            </div>
            <button type="submit" class="px-5 py-2 rounded-md bg-teal-600 text-white font-medium hover:bg-teal-700">Continue</button>
            {{ html()->form()->close() }}
        </div>
    @endif

    @endif

    @endcan

    {{-- Recommendation --}}
    @if($case->recommended_resolution)
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
            <h3 class="font-semibold text-lg mb-2">Recommended Resolution</h3>
            <div class="flex gap-2 mb-2">
                @foreach($recommendedKeys as $key)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-600 text-white">
                        {{ $resolutionLabels[$key] ?? $key }}
                    </span>
                @endforeach
            </div>
            <p class="text-sm text-gray-700"><strong>Recommended Next Step:</strong> {{ $case->recommended_next_step }}</p>
        </div>

        @can('customer_credit.grant')
        @if($isStoreCredit && $case->outcome !== 'completed')
            <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm mb-6">
                <h3 class="font-semibold text-lg mb-3">Approve &amp; Issue Store Credit</h3>
                <p class="text-sm text-gray-500 mb-3">Manual action — nothing is issued until you submit this form.</p>
                {{ html()->form('POST', route('admin.resolution-center.issue-credit', $case->unique_id))->open() }}
                <div class="flex gap-3 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1 required">Amount</label>
                        <div class="flex items-center border border-gray-300 rounded-md px-3 py-2">
                            <span class="text-gray-500 text-sm mr-1">$</span>
                            <input type="number" step="0.01" min="0.01" name="amount" class="text-sm focus:outline-none" required />
                        </div>
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reason (Optional)</label>
                        {!! html()->text('reason', null)->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700')->placeholder('Defaults to "Resolution Center — Order #' . optional($case->order)->order_number . '"') !!}
                    </div>
                    <button type="submit" class="px-5 py-2 rounded-md bg-teal-600 text-white font-medium hover:bg-teal-700">Approve &amp; Issue</button>
                </div>
                {{ html()->form()->close() }}
            </div>
        @endif
        @endcan

        @if($isReschedule || $isRefund)
            <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm mb-6">
                <p class="text-sm text-gray-600">
                    This action is completed manually on the
                    <a class="text-blue-600 hover:underline" href="{{ route('admin.order-management.orders.edit', optional($case->order)->unique_id) }}">Order Edit screen</a>
                    ({{ $isReschedule ? 'Reschedule control' : 'Refund action' }}). Once completed there, record the outcome below.
                </p>
            </div>
        @endif

        @can('resolution_center.use')
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm mb-6">
            <h3 class="font-semibold text-lg mb-3">Employee Decision</h3>
            {{ html()->form('POST', route('admin.resolution-center.decision', $case->unique_id))->open() }}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 required">Decision</label>
                    {!! html()->select('employee_decision', [
                            \App\Services\ResolutionCenterService::DECISION_FOLLOWED => 'Followed Recommendation',
                            \App\Services\ResolutionCenterService::DECISION_OVERRIDDEN => 'Overridden',
                        ])->id('employeeDecisionSelect')->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700')->value($case->employee_decision) !!}
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Outcome</label>
                    {!! html()->select('outcome', [
                            \App\Services\ResolutionCenterService::OUTCOME_PENDING => 'Pending',
                            \App\Services\ResolutionCenterService::OUTCOME_COMPLETED => 'Completed',
                            \App\Services\ResolutionCenterService::OUTCOME_CANCELLED => 'Cancelled',
                        ])->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700')->value($case->outcome) !!}
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Decision Detail (Optional)</label>
                    {!! html()->textarea('employee_decision_detail', $case->employee_decision_detail)->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700')->rows(2)->placeholder('If overridden, what was chosen instead and why?') !!}
                </div>
                @can('resolution_center.override')
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Manager Override Reason (Optional)</label>
                    {!! html()->textarea('manager_override_reason', $case->manager_override_reason)->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700')->rows(2) !!}
                </div>
                @endcan
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Internal Notes (Optional)</label>
                    {!! html()->textarea('notes', $case->notes)->class('w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700')->rows(2) !!}
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="px-6 py-2 rounded-md bg-blue-600 text-white font-medium hover:bg-blue-700 shadow-sm">Save Decision</button>
            </div>
            {{ html()->form()->close() }}
        </div>
        @endcan
    @endif

    {{-- Audit Trail — Phase 3.6 --}}
    @can('resolution_center.view_audit_history')
    <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm mb-6">
        <h3 class="font-semibold text-lg mb-3">Audit Trail</h3>
        @if($case->activityLogs->isEmpty())
            <p class="text-sm text-gray-500">No activity recorded yet.</p>
        @else
            <ul class="divide-y divide-gray-100">
                @foreach($case->activityLogs as $log)
                    <li class="py-2 text-sm text-gray-700 flex items-start justify-between gap-4">
                        <div>
                            <span class="font-medium text-gray-900">{{ str_replace('_', ' ', ucfirst($log->action)) }}</span>
                            @if($log->field)
                                <span class="text-gray-500">— {{ $log->field }}: {{ $log->old_value ?? '—' }} → {{ $log->new_value ?? '—' }}</span>
                            @endif
                        </div>
                        <div class="text-xs text-gray-400 whitespace-nowrap">
                            {{ optional($log->user)->full_name ?? 'System' }} · {{ $log->created_at->format('M j, Y g:i A') }}
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
    @endcan

@endsection

