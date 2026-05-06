<?php

namespace App\Http\Controllers\Admin\Stores;

use App\Http\Controllers\Controller;

// Request
use App\Http\Requests\Admin\Stores\UpdateRequest;

// Models
use App\Models\Stores\Store;
use App\Models\Stores\HoursOfOperation;

class UpdateController extends Controller
{
    public function __invoke($unique_id, UpdateRequest $request)
    {
        $validatedData = $request->validated();

        $store = Store::where('unique_id', $unique_id)->firstOrFail();

        // Prevent direct demotion of the current primary store.
        if ($store->is_primary === 'Yes' && ($validatedData['is_primary'] ?? null) === 'No') {
            return redirect()->back()
                ->withInput()
                ->withErrors([
                    'is_primary' => 'You must assign another store as primary before this one can be changed to an alternative.'
                ]);
        }

        // Handle primary store logic
        if ($validatedData['is_primary'] === 'Yes') {
            Store::where('id', '!=', $store->id)->update(['is_primary' => 'No']);
        }

        // Update main store fields
        $store->fill($validatedData)->save();

        // Update hours
        $this->updateHours($store->id, $validatedData);

        flash('Store updated successfully.')->success();

        $action = $request->input('action');

        return match ($action) {
            'save' => redirect()->route('admin.stores.edit', ['unique_id' => $store->unique_id]),
            'save_new' => redirect()->route('admin.stores.create'),
            default => redirect()->route('admin.stores.index'),
        };
    }

    private function updateHours(int $storeId, array $data)
    {
        $days = [
            'monday',
            'tuesday',
            'wednesday',
            'thursday',
            'friday',
            'saturday',
            'sunday'
        ];

        foreach ($days as $day) {

            $isClosed = $data["{$day}_closed"];
            $start = $data["{$day}_start"] ?? null;
            $end = $data["{$day}_end"] ?? null;

            $record = HoursOfOperation::where('store_id', $storeId)
                ->where('day_name', ucfirst($day))
                ->first();

            if ($record) {
                // Update existing record
                $record->update([
                    'is_closed'  => $isClosed,
                    'is_lunch_required' => $data["{$day}_lunch"] ?? false,
                    'start_time' => $isClosed ? null : $this->convertTo24Hour($start),
                    'end_time'   => $isClosed ? null : $this->convertTo24Hour($end),
                ]);
            } else {
                // Create missing day
                HoursOfOperation::create([
                    'store_id'   => $storeId,
                    'day_name'   => ucfirst($day),
                    'is_closed'  => $isClosed,
                    'start_time' => $isClosed ? null : $this->convertTo24Hour($start),
                    'end_time'   => $isClosed ? null : $this->convertTo24Hour($end),
                ]);
            }
        }
    }


    private function convertTo24Hour(?string $time)
    {
        if (!$time) return null;
        return date("H:i:s", strtotime($time));
    }
}
