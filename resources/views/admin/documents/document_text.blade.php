@extends('admin.layouts.app')

@section('title', 'Price List — Document Text')

@push('css')
<style>
    main { background-color: #f8fafc; flex: 1 1 auto; }
</style>
@endpush

@section('content')

    @include('flash::message')

    @include('admin.documents.partials._price_list_nav', [
        'plSubtitle' => 'Edit the text printed on the Customer Price List. Merge codes are replaced with live '
            . 'company and store values each time a document is generated — the raw codes stay visible here.',
    ])

    <form method="POST" action="{{ route('admin.documents.price-list.text.save') }}">
        @csrf

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
            <div class="grid grid-cols-1 gap-4">
                <div class="max-w-xl">
                    <label for="price_list_title" class="block text-sm font-medium text-gray-700 mb-1.5">
                        Document Title <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="price_list_title" id="price_list_title" maxlength="120" required
                        value="{{ old('price_list_title', $title) }}"
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Title printed in the document header">
                    @error('price_list_title') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="price_list_value_message" class="block text-sm font-medium text-gray-700 mb-1.5">
                        Value Message
                    </label>
                    <textarea name="price_list_value_message" id="price_list_value_message" rows="3" maxlength="1000"
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Highlighted message on the final page (merge codes supported)">{{ old('price_list_value_message', $valueMessage) }}</textarea>
                    @error('price_list_value_message') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="price_list_disclaimer" class="block text-sm font-medium text-gray-700 mb-1.5">
                        Disclaimer / Final-Page Text
                    </label>
                    <textarea name="price_list_disclaimer" id="price_list_disclaimer" rows="12" maxlength="5000"
                        class="w-full rounded-lg border-gray-300 text-sm font-mono focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Final-page disclaimer (merge codes supported)">{{ old('price_list_disclaimer', $disclaimer) }}</textarea>
                    <p class="mt-1 text-xs text-gray-400">
                        Printed under "Important Pricing Information" on the final page. Line breaks are preserved.
                    </p>
                    @error('price_list_disclaimer') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                @include('admin.documents.partials._merge_codes_help')
            </div>
        </div>

        <div class="flex items-center gap-3 mb-6">
            <button type="submit"
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium text-sm transition">
                <x-heroicon-o-check class="w-4 h-4" />
                Save Document Text
            </button>
            <a href="{{ route('admin.documents.price-list.form') }}"
                class="text-sm font-medium text-gray-500 hover:text-gray-700">Back to Generate</a>
        </div>
    </form>

@endsection
