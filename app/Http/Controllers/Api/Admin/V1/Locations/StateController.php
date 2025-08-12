<?php

namespace App\Http\Controllers\Api\Admin\V1\Locations;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;

// Requests
use App\Models\Locations\State;

// Resources
use App\Http\Resources\Api\Admin\V1\States\ListResource;

class StateController extends BaseController
{
    /**
     * States List
     *
     * @group Admin App
     * @authenticated
     */
    public function __invoke(Request $request)
    {
        $states = State::order('asc')->get();

        return response()->json([
            'success' => true,
            'message' => trans('messages.api.admin.v1.locations.states_found'),
            'states' => ListResource::collection($states),
        ]);
    }
}
