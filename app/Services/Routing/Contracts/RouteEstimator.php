<?php

namespace App\Services\Routing\Contracts;

use App\Services\Routing\RouteEstimateRequest;
use App\Services\Routing\RouteEstimateResult;

/**
 * Computes a single point-to-point route estimate. Provider-neutral and shared:
 * Field Service calls it for one technician/truck leg today; Dispatch will call
 * it once per stop-to-stop leg later, with no change to this contract.
 * Implementations must never throw for a provider/geocoding failure — they
 * return a RouteEstimateResult carrying the normalized RouteStatus.
 */
interface RouteEstimator
{
    public function estimate(RouteEstimateRequest $request): RouteEstimateResult;
}
