<?php

namespace App\Http\Controllers\Admin\Stores;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

// Request
use App\Http\Requests\Admin\Stores\StoreRequest;

// Models
use App\Models\Stores\Store;
use App\Models\Stores\HoursOfOperation;
use App\Models\Stores\StoreServiceArea;


class StoreController extends Controller
{
    use Concerns\SavesPublicPage;

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

        $store = DB::transaction(function () use ($validatedData) {
            $newStore = Store::create($validatedData);

            if (($validatedData['is_primary'] ?? null) === 'Yes') {
                Store::where('id', '!=', $newStore->id)->update(['is_primary' => 'No']);
            }

            $this->saveHours($newStore->id, $validatedData);
            $this->saveServiceAreas($newStore->id, $validatedData);
            $this->savePublicPage($newStore, $validatedData);

            return $newStore;
        });

        flash('Store created successfully.')->success();

        $action = $request->input('action');

        return match ($action) {
            'save'     => redirect()->route('admin.stores.edit', ['unique_id' => $store->unique_id]),
            'save_new' => redirect()->route('admin.stores.create'),
            default    => redirect()->route('admin.stores.index'),
        };
    }

    private function saveServiceAreas(int $storeId, array $data): void
    {
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
'is_lunch_required' => $data["{$day}_lunch"] ?? false,
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
