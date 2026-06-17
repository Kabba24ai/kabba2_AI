@php
// $group  : 'included' | 'excluded'
// $i      : integer index or '__IDX__' (JS template placeholder)
// $area   : StoreServiceArea|object|null
// $states : collection [id => name]

$areaType   = $area->area_type ?? '';
$name       = $area->name ?? '';
$city       = $area->city ?? '';
$county     = $area->county ?? '';
$areaState  = $area->state ?? '';
$zip        = $area->zip_code ?? '';
$notes      = $area->notes ?? '';
$deliveryOn = isset($area->delivery_allowed) ? ($area->delivery_allowed ? '1' : '0') : '1';
$pickupOn   = isset($area->pickup_allowed)   ? ($area->pickup_allowed   ? '1' : '0') : '1';
$isActive   = isset($area->is_active)        ? ($area->is_active        ? '1' : '0') : '1';
@endphp

<td class="pr-2 py-2">
    <select name="service_areas_{{ $group }}[{{ $i }}][area_type]"
            class="border rounded px-2 py-1 text-sm bg-white text-gray-700 border-gray-300 w-28">
        <option value="city"        @selected($areaType === 'city')>City</option>
        <option value="county"      @selected($areaType === 'county')>County</option>
        <option value="zip"         @selected($areaType === 'zip')>ZIP Code</option>
        <option value="custom_area" @selected($areaType === 'custom_area')>Custom Area</option>
    </select>
</td>

<td class="pr-2 py-2">
    <input type="text"
           name="service_areas_{{ $group }}[{{ $i }}][name]"
           value="{{ $name }}"
           placeholder="Name"
           class="border rounded px-2 py-1 text-sm bg-white text-gray-700 border-gray-300 w-24">
</td>

<td class="pr-2 py-2">
    <input type="text"
           name="service_areas_{{ $group }}[{{ $i }}][city]"
           value="{{ $city }}"
           placeholder="City"
           class="border rounded px-2 py-1 text-sm bg-white text-gray-700 border-gray-300 w-24">
</td>

<td class="pr-2 py-2">
    <input type="text"
           name="service_areas_{{ $group }}[{{ $i }}][county]"
           value="{{ $county }}"
           placeholder="County"
           class="border rounded px-2 py-1 text-sm bg-white text-gray-700 border-gray-300 w-24">
</td>

<td class="pr-2 py-2">
    <select name="service_areas_{{ $group }}[{{ $i }}][state]"
            class="border rounded px-2 py-1 text-sm bg-white text-gray-700 border-gray-300 w-28">
        <option value="">State</option>
        @foreach ($states as $stateId => $stateName)
            @if ($stateId !== '')
                <option value="{{ $stateName }}" @selected($areaState === $stateName)>{{ $stateName }}</option>
            @endif
        @endforeach
    </select>
</td>

<td class="pr-2 py-2">
    <input type="text"
           name="service_areas_{{ $group }}[{{ $i }}][zip_code]"
           value="{{ $zip }}"
           placeholder="ZIP"
           class="border rounded px-2 py-1 text-sm bg-white text-gray-700 border-gray-300 w-20">
</td>

@if ($group === 'included')
<td class="pr-2 py-2 text-center">
    <select name="service_areas_{{ $group }}[{{ $i }}][delivery_allowed]"
            class="border rounded px-2 py-1 text-sm bg-white text-gray-700 border-gray-300">
        <option value="1" @selected($deliveryOn === '1')>Yes</option>
        <option value="0" @selected($deliveryOn === '0')>No</option>
    </select>
</td>
<td class="pr-2 py-2 text-center">
    <select name="service_areas_{{ $group }}[{{ $i }}][pickup_allowed]"
            class="border rounded px-2 py-1 text-sm bg-white text-gray-700 border-gray-300">
        <option value="1" @selected($pickupOn === '1')>Yes</option>
        <option value="0" @selected($pickupOn === '0')>No</option>
    </select>
</td>
@endif

<td class="pr-2 py-2">
    <input type="text"
           name="service_areas_{{ $group }}[{{ $i }}][notes]"
           value="{{ $notes }}"
           placeholder="{{ $group === 'excluded' ? 'Reason / Notes' : 'Notes' }}"
           class="border rounded px-2 py-1 text-sm bg-white text-gray-700 border-gray-300 w-32">
</td>

<td class="pr-2 py-2 text-center">
    <select name="service_areas_{{ $group }}[{{ $i }}][is_active]"
            class="border rounded px-2 py-1 text-sm bg-white text-gray-700 border-gray-300">
        <option value="1" @selected($isActive === '1')>Active</option>
        <option value="0" @selected($isActive === '0')>Inactive</option>
    </select>
</td>

<td class="py-2">
    <button type="button"
            onclick="removeServiceAreaRow(this, '{{ $group }}')"
            class="text-red-500 hover:text-red-700 text-xs font-medium whitespace-nowrap">
        Remove
    </button>
</td>
