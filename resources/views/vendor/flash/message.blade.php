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
                    $baseClasses = 'border-green-300 bg-green-300 text-green-800 dark:border-green-700 dark:bg-green-900 dark:text-green-200';
                    $btnClasses  = 'bg-green-500 text-white hover:bg-green-600 focus:ring-green-300 dark:focus:ring-green-600';
                    break;

                case 'error':
                case 'danger':
                    $baseClasses = 'border-red-300 bg-red-300 text-red-800 dark:border-red-700 dark:bg-red-900 dark:text-red-200';
                    $btnClasses  = 'bg-red-500 text-white hover:bg-red-600 focus:ring-red-300 dark:focus:ring-red-600';
                    break;

                case 'warning':
                    $baseClasses = 'border-yellow-300 bg-yellow-300 text-yellow-800 dark:border-yellow-700 dark:bg-yellow-900 dark:text-yellow-200';
                    $btnClasses  = 'bg-yellow-500 text-white hover:bg-yellow-600 focus:ring-yellow-300 dark:focus:ring-yellow-600';
                    break;

                case 'info':
                    $baseClasses = 'border-blue-300 bg-blue-300 text-blue-800 dark:border-blue-700 dark:bg-blue-900 dark:text-blue-200';
                    $btnClasses  = 'bg-blue-500 text-white hover:bg-blue-600 focus:ring-blue-300 dark:focus:ring-blue-600';
                    break;

                default:
                    $baseClasses = 'border-gray-300 bg-gray-300 text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200';
                    $btnClasses  = 'bg-gray-500 text-white hover:bg-gray-600 focus:ring-gray-300 dark:focus:ring-gray-600';
                    break;
            }
        @endphp

        <div
            x-data="{ show: true }"
            x-show="show"
            x-transition
            class="relative mb-4 px-4 py-3 rounded-xl {{ $baseClasses }}"
            role="alert"
        >
            {!! $message['message'] !!}

            @if ($message['important'])
                <button
                    @click="show = false"
                    class="absolute top-2 right-2 w-6 h-6 flex items-center justify-center rounded-full shadow transition focus:outline-none focus:ring-2 {{ $btnClasses }}"
                    aria-label="Close alert"
                >
                    <x-heroicon-s-x-mark class="w-4 h-4" />
                </button>
            @endif
        </div>
    @endif
@endforeach

{{ session()->forget('flash_notification') }}
