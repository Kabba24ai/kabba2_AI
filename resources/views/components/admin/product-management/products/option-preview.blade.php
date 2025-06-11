<div>
<div class="overflow-x-auto">
  <table class="min-w-full border-collapse">
    <thead>
      <tr class="bg-gray-50 text-xs font-medium text-gray-500 uppercase">
        <th class="px-3 py-2">#</th>
        <th class="px-3 py-2">Drag</th>
        <th class="px-3 py-2 text-left">Label</th>

        @if($productType === 'rental')
          <th class="px-3 py-2">Daily</th>
          <th class="px-3 py-2">W/E Spcl.</th>
          <th class="px-3 py-2">Weekly</th>
          <th class="px-3 py-2">Monthly</th>
        @elseif($productType === 'retail')
          <th class="px-3 py-2 text-left">Retail Price</th>
        @endif

        <th class="px-3 py-2">Charged Per Order</th>
        <th class="px-3 py-2">Value</th>
        <th class="px-3 py-2">Comment</th>
        <th class="px-3 py-2">Actions</th>
      </tr>
    </thead>

    <tbody class="divide-y divide-gray-200">
      @forelse($options as $i => $opt)
        <tr class="hover:bg-gray-50">
          <td class="px-3 py-2">{{ $i + 1 }}</td>

          {{-- drag handle --}}
          <td class="px-3 py-2 cursor-move">
            <svg class="h-4 w-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
              <path d="M7 4a1 1 0 100 2h6a1 1 0 100-2H7zM7 9a1 1 0 100 2h6a1 1 0 100-2H7zM7 14a1 1 0 100 2h6a1 1 0 100-2H7z"/>
            </svg>
          </td>

          {{-- label --}}
          <td class="px-3 py-2">
            <input
              type="text"
              name="options[{{ $opt->id }}][label]"
              value="{{ $opt->name }}"
              class="w-full rounded border-gray-300 text-sm"
            >
          </td>

          @if($productType === 'rental')
            <td class="px-3 py-2">
              <input type="number" step="0.01"
                     name="options[{{ $opt->id }}][daily]"
                     class="w-full rounded border-gray-300 text-sm"
                     value="{{ old('options.'.$opt->id.'.daily', $opt->pivot->daily ?? '') }}">
            </td>
            <td class="px-3 py-2">
              <input type="number" step="0.01"
                     name="options[{{ $opt->id }}][weekend_special]"
                     class="w-full rounded border-gray-300 text-sm"
                     value="{{ old('options.'.$opt->id.'.weekend_special', $opt->pivot->weekend_special ?? '') }}">
            </td>
            <td class="px-3 py-2">
              <input type="number" step="0.01"
                     name="options[{{ $opt->id }}][weekly]"
                     class="w-full rounded border-gray-300 text-sm"
                     value="{{ old('options.'.$opt->id.'.weekly', $opt->pivot->weekly ?? '') }}">
            </td>
            <td class="px-3 py-2">
              <input type="number" step="0.01"
                     name="options[{{ $opt->id }}][monthly]"
                     class="w-full rounded border-gray-300 text-sm"
                     value="{{ old('options.'.$opt->id.'.monthly', $opt->pivot->monthly ?? '') }}">
            </td>

          @elseif($productType === 'retail')
            <td class="px-3 py-2">
              <input type="number" step="0.01"
                     name="options[{{ $opt->id }}][retail_price]"
                     class="w-full rounded border-gray-300 text-sm"
                     value="{{ old('options.'.$opt->id.'.retail_price', $opt->pivot->retail_price ?? '') }}">
            </td>
          @endif

          {{-- charged per order --}}
          <td class="px-3 py-2">
            <select
              name="options[{{ $opt->id }}][charged_per_order]"
              class="w-full rounded border-gray-300 text-sm"
            >
              <option value="unlimited"
                @selected(old('options.'.$opt->id.'.charged_per_order', $opt->pivot->charged_per_order ?? '') === 'unlimited')
              >Unlimited</option>
              <option value="per_item"
                @selected(old('options.'.$opt->id.'.charged_per_order', $opt->pivot->charged_per_order ?? '') === 'per_item')
              >Per Item</option>
            </select>
          </td>

          {{-- value --}}
          <td class="px-3 py-2">
            <select
              name="options[{{ $opt->id }}][value]"
              class="w-full rounded border-gray-300 text-sm"
            >
              <option value="blank"
                @selected(old('options.'.$opt->id.'.value', $opt->pivot->value ?? '') === 'blank')
              >Blank</option>
              <option value="checked"
                @selected(old('options.'.$opt->id.'.value', $opt->pivot->value ?? '') === 'checked')
              >Checked</option>
            </select>
          </td>

          {{-- comment icon --}}
          <td class="px-3 py-2 text-center">
            <button type="button" class="text-gray-400 hover:text-gray-600">
              <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M18 10c0 3.866-3.582 7-8 7a8.082 8.082 0 01-4.427-1.253L2 17l1.253-3.573A8.082 8.082 0 012 10c0-4.418 3.134-8 7-8s8 3.582 8 8z"/>
              </svg>
            </button>
          </td>

          {{-- delete --}}
          <td class="px-3 py-2 text-center">
            <button type="button" class="text-red-500 hover:text-red-700">
              &times;
            </button>
          </td>
        </tr>

      @empty
        <tr>
          <td colspan="{{ $productType==='rental' ? 11 : 8 }}" class="py-4 text-center text-gray-500">
            No options selected.
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>

</div>
