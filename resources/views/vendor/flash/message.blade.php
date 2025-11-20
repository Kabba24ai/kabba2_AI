@foreach (session('flash_notification', collect())->toArray() as $message)
    @if ($message['overlay'])
        {{-- Modal message --}}
        @include('flash::modal', [
            'modalClass' => 'flash-modal',
            'title'      => $message['title'],
            'body'       => $message['message'],
        ])
    @else
        @php
            $level = $message['level'];
            switch ($level) {
                case 'success':
                    $baseClasses      = 'border-green-300 bg-green-300 text-green-800';
                    $btnClasses       = 'bg-green-500 text-white hover:bg-green-600 focus:ring-green-300';
                    $badgeTextClasses = 'text-green-600 dark:text-green-200';
                    break;
                case 'error':
                case 'danger':
                    $baseClasses      = 'border-red-300 bg-red-300 text-red-800';
                    $btnClasses       = 'bg-red-500 text-white hover:bg-red-600 focus:ring-red-300';
                    $badgeTextClasses = 'text-red-600 dark:text-red-200';
                    break;
                case 'warning':
                    $baseClasses      = 'border-yellow-300 bg-yellow-300 text-yellow-800';
                    $btnClasses       = 'bg-yellow-500 text-white hover:bg-yellow-600 focus:ring-yellow-300';
                    $badgeTextClasses = 'text-yellow-600 dark:text-yellow-200';
                    break;
                case 'info':
                    $baseClasses      = 'border-blue-300 bg-blue-300 text-blue-800';
                    $btnClasses       = 'bg-blue-500 text-white hover:bg-blue-600 focus:ring-blue-300';
                    $badgeTextClasses = 'text-blue-600 dark:text-blue-200';
                    break;
                default:
                    $baseClasses      = 'border-gray-300 bg-gray-300 text-gray-800';
                    $btnClasses       = 'bg-gray-500 text-white hover:bg-gray-600 focus:ring-gray-300';
                    $badgeTextClasses = 'text-gray-600 dark:text-gray-200';
                    break;
            }
        @endphp

        <div
            x-data="{ show: true, seconds: 1 }"
            x-init="
              const total = 1, interval = setInterval(() => {
                if (seconds > 0) seconds--;
                if (seconds === 0) clearInterval(interval);
              }, 1000);
              setTimeout(() => show = false, total * 1000);
            "
            x-show="show"
            x-transition:enter="transition-all ease-out duration-300"
            x-transition:enter-start="opacity-0 transform translate-y-2 scale-95"
            x-transition:enter-end="opacity-100 transform translate-y-0 scale-100"
            x-transition:leave="transition-opacity ease-in duration-700 delay-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="w-full mb-4 flex items-center justify-between px-6 py-3 rounded-xl {{ $baseClasses }}"
            role="alert"
        >
            {{-- Message --}}
            <div class="flex-1 ">
                {!! $message['message'] !!}
            </div>

            {{-- Controls / Badge --}}
            <div class="flex items-center space-x-2">
                @if ($message['important'])
                    <button
                        @click="show = false"
                        class="w-6 h-6 flex items-center justify-center rounded shadow transition focus:outline-none focus:ring-2 {{ $btnClasses }}"
                        aria-label="Close alert"
                    >
                        <x-heroicon-s-x-mark class="w-4 h-4" />
                    </button>
                @endif

                {{-- Counter Badge --}}
                <span
                    x-text="seconds + 's'"
                    class="inline-flex items-center justify-center px-2 h-7 min-w-[24px] font-medium rounded-full bg-white dark:bg-gray-800 shadow {{ $badgeTextClasses }}"
                ></span>
            </div>
        </div>
    @endif
@endforeach

{{ session()->forget('flash_notification') }}
