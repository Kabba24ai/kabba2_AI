@foreach (session('flash_notification', collect())->toArray() as $message)
    @if ($message['overlay'])
        {{-- Modal message --}}
        @include('flash::modal', [
            'modalClass' => 'flash-modal',
            'title'      => $message['title'],
            'body'       => $message['message']
        ])
    @else
        @php
            $level = $message['level'];
            $color = match($level) {
                'success' => 'green',
                'error', 'danger' => 'red',
                'warning' => 'yellow',
                'info' => 'blue',
                default => 'gray',
            };
        @endphp

        <div
            x-data="{ show: true }"
            x-show="show"
            x-transition
            class="relative mb-4 px-4 py-3 rounded-xl border border-{{$color}}-300 bg-{{$color}}-300 text-{{$color}}-800 dark:bg-{{$color}}-900 dark:text-{{$color}}-200 dark:border-{{$color}}-700"
            role="alert"
        >
            {!! $message['message'] !!}

            @if ($message['important'])
                <button @click="show = false" class="absolute top-2 right-2 text-{{$color}}-800 dark:text-{{$color}}-200">
                    <x-heroicon-s-x-mark class="w-4 h-4" />
                </button>
            @endif
        </div>
    @endif
@endforeach

{{ session()->forget('flash_notification') }}
