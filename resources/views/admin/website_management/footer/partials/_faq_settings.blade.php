<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

    {{-- Notice for FAQ (half width) --}}
    <div class="mt-4 rounded-md p-4 text-left bg-blue-50 border border-blue-200 text-blue-900">
        <div class="flex items-start space-x-3">
            <x-heroicon-o-information-circle class="w-8 h-8 text-blue-600" />
            <div>
                <h4 class="text-sm font-medium text-blue-800">Info</h4>
                <p class="text-sm mt-1 text-blue-700">
                    If you want to update the FAQs displayed on your website,
                    you can manage them from the FAQ settings page.
                    <a href="{{ route('admin.website-management.faq-page.index') }}"
                       class="font-medium text-blue-800 underline hover:text-blue-900">
                        Click here to go to FAQ settings →
                    </a>
                </p>
            </div>
        </div>
    </div>

</div>
