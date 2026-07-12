<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a lifecycle idempotency-key collision resolves to an EXISTING
 * transition whose terminal status differs from the one being recorded — i.e.
 * two competing terminal outcomes raced within the same cycle. Surfacing this
 * (rather than swallowing the collision) rolls back the whole transaction so
 * the source status can never disagree with the recorded lifecycle event.
 */
class AlertLifecycleConsistencyException extends RuntimeException
{
}
