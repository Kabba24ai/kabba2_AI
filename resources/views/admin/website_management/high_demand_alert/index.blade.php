@extends('admin.layouts.app')

@section('title', 'High Demand Alert')

@section('content')

    {{-- Same centered container as the Website Builder pages --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    @include('flash::message')
    @include('admin.partials.formErrors')

    {{-- Page Header --}}
    <div class="flex items-center space-x-3 mb-6">
        <x-heroicon-o-bell-alert class="h-8 w-8 text-red-600" />
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">High Demand Alert</h1>
            <p class="text-sm text-gray-600">The popup shown before adding a high-demand product to the cart</p>
        </div>
    </div>

    {{-- Info: where the per-product toggle lives --}}
    <div class="flex items-start gap-2 rounded-md border border-blue-200 bg-blue-50 px-3 py-2 mb-6">
        <x-heroicon-o-information-circle class="h-5 w-5 text-blue-500 shrink-0 mt-0.5" />
        <p class="text-sm text-blue-800">
            This popup only appears on products with the <span class="font-semibold">High Demand Alert</span> checkbox
            enabled (Products → Edit Product → Additional Rental Prices). The design and wording below are shared by
            every high-demand product.
        </p>
    </div>

    <form method="POST" action="{{ route('admin.website-management.high-demand-alert.update') }}"
          enctype="multipart/form-data" data-parsley-validate
          x-data="hdAlertEditor()">
        @csrf

        {{-- ── Card 1: settings ─────────────────────────────────────────── --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center space-x-2">
                    <x-heroicon-o-pencil-square class="h-5 w-5 text-blue-600" />
                    <h3 class="text-lg font-bold text-gray-900">High Demand Product Alert</h3>
                </div>
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-md text-sm font-medium shadow-sm">
                    Save Alert Settings
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                {{-- Alert Image --}}
                <div class="md:col-span-2 border border-gray-200 rounded-lg p-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Alert Image</label>
                    <p class="text-xs text-gray-500 mb-3">
                        Recommended: portrait or square, about 500 × 650 px, under 2 MB. The image keeps its
                        aspect ratio and is capped at the popup's height — it is never stretched.
                    </p>
                    <div class="flex items-center gap-5 flex-wrap">
                        <img :src="imageSrc" alt="Alert image preview"
                             class="h-24 w-24 object-contain border rounded-md bg-gray-50 p-1">
                        <div class="flex flex-col gap-2">
                            <button type="button"
                                    @click="window.MediaPicker.open(m => { mediaId = m.id; imageSrc = m.url; removeImage = false; })"
                                    class="text-sm text-blue-600 border border-blue-200 hover:bg-blue-50 rounded-md px-3 py-1.5">
                                Choose from Media Library
                            </button>
                            <label class="inline-flex items-center gap-2 px-3 py-1.5 border border-gray-300 rounded-md cursor-pointer bg-white hover:bg-gray-50 text-sm text-gray-700">
                                Upload New
                                <input type="file" name="high_demand_alert_image"
                                       accept="image/jpeg,image/png,image/webp,image/gif" class="sr-only"
                                       @change="fileChosen($event)">
                            </label>
                            <button type="button" x-show="!usingDefault" x-cloak
                                    @click="mediaId = ''; removeImage = true; imageSrc = defaultImage; usingDefault = true"
                                    class="text-sm text-red-500 hover:underline text-left">
                                Restore Default Image
                            </button>
                            <p class="text-xs text-gray-400" x-show="usingDefault">Using the built-in default image.</p>
                        </div>
                    </div>
                    <input type="hidden" name="high_demand_alert_image_media_id" :value="mediaId">
                    <input type="hidden" name="remove_image" :value="removeImage ? 1 : 0">
                    @error('high_demand_alert_image') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    @error('high_demand_alert_image_media_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Heading --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Alert Heading</label>
                    {!! html()->text('high_demand_alert_title', old('high_demand_alert_title', $settings['high_demand_alert_title'] ?? ''))
                        ->class('w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:outline-none')
                        ->attributes(['placeholder' => 'High Demand Alert!', 'maxlength' => 160, 'x-model' => 'title']) !!}
                    <p class="mt-1 text-xs text-gray-500">Blank = default: "High Demand Alert!"</p>
                    @error('high_demand_alert_title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Phone --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                    {!! html()->text('high_demand_alert_phone', old('high_demand_alert_phone', $settings['high_demand_alert_phone'] ?? ''))
                        ->class('masked-phone w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:outline-none')
                        ->attributes([
                            'maxlength' => 14,
                            'data-parsley-pattern' => '^\(\d{3}\)\s\d{3}-\d{4}$',
                            'data-parsley-error-message' => 'Please enter phone number in format (xxx) xxx-xxxx',
                            'placeholder' => '(xxx) xxx-xxxx',
                            'x-model' => 'phone',
                        ]) !!}
                    <p class="mt-1 text-xs text-gray-500">
                        This is the phone number displayed in the High Demand Alert popup — this screen is the
                        only place it is edited. Blank = compatibility fallback to the Company Phone Number.
                    </p>
                    @error('high_demand_alert_phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Message --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Alert Message</label>
                    {!! html()->textarea('high_demand_alert_message', old('high_demand_alert_message', $settings['high_demand_alert_message'] ?? ''))
                        ->class('w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:outline-none')
                        ->rows(4)
                        ->attributes(['placeholder' => \App\Services\Website\HighDemandAlertService::DEFAULT_MESSAGE ? strip_tags(\App\Services\Website\HighDemandAlertService::DEFAULT_MESSAGE) : '', 'maxlength' => 2000, 'x-model' => 'message']) !!}
                    <p class="mt-1 text-xs text-gray-500">
                        Line breaks are kept. Basic emphasis tags like &lt;strong&gt; and &lt;em&gt; are allowed;
                        anything unsafe is stripped automatically. Blank = the default message.
                    </p>
                    @error('high_demand_alert_message') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Button text --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Continue Button Text</label>
                    {!! html()->text('high_demand_alert_button_text', old('high_demand_alert_button_text', $settings['high_demand_alert_button_text'] ?? ''))
                        ->class('w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:ring-2 focus:outline-none')
                        ->attributes(['placeholder' => 'Continue Reservation', 'maxlength' => 60, 'x-model' => 'buttonText']) !!}
                    <p class="mt-1 text-xs text-gray-500">Blank = default: "Continue Reservation"</p>
                    @error('high_demand_alert_button_text') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

            </div>
        </div>

        {{-- ── Card 2: live preview ─────────────────────────────────────── --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center space-x-2 mb-1">
                <x-heroicon-o-eye class="h-5 w-5 text-blue-600" />
                <h3 class="text-lg font-bold text-gray-900">Live Preview</h3>
            </div>
            <p class="text-sm text-gray-500 mb-4">Exactly how the popup appears on the public product page. Updates as you type.</p>

            <div class="bg-black/50 rounded-lg p-6 flex items-center justify-center">
                {{-- Replica of the public modal (front/products/index.blade.php) --}}
                <div class="bg-white rounded-lg shadow-lg w-full md:max-w-3xl p-6 relative max-w-[95%] flex flex-col md:flex-row items-center gap-6">
                    <div class="w-full md:w-1/3 flex justify-center">
                        <img :src="imageSrc" alt="Alert preview image" class="max-h-64 object-contain">
                    </div>
                    <div class="flex-1 text-center md:text-left">
                        <h2 class="text-red-600 font-bold text-2xl mb-4"
                            x-text="title || @js(\App\Services\Website\HighDemandAlertService::DEFAULT_TITLE)"></h2>
                        <p class="text-gray-700 mb-4 whitespace-pre-line"
                           x-text="message || @js(strip_tags(\App\Services\Website\HighDemandAlertService::DEFAULT_MESSAGE))"></p>
                        <p class="text-black text-lg mb-6 font-bold" x-text="phone || @js($config->phone ?? '')"></p>
                        <div class="flex flex-col sm:flex-row justify-center md:justify-end gap-3">
                            <span class="px-5 py-2 bg-green-600 text-white rounded inline-block cursor-default"
                                  x-text="buttonText || @js(\App\Services\Website\HighDemandAlertService::DEFAULT_BUTTON_TEXT)"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </form>

    </div>

    @include('admin.partials._media_picker_modal')

@endsection

@push('js')
<script>
    function hdAlertEditor() {
        return {
            title:      @js(old('high_demand_alert_title', $settings['high_demand_alert_title'] ?? '')),
            message:    @js(strip_tags(old('high_demand_alert_message', $settings['high_demand_alert_message'] ?? ''))),
            phone:      @js(old('high_demand_alert_phone', $settings['high_demand_alert_phone'] ?? '')),
            buttonText: @js(old('high_demand_alert_button_text', $settings['high_demand_alert_button_text'] ?? '')),
            mediaId:    @js($config->imageMediaId ?? ''),
            imageSrc:   @js($config->imageUrl),
            defaultImage: @js(asset(\App\Services\Website\HighDemandAlertService::DEFAULT_IMAGE_PATH)),
            usingDefault: @js($config->usesDefaultImage),
            removeImage: false,
            fileChosen(e) {
                const file = e.target.files[0];
                if (!file) return;
                this.mediaId = '';
                this.removeImage = false;
                this.usingDefault = false;
                this.imageSrc = URL.createObjectURL(file);
            },
        };
    }
</script>
@endpush
