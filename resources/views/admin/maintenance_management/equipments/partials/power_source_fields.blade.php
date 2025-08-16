@if($powerSourceType === 'diesel')
    <div class="flex items-center space-x-2">
        <input type="checkbox" name="has_def" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" 
            {{ old('has_def', $equipment->has_def ?? false) ? 'checked' : '' }}>
        <label class="text-sm font-medium text-gray-700">Has DEF (Diesel Exhaust Fluid)</label>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Diesel Tank Capacity (Gallons)</label>
        <input type="number" name="diesel_tank_capacity" placeholder="0" min="0" step="0.1"
            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
            value="{{ old('diesel_tank_capacity', $equipment->diesel_tank_capacity ?? '') }}">
    </div>
@elseif($powerSourceType === 'gas')
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Gas Tank Capacity (Gallons)</label>
        <input type="number" name="gas_tank_capacity" placeholder="0" min="0" step="0.1"
            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
            value="{{ old('gas_tank_capacity', $equipment->gas_tank_capacity ?? '') }}">
    </div>
@elseif($powerSourceType === 'batteries')
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Standard Battery Count</label>
        <input type="number" name="standard_battery_count" placeholder="0" min="0" step="1"
            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
            value="{{ old('standard_battery_count', $equipment->standard_battery_count ?? '') }}">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Expanded Battery Count</label>
        <input type="number" name="expanded_battery_count" placeholder="0" min="0" step="1"
            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
            value="{{ old('expanded_battery_count', $equipment->expanded_battery_count ?? '') }}">
    </div>
@endif