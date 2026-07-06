{{-- Merge code reference — values fill in when a document is generated --}}
<div class="rounded-md p-4 bg-blue-50 border border-blue-200">
    <div class="flex items-start space-x-3">
        <x-heroicon-o-code-bracket class="w-5 h-5 text-blue-600 mt-0.5" />
        <div class="w-full">
            <h4 class="text-sm font-medium text-blue-800">Available Merge Codes</h4>
            <p class="text-xs mt-1 text-blue-700 mb-2">
                Use these in the text fields — values are filled in when a document is generated.
                Unknown codes are left visible in the document so typos are easy to spot.
            </p>
            <dl class="grid grid-cols-1 lg:grid-cols-2 gap-x-6 gap-y-1">
                @foreach (\App\Services\DocumentGenerator\DocumentMergeCodes::available() as $code => $description)
                    <div class="flex items-baseline gap-2 text-xs">
                        {{-- Brace pairs built by concatenation: a literal "}}" inside an echo ends it early --}}
                        <dt><code class="bg-white border border-blue-200 rounded px-1.5 py-0.5 text-blue-800 whitespace-nowrap">{{ '{'.'{ '.$code.' }'.'}' }}</code></dt>
                        <dd class="text-blue-700">{{ $description }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </div>
</div>
