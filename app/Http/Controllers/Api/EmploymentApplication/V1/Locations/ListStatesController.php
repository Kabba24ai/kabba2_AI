<?php

namespace App\Http\Controllers\Api\EmploymentApplication\V1\Locations;

use App\Http\Controllers\Api\BaseController;
use App\Models\Locations\State;
use Illuminate\Http\JsonResponse;

class ListStatesController extends BaseController
{
    public function __invoke(): JsonResponse
    {
        $states = State::orderBy('name', 'asc')->get();

        return response()->json([
            'success' => true,
            'message' => 'States fetched successfully.',
            'data'    => $states->map(fn ($state) => [
                'id'           => $state->id,
                'name'         => $state->name,
                'abbreviation' => $state->abbreviation,
            ]),
        ]);
    }
}
