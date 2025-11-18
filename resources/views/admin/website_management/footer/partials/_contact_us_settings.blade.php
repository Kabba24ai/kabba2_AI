<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

    {{-- Notice (now half width) --}}
    <div class="mt-4 rounded-md p-4 text-left bg-blue-50 border border-blue-200 text-blue-900">

        <div class="flex items-start space-x-3">
            <div>
                <x-heroicon-o-information-circle class="w-4 h-5 text-blue-600 self-start" />
            </div>
            <div>

                <p class="text-sm text-blue-700">
                    You can manage and update the Contact Us shown on your website from the Contact Us page
                    <a href="{{ route('admin.stores.index') }}"
                        class="font-medium text-blue-800 underline hover:text-blue-900">
                        Click here to go to Contact Us settings →
                    </a>
                </p>
            </div>
        </div>
    </div>

</div>