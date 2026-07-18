<?php

namespace App\Http\Controllers\Admin\Stores;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

// Request
use App\Http\Requests\Admin\Stores\UpdateRequest;

// Models
use App\Models\Stores\Store;
use App\Models\Stores\HoursOfOperation;
use App\Models\Stores\StoreServiceArea;

class UpdateController extends Controller
{
    use Concerns\SavesPublicPage;

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

        DB::transaction(function () use ($store, $validatedData) {
            // Update main store fields
            $store->fill($validatedData)->save();

            // Update hours
            $this->updateHours($store->id, $validatedData);

            // Update service areas
            $this->saveServiceAreas($store->id, $validatedData);

            // Update public website page settings
            $this->savePublicPage($store, $validatedData);
        });

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


    private function saveServiceAreas(int $storeId, array $data): void
    {
        // Remove all existing service areas for this store and replace
        StoreServiceArea::where('store_id', $storeId)->delete();

        // Radius row
        if (!empty($data['service_area_enable_radius'])) {
            StoreServiceArea::create([
                'store_id'         => $storeId,
                'area_group'       => 'radius',
                'area_type'        => 'radius',
                'radius_miles'     => $data['service_area_radius_miles'] ?? null,
                'delivery_allowed' => (bool) ($data['service_area_delivery_allowed'] ?? true),
                'pickup_allowed'   => (bool) ($data['service_area_pickup_allowed']   ?? true),
                'is_active'        => true,
            ]);
        }

        // Included areas
        foreach ($data['service_areas_included'] ?? [] as $sort => $row) {
            if (empty($row['area_type'])) continue;
            StoreServiceArea::create([
                'store_id'         => $storeId,
                'area_group'       => 'included',
                'area_type'        => $row['area_type'],
                'name'             => $row['name']     ?? null,
                'city'             => $row['city']     ?? null,
                'county'           => $row['county']   ?? null,
                'state'            => $row['state']    ?? null,
                'zip_code'         => $row['zip_code'] ?? null,
                'delivery_allowed' => (bool) ($row['delivery_allowed'] ?? true),
                'pickup_allowed'   => (bool) ($row['pickup_allowed']   ?? true),
                'notes'            => $row['notes']    ?? null,
                'sort_order'       => (int) $sort,
                'is_active'        => (bool) ($row['is_active'] ?? true),
            ]);
        }

        // Excluded areas
        foreach ($data['service_areas_excluded'] ?? [] as $sort => $row) {
            if (empty($row['area_type'])) continue;
            StoreServiceArea::create([
                'store_id'   => $storeId,
                'area_group' => 'excluded',
                'area_type'  => $row['area_type'],
                'name'       => $row['name']     ?? null,
                'city'       => $row['city']     ?? null,
                'county'     => $row['county']   ?? null,
                'state'      => $row['state']    ?? null,
                'zip_code'   => $row['zip_code'] ?? null,
                'notes'      => $row['notes']    ?? null,
                'sort_order' => (int) $sort,
                'is_active'  => (bool) ($row['is_active'] ?? true),
            ]);
        }
    }

    private function convertTo24Hour(?string $time)
    {
        if (!$time) return null;
        return date("H:i:s", strtotime($time));
    }
}
