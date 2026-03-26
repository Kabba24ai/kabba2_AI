@extends('admin.layouts.app')

@section('title', 'kabba.ai Customer — ' . trim(($submission->first_name ?? '') . ' ' . ($submission->last_name ?? '')))

@push('css')
@endpush

@section('content')
    @include('flash::message')

    {{-- Header --}}
    <div class="bg-gray-50 px-4 py-4 border-b border-gray-200 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                <a href="{{ route('admin.crm.kabba-ai-customers.index') }}"
                   class="flex items-center text-gray-600 hover:text-gray-800 transition">
                    <x-heroicon-o-arrow-left class="w-5 h-5 mr-1" />
                    <span class="text-sm font-medium">Back to kabba.ai Customers</span>
                </a>

                <div class="hidden sm:block h-6 border-l border-gray-300"></div>

                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        {{ trim(($submission->first_name ?? '') . ' ' . ($submission->last_name ?? '')) ?: 'Unknown' }}
                    </h1>
                    <p class="text-sm text-gray-500">{{ $submission->unique_id }}</p>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.crm.kabba-ai-customers.edit', $submission->unique_id) }}"
                   class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold bg-amber-500 text-white hover:bg-amber-600 transition">
                    <x-heroicon-o-pencil-square class="w-4 h-4 mr-1" />
                    Edit Demo Fields
                </a>
                @php
                    $statusClasses = match(strtolower($submission->status ?? '')) {
                        'active', 'completed', 'approved' => 'bg-green-100 text-green-700 border-green-200',
                        'pending' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                        'cancelled', 'failed', 'rejected' => 'bg-red-100 text-red-700 border-red-200',
                        default => 'bg-gray-100 text-gray-700 border-gray-200',
                    };
                @endphp
                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold border {{ $statusClasses }}">
                    {{ ucfirst($submission->status ?? 'Unknown') }}
                </span>
            </div>
        </div>
    </div>

    {{-- Summary Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex flex-col gap-1">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Amount</p>
            <p class="text-2xl font-bold text-gray-900">${{ number_format((float) $submission->amount, 2) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex flex-col gap-1">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Status</p>
            <p class="text-lg font-semibold text-gray-900">{{ ucfirst($submission->status ?? '-') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex flex-col gap-1">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Setup Status</p>
            <p class="text-lg font-semibold text-gray-900">{{ ucwords(str_replace('_', ' ', $submission->setup_status ?? 'pending')) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex flex-col gap-1">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Submitted</p>
            <p class="text-sm font-semibold text-gray-900">
                {{ optional($submission->created_at)->format(config('app.date.date_format') . ' h:i A') ?: '-' }}
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left Column --}}
        <div class="lg:col-span-2 flex flex-col gap-6">

            {{-- Contact Information --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h2 class="text-base font-semibold text-gray-900 flex items-center gap-2 mb-5">
                    <x-heroicon-o-user class="w-5 h-5 text-gray-600" />
                    Contact Information
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 text-sm">
                    <div>
                        <p class="text-xs text-gray-500 font-medium mb-0.5">First Name</p>
                        <p class="text-gray-900">{{ $submission->first_name ?: '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 font-medium mb-0.5">Last Name</p>
                        <p class="text-gray-900">{{ $submission->last_name ?: '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 font-medium mb-0.5">Email Address</p>
                        <a href="mailto:{{ $submission->email }}" class="text-blue-600 hover:underline break-all">
                            {{ $submission->email ?: '-' }}
                        </a>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 font-medium mb-0.5">Phone Number</p>
                        <p class="text-gray-900">
                            {{ \App\Helpers\CustomHelper::formatPhone($submission->phone_number) ?: '-' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 font-medium mb-0.5">Business Name</p>
                        <p class="text-gray-900">{{ $submission->business_name ?: '-' }}</p>
                    </div>
                </div>
            </div>

            {{-- Address --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h2 class="text-base font-semibold text-gray-900 flex items-center gap-2 mb-5">
                    <x-heroicon-o-map-pin class="w-5 h-5 text-gray-600" />
                    Address
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 text-sm">
                    <div class="sm:col-span-2">
                        <p class="text-xs text-gray-500 font-medium mb-0.5">Street Address</p>
                        <p class="text-gray-900">{{ $submission->street_address ?: '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 font-medium mb-0.5">City</p>
                        <p class="text-gray-900">{{ $submission->city ?: '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 font-medium mb-0.5">State</p>
                        <p class="text-gray-900">{{ $submission->state ?: '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 font-medium mb-0.5">Zip Code</p>
                        <p class="text-gray-900">{{ $submission->zip_code ?: '-' }}</p>
                    </div>
                </div>
            </div>

            {{-- Payment Information --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h2 class="text-base font-semibold text-gray-900 flex items-center gap-2 mb-5">
                    <x-heroicon-o-credit-card class="w-5 h-5 text-gray-600" />
                    Payment Information
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 text-sm">
                    <div>
                        <p class="text-xs text-gray-500 font-medium mb-0.5">Card Holder Name</p>
                        <p class="text-gray-900">{{ $submission->card_name ?: '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 font-medium mb-0.5">Card Brand</p>
                        <p class="text-gray-900">{{ $submission->card_brand ? ucfirst($submission->card_brand) : '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 font-medium mb-0.5">Card Last Four</p>
                        <p class="text-gray-900">
                            @if($submission->card_last_four)
                                •••• {{ $submission->card_last_four }}
                            @else
                                -
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 font-medium mb-0.5">Card Expiry</p>
                        <p class="text-gray-900">{{ $submission->card_expiry ?: '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 font-medium mb-0.5">Customer Profile ID</p>
                        <p class="text-gray-900 font-mono text-xs">{{ $submission->customer_profile_id ?: '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 font-medium mb-0.5">Payment Profile ID</p>
                        <p class="text-gray-900 font-mono text-xs">{{ $submission->payment_profile_id ?: '-' }}</p>
                    </div>
                </div>
            </div>

            {{-- Demo Notes --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h2 class="text-base font-semibold text-gray-900 flex items-center gap-2 mb-5">
                    <x-heroicon-o-clipboard-document-check class="w-5 h-5 text-gray-600" />
                    Demo Details
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 text-sm">
                    <div>
                        <p class="text-xs text-gray-500 font-medium mb-0.5">Demo Status</p>
                        @php
                            $ss = $submission->setup_status ?? 'pending';
                            $ssClasses = match($ss) {
                                'completed' => 'bg-green-100 text-green-700',
                                'in_progress' => 'bg-blue-100 text-blue-700',
                                default => 'bg-yellow-100 text-yellow-700',
                            };
                        @endphp
                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $ssClasses }}">
                            {{ ucwords(str_replace('_', ' ', $ss)) }}
                        </span>
                    </div>
                    <div class="sm:col-span-2">
                        <p class="text-xs text-gray-500 font-medium mb-0.5">Comment</p>
                        <p class="text-gray-900 whitespace-pre-wrap">{{ $submission->comment ?: '-' }}</p>
                    </div>
                </div>
            </div>

            {{-- Response Message --}}
            @if($submission->response_message)
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h2 class="text-base font-semibold text-gray-900 flex items-center gap-2 mb-4">
                    <x-heroicon-o-chat-bubble-left-ellipsis class="w-5 h-5 text-gray-600" />
                    Response Message
                </h2>
                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $submission->response_message }}</p>
            </div>
            @endif

        </div>

        {{-- Right Column --}}
        <div class="flex flex-col gap-6">

            {{-- Schedule --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h2 class="text-base font-semibold text-gray-900 flex items-center gap-2 mb-5">
                    <x-heroicon-o-calendar-days class="w-5 h-5 text-gray-600" />
                    Schedule
                </h2>

                @if($submission->schedule_datetime)
                    @php
                        $schedule = \Carbon\Carbon::parse($submission->schedule_datetime);
                        $isPast = $schedule->isPast();
                        $isToday = $schedule->isToday();
                        $isFuture = $schedule->isFuture();
                    @endphp
                    <div class="rounded-lg border {{ $isPast ? 'border-gray-200 bg-gray-50' : ($isToday ? 'border-blue-200 bg-blue-50' : 'border-green-200 bg-green-50') }} p-4">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 mt-0.5">
                                @if($isToday)
                                    <x-heroicon-o-clock class="w-5 h-5 text-blue-500" />
                                @elseif($isFuture)
                                    <x-heroicon-o-calendar class="w-5 h-5 text-green-500" />
                                @else
                                    <x-heroicon-o-check-circle class="w-5 h-5 text-gray-400" />
                                @endif
                            </div>
                            <div>
                                <p class="text-sm font-semibold {{ $isPast ? 'text-gray-700' : ($isToday ? 'text-blue-700' : 'text-green-700') }}">
                                    {{ $schedule->format('l, F j, Y') }}
                                </p>
                                <p class="text-sm {{ $isPast ? 'text-gray-500' : ($isToday ? 'text-blue-600' : 'text-green-600') }} mt-0.5">
                                    {{ $schedule->format('g:i A') }}
                                </p>
                                <p class="text-xs mt-2 {{ $isPast ? 'text-gray-400' : ($isToday ? 'text-blue-500' : 'text-green-500') }}">
                                    @if($isToday)
                                        Today — {{ $schedule->diffForHumans() }}
                                    @elseif($isFuture)
                                        Upcoming — {{ $schedule->diffForHumans() }}
                                    @else
                                        Gone — {{ $schedule->diffForHumans() }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-8 text-center">
                        <x-heroicon-o-calendar class="w-10 h-10 text-gray-300 mb-2" />
                        <p class="text-sm text-gray-400">No schedule set</p>
                    </div>
                @endif
            </div>

            {{-- Meta Information --}}
            @if(!empty($submission->meta))
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h2 class="text-base font-semibold text-gray-900 flex items-center gap-2 mb-5">
                    <x-heroicon-o-information-circle class="w-5 h-5 text-gray-600" />
                    Additional Info
                </h2>
                <dl class="space-y-3 text-sm">
                    @foreach($submission->meta as $key => $value)
                        @if(!is_array($value) && !is_null($value) && $value !== '')
                        <div>
                            <dt class="text-xs text-gray-500 font-medium mb-0.5">{{ ucwords(str_replace(['_', '-'], ' ', $key)) }}</dt>
                            <dd class="text-gray-900 break-all">{{ $value }}</dd>
                        </div>
                        @endif
                    @endforeach
                </dl>
            </div>
            @endif

            {{-- Record Info --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h2 class="text-base font-semibold text-gray-900 flex items-center gap-2 mb-5">
                    <x-heroicon-o-clock class="w-5 h-5 text-gray-600" />
                    Record Info
                </h2>
                <div class="space-y-3 text-sm">
                    <div>
                        <p class="text-xs text-gray-500 font-medium mb-0.5">Submitted At</p>
                        <p class="text-gray-900">
                            {{ optional($submission->created_at)->format(config('app.date.date_format') . ' h:i A') ?: '-' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 font-medium mb-0.5">Last Updated</p>
                        <p class="text-gray-900">
                            {{ optional($submission->updated_at)->format(config('app.date.date_format') . ' h:i A') ?: '-' }}
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('js')
@endpush
