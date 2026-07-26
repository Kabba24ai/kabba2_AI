@extends('admin.layouts.app')

@section('title', 'Billing Operations')

@section('content')

    @include('flash::message')

    {{-- Task Manager → Billing Operations → Overview.
         The Billing Admin's entry point. Read-only cards derived from the
         shared BillingOperationsSummary (the same canonical datasets the
         Fuel and Damage workspaces use); each links to its workspace. No
         queue, no resolution controls, no customer/CRM data here. --}}
    <div class="max-w-6xl mx-auto px-4 py-6">

        <div class="flex items-start justify-between mb-5 gap-4 flex-wrap">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Billing Operations</h1>
                <p class="text-sm text-gray-500 mt-0.5">What billing work needs attention right now.</p>
            </div>

            {{-- Primary Billing Admin identity / configuration state --}}
            <div class="text-sm">
                @if ($billingAdmin)
                    @if ($viewerIsAdmin)
                        <span class="text-gray-700">Welcome back, <span class="font-semibold text-gray-900">{{ $billingAdmin->full_name }}</span></span>
                    @else
                        <span class="text-gray-500">Primary Billing Admin:</span>
                        <span class="font-semibold text-gray-900">{{ $billingAdmin->full_name }}</span>
                    @endif
                @elseif ($adminIsInactive)
                    <span class="inline-flex items-center gap-1 text-amber-700">
                        <x-heroicon-o-exclamation-triangle class="w-4 h-4" />
                        Primary Billing Admin is inactive —
                        <a href="{{ route('admin.tasks.billing.settings.index') }}" class="font-medium underline">update in Settings</a>
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 text-gray-600">
                        <x-heroicon-o-information-circle class="w-4 h-4" />
                        No Primary Billing Admin configured —
                        <a href="{{ route('admin.tasks.billing.settings.index') }}" class="font-medium underline">configure in Settings</a>
                    </span>
                @endif
            </div>
        </div>

        {{-- Summary cards --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">

            <x-admin.billing.stat-card
                label="Fuel Charges — Open"
                :value="$metrics['fuel_open']"
                :href="route('admin.tasks.billing.fuel-charges.index')"
                accent="orange" />

            <x-admin.billing.stat-card
                label="Damage Charges — Open"
                :value="$metrics['damage_open']"
                :href="route('admin.tasks.billing.damage-charges.index')"
                accent="rose" />

            <x-admin.billing.stat-card
                label="Needs Pricing"
                :value="$metrics['needs_pricing']"
                :href="route('admin.tasks.billing.damage-charges.index', ['needs_pricing' => 1])"
                accent="amber"
                hint="Damage with no collectible amount" />

            <x-admin.billing.stat-card
                label="Resolved Today"
                :value="$metrics['resolved_today']"
                accent="green"
                hint="Fuel + Damage alert completions today" />

            <x-admin.billing.stat-card
                label="Oldest Outstanding"
                :value="$metrics['oldest_outstanding_days'] === null ? '—' : $metrics['oldest_outstanding_days'] . 'd'"
                accent="gray"
                hint="Oldest active Fuel/Damage item" />
        </div>

        {{-- Quick links into the workspaces --}}
        <div class="mt-6 flex flex-wrap items-center gap-3 text-sm">
            <a href="{{ route('admin.tasks.billing.fuel-charges.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                <x-heroicon-o-fire class="w-4 h-4" /> Fuel Charge Resolution
            </a>
            <a href="{{ route('admin.tasks.billing.damage-charges.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                <x-heroicon-o-wrench class="w-4 h-4" /> Damage Charge Resolution
            </a>
            <a href="{{ route('admin.tasks.billing.settings.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                <x-heroicon-o-cog-6-tooth class="w-4 h-4" /> Settings
            </a>
        </div>

        <p class="mt-4 text-xs text-gray-400">
            Resolved Today reflects Fuel and Damage alert completions recorded today; Service Ticket-originated
            charge resolutions are not yet included in this metric.
        </p>
    </div>

@endsection
