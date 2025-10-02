<div>
    <div id="{{ $id }}">
        {{ html()->form()->attributes([
                'action' => $action,
                'method' => $method,
                'autocomplete' => $autocomplete,
                'data-parsley-validate' => $validate,
            ])->open() }}

        @csrf

        {{-- Top Save Button --}}
        <div class="mb-6 flex justify-end">
            <button type="submit"
                class="flex items-center space-x-2 px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-save w-4 h-4 mr-2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                <span>{{ $saveLabel }}</span>
            </button>
        </div>

        {{-- Slot for inner settings --}}
        <div>
            {{ $slot }}
        </div>

        {{-- Bottom Save Button --}}
        <div class="mt-6 flex justify-end">
            <button type="submit"
                class="flex items-center space-x-2 px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-save w-4 h-4 mr-2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                <span>{{ $saveLabel }}</span>
            </button>
        </div>
        {{ html()->form()->close() }}
    </div>
</div>
