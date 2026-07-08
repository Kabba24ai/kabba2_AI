{{-- Dispatch assignment form — expects $ticket, $technicians, $trucks, $inputClass, $labelClass --}}
<form method="POST" action="{{ route('admin.field-service.tickets.dispatch.update', $ticket) }}">
    @csrf
    @method('PUT')
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <label class="{{ $labelClass }}">Technician</label>
            <select name="technician_id" class="{{ $inputClass }}">
                <option value="">— Not assigned yet —</option>
                @foreach ($technicians as $technician)
                    <option value="{{ $technician->id }}" @selected((int) old('technician_id', $ticket->technician_id) === $technician->id)>
                        {{ $technician->first_name }} {{ $technician->last_name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="{{ $labelClass }}">Service Truck</label>
            <select name="truck_id" class="{{ $inputClass }}">
                <option value="">— Not assigned yet —</option>
                @foreach ($trucks as $truck)
                    <option value="{{ $truck->id }}" @selected((int) old('truck_id', $ticket->truck_id) === $truck->id)>
                        {{ $truck->truck_name }}{{ $truck->truck_number ? ' #' . $truck->truck_number : '' }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="{{ $labelClass }}">Estimated Departure</label>
            <input type="datetime-local" name="estimated_departure_at"
                value="{{ old('estimated_departure_at', $ticket->estimated_departure_at?->format('Y-m-d\TH:i')) }}"
                class="{{ $inputClass }}">
        </div>
        <div>
            <label class="{{ $labelClass }}">Estimated Arrival</label>
            <input type="datetime-local" name="estimated_arrival_at"
                value="{{ old('estimated_arrival_at', $ticket->estimated_arrival_at?->format('Y-m-d\TH:i')) }}"
                class="{{ $inputClass }}">
            @error('estimated_arrival_at')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="sm:col-span-2">
            <label class="{{ $labelClass }}">Suggested Tools</label>
            <textarea name="suggested_tools" rows="2" class="{{ $inputClass }}">{{ old('suggested_tools', $ticket->suggested_tools) }}</textarea>
        </div>
        <div class="sm:col-span-2">
            <label class="{{ $labelClass }}">Suggested Parts</label>
            <textarea name="suggested_parts" rows="2" class="{{ $inputClass }}">{{ old('suggested_parts', $ticket->suggested_parts) }}</textarea>
        </div>
        <div class="sm:col-span-2">
            <label class="{{ $labelClass }}">Special Instructions</label>
            <textarea name="special_instructions" rows="2" class="{{ $inputClass }}">{{ old('special_instructions', $ticket->special_instructions) }}</textarea>
        </div>
    </div>
    <div class="flex justify-end mt-3">
        <button type="submit"
            class="px-4 py-2 rounded-lg text-sm font-medium bg-indigo-600 text-white hover:bg-indigo-700 transition">
            Save Assignment
        </button>
    </div>
</form>
