{{-- Title & Parent Category --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <div>
        <label for="title" class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300 required ">Title</label>
        {{ html()->text('title')
            ->class([
                'w-full rounded-lg border px-4 py-2 text-sm shadow-sm  dark:bg-gray-900 dark:text-white',
                'border-red-500' => $errors->has('title')
            ])
            ->attributes([
                'maxlength' => 240,
                'data-parsley-maxlength' => 240,
                'placeholder' => 'Title',
                'autocomplete' => 'off'
            ])
            ->required()
        }}
    </div>

     @if(!isset($objProduct) || (isset($objProduct) && $objProduct->childCategories()->count() <= 0))
    <div class="space-y-2">
        <label for="parent_id" class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300">Category</label>
        {{ html()->select('parent_id', $categories, null)
            ->class([
                'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-brand-400 dark:bg-gray-900 dark:text-white',
                'border-red-500' => $errors->has('parent_id')
            ])
            ->attributes([
                'data-control' => 'select2',
                'autocomplete' => 'off'
            ])
            ->placeholder('Select category')
        }}
    </div>
    @endif

</div>

{{-- Short Content --}}
<div class="mb-8">
    <label for="short_content" class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300">Short Content</label>
    {{ html()->textarea('short_content')
        ->class([
            'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-brand-400 dark:bg-gray-900 dark:text-white',
            'border-red-500' => $errors->has('short_content')
        ])
        ->attributes([
            'rows' => 3,
            'maxlength' => 1000,
            'data-parsley-maxlength' => 1000,
            'autocomplete' => 'off'
        ])
    }}
</div>

{{-- Content --}}
<div class="mb-6">
    <label for="content" class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300">Content</label>
    {{ html()->textarea('content')
        ->class('ckeditor w-full h-150 rounded-md border px-4 py-2 text-sm shadow-sm dark:bg-gray-900 dark:text-white border-gray-300 focus:ring-brand-500 focus:border-brand-500')
        ->attributes([
            'autocomplete' => 'off'
        ])
    }}
</div>


{{-- Media & Status --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <div>
        <label for="media" class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300">
            Media {!! (isset($objProduct) && $objProduct->media) ? '' : '<span class="text-red-500">*</span>' !!}
        </label>
        {{ html()->file('media')
            ->class([
                'w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-brand-400 dark:bg-gray-900 dark:text-white',
                'border-red-500' => $errors->has('media')
            ])
            ->attributes(['accept' => '.png, .jpg, .jpeg'])
            ->required(!isset($objProduct) || !$objProduct->media)
        }}

        @if(isset($objProduct) && $objProduct->media)
            <div class="mt-4 flex objProducts-center gap-3">
                <a data-fancybox href="{{ $objProduct->media->getUrl() }}">
                    <img src="{{ $objProduct->media->getUrl() }}" alt="{{ $objProduct->media->name }}"
                         class="w-16 h-16 object-cover rounded-md shadow-sm">
                </a>
                {{-- <a href="{{ route('#', ['unique_id' => $objProduct->media->unique_id]) }}"
                   data-token="{{ csrf_token() }}"
                   class="text-red-500 hover:text-red-700 font-medium delete-media-btn">
                    Remove
                </a> --}}
            </div>
        @endif
    </div>

    <div>
        <label for="status" class="block mb-2 font-medium text-sm text-gray-700 dark:text-gray-300">Status <span class="text-red-500">*</span></label>
        {{ html()->select('status', ['Active' => 'Active', 'Inactive' => 'Inactive'], null)
            ->class('w-full rounded-lg border px-4 py-2 text-sm shadow-sm focus:ring-2 focus:border-brand-400 dark:bg-gray-900 dark:text-white')
            ->required()
            ->placeholder('Please select')
        }}
    </div>
</div>
