<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

    {{-- Notice for Terms & Policy (half width) --}}
    <div class="mt-4 rounded-md p-4 text-left bg-blue-50 border border-blue-200 text-blue-900">
        <div class="flex items-start space-x-3">
            <x-heroicon-o-information-circle class="w-8 h-8 text-blue-600" />
            <div>
                <h4 class="text-sm font-medium text-blue-800">Info</h4>
                <p class="text-sm mt-1 text-blue-700">
                    If you want to update the Terms & Policy content shown on your website,
                    you can manage them from the Terms & Policy settings page.
                    <a href="{{route('admin.terms-and-conditions.global')}}"
                       class="font-medium text-blue-800 underline hover:text-blue-900">
                        Click here to go to Terms & Policy settings →
                    </a>
                </p>
            </div>
        </div>
    </div>

</div>
