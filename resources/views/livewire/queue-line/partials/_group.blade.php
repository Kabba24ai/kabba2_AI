@php $groupOrder = $group['order']; @endphp
{{-- w-fit + the section's flex-wrap lets several order groups share a row
     (Phase 4 §6: no dead whitespace on wide wall displays) while the group
     border keeps the order boundary unmistakable. --}}
<div class="w-fit max-w-full rounded-lg border border-gray-200 bg-white p-4">
    {{-- Order-level context: once per group, never repeated on child cards --}}
    <div class="flex flex-wrap items-center gap-3 mb-3">
        <a href="{{ route('admin.order-management.orders.edit', $groupOrder->unique_id) }}"
            class="{{ $ui['groupHeader'] }} font-bold text-sky-700 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500">
            Order #{{ $groupOrder->order_number }}
        </a>
        <span class="{{ $ui['groupHeader'] }} text-gray-800 truncate max-w-md" title="{{ $groupOrder->customer_name }}">
            {{ $groupOrder->customer_name }}
        </span>
        @if ($groupOrder->last_payment_status === null)
            {{-- Presentation-only wording: an order with no payment rows is
                 not "Unknown" and must not be guessed paid/unpaid (§12) --}}
            <span class="px-2 py-0.5 rounded {{ $ui['badge'] }} font-medium bg-gray-100 text-gray-600">No Payment Recorded</span>
        @else
            {!! \App\Helpers\CustomHelper::paymentStatusBadge($groupOrder->last_payment_status) !!}
        @endif
        <span class="ml-auto {{ $ui['badge'] }} text-gray-400 font-medium">
            {{ count($group['items']) }} {{ count($group['items']) === 1 ? 'item' : 'items' }}
        </span>
    </div>

    <div class="flex flex-wrap gap-4">
        @foreach ($group['items'] as $item)
            @php $assignment = \App\Services\QueueLine\QueueLineEligibility::classifyAssignment($item); @endphp
            @if ($assignment === \App\Services\QueueLine\QueueLineEligibility::ASSIGNMENT_UNASSIGNED)
                @include('livewire.queue-line.partials._attention_row', ['item' => $item, 'groupOrder' => $groupOrder, 'ui' => $ui])
            @else
                @include('livewire.queue-line.partials._card', ['item' => $item, 'groupOrder' => $groupOrder, 'assignment' => $assignment, 'ui' => $ui])
            @endif
        @endforeach
    </div>
</div>
