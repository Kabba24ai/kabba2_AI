<?php

namespace App\Http\Controllers\Admin\Stores;

use App\Http\Controllers\Controller;

// Request
use App\Http\Requests\Admin\Stores\StoreRequest;

// Models
use App\Models\Stores\Store;
use App\Models\Stores\HoursOfOperation;


class StoreController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(StoreRequest $request)
    {
		// Use validated data
        $validatedData = $request->validated();

        // Normalize is_global to 'Yes' or 'No'
        //$validatedData['is_global'] = isset($validatedData['is_global']) ? 'Yes' : 'No';

        $store = Store::create($validatedData);
        // If is_primary is set to true/yes in the request
        if (isset($validatedData['is_primary']) && $validatedData['is_primary']) {
            // Remove primary flag from all other stores
            Store::where('id', '!=', $store->id)
                ->update(['is_primary' => 'No']);
        }

        // Save hours of operation
        $this->saveHours($store->id, $validatedData);

        flash('Store created successfully.')->success();

        // Determine the redirection based on the button clicked
        $action = $request->input('action');

        return match ($action) {
            'save' => redirect()->route('admin.stores.edit', ['unique_id' => $store->unique_id]),
            'save_new' => redirect()->route('admin.stores.create'),
            default => redirect()->route('admin.stores.index'), // fallback
        };
    }

    private function saveHours(int $storeId, array $data)
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

            HoursOfOperation::create([
                'store_id'   => $storeId,
                'day_name'   => ucfirst($day),
                'is_closed'  => $isClosed,

                'start_time' => $isClosed ? null : $this->convertTo24Hour($data["{$day}_start"] ?? null),
                'end_time'   => $isClosed ? null : $this->convertTo24Hour($data["{$day}_end"] ?? null),
            ]);
        }
    }

    private function convertTo24Hour(?string $time)
    {
        if (!$time) {
            return null;
        }

        // Convert "12:00 PM" to "12:00:00"
        $parsed = date("H:i:s", strtotime($time));
        return $parsed;
    }
}
